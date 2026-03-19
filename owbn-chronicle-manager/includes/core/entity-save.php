<?php
/**
 * Generic Entity Save Handler
 *
 * Provides a single save_post handler for all registered entity types,
 * driven by entity config from the registry. Replaces per-entity save files.
 *
 */

if (!defined('ABSPATH')) exit;

/**
 * Main save handler for all registered entity types.
 *
 * Hooked on save_post. Looks up entity config from the post type,
 * verifies nonce, iterates field definitions, and delegates to
 * owbn_save_entity_field() for type-based sanitization.
 *
 * @param int     $post_id The post ID being saved.
 * @param WP_Post $post    The post object being saved.
 * @return void
 */
function owbn_save_entity_meta(int $post_id, WP_Post $post): void
{
    // Skip autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Look up entity config from post type
    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return;

    $entity_key = $config['entity_key'];

    // Verify nonce
    $nonce_name = "owbn_{$entity_key}_nonce";
    $nonce_action = "owbn_{$entity_key}_save";

    if (
        !isset($_POST[$nonce_name]) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_name])), $nonce_action)
    ) {
        return;
    }

    // If validation failed on a published post, skip all meta saves to preserve existing data
    if (get_transient("owbn_{$entity_key}_validation_blocked_{$post_id}")) {
        delete_transient("owbn_{$entity_key}_validation_blocked_{$post_id}");
        return;
    }

    // Get field definitions by calling the config's callable
    $field_definitions_callable = $config['field_definitions'] ?? null;
    if (!is_callable($field_definitions_callable)) return;

    $definitions = call_user_func($field_definitions_callable);

    // Capture old values for change notification
    $old_values = [];
    foreach ($definitions as $fields) {
        foreach ($fields as $key => $meta) {
            $old_values[$key] = get_post_meta($post_id, $key, true);
        }
    }

    // Get immutable and restricted fields from config
    $immutable_fields  = $config['immutable_fields'] ?? [];
    $restricted_fields = $config['restricted_fields'] ?? [];
    $staff_fields      = $config['staff_fields'] ?? [];

    $is_admin = owbn_is_admin_user();
    $staff_user_dirty = false;

    // Determine if pending changeset logic applies:
    // Only for published posts edited by non-admins that have staff fields defined
    $is_published  = ($post->post_status === 'publish');
    $needs_pending = ($is_published && !$is_admin && !empty($staff_fields));
    $pending_values = [];

    // Loop all field definitions
    foreach ($definitions as $fields) {
        foreach ($fields as $key => $meta) {

            // IMMUTABLE: Never change once set
            if (in_array($key, $immutable_fields, true)) {
                $existing = get_post_meta($post_id, $key, true);
                if (!empty($existing)) {
                    continue;
                }
            }

            // RESTRICTED: Skip if field wasn't submitted (was disabled) and user is not admin
            if (in_array($key, $restricted_fields, true) && !$is_admin) {
                if (!isset($_POST[$key])) {
                    continue;
                }
            }

            $raw = owbn_safe_post_value($key);

            // For staff fields on published posts by non-admins:
            // Sanitize but don't save to live meta — collect for pending changeset
            if ($needs_pending && in_array($key, $staff_fields, true)) {
                $sanitized = owbn_sanitize_staff_field_for_pending($key, $meta);
                $previous  = get_post_meta($post_id, $key, true);

                if (owbn_detect_staff_field_dirty($key, $meta, $sanitized, $previous)) {
                    $staff_user_dirty = true;
                    $pending_values[$key] = $sanitized;
                } else {
                    // User assignment didn't change — save normally
                    // (non-user subfields like display_name may have been updated)
                    $staff_user_dirty = owbn_save_entity_field($post_id, $key, $meta, $raw, $staff_user_dirty);
                }
                continue;
            }

            // Normal save for non-staff fields (or staff fields when admin/draft)
            $staff_user_dirty = owbn_save_entity_field($post_id, $key, $meta, $raw, $staff_user_dirty);
        }
    }

    // Handle pending changeset for published posts by non-admins
    if ($needs_pending && !empty($pending_values)) {
        // Self-promotion detection on pending values
        $current_user_id = (string) get_current_user_id();
        $self_promoted = false;

        foreach ($pending_values as $field_value) {
            if (is_array($field_value) && isset($field_value['user']) && $field_value['user'] === $current_user_id) {
                $self_promoted = true;
                break;
            }
        }

        // Store pending changeset (replaces any previous pending)
        update_post_meta($post_id, '_owbn_pending_changes', [
            'fields'        => $pending_values,
            'submitted_by'  => get_current_user_id(),
            'submitted_at'  => current_time('mysql'),
            'self_promoted' => $self_promoted,
        ]);

        set_transient("owbn_{$entity_key}_pending_notice_{$post_id}", true, 60);

        // Revoke removed users immediately (no approval needed for revokes)
        $pending_new = $old_values;
        foreach ($pending_values as $k => $v) {
            $pending_new[$k] = $v;
        }
        owbn_sync_staff_roles($post_id, $config, $old_values, $pending_new, true);

        // Notify about pending changeset
        owbn_send_change_notification($post_id, $config, $old_values, $pending_new, true);

        return; // Do NOT call owbn_handle_entity_staff_change — post stays published
    }

    // For admins or draft posts, use existing staff change handler
    if ($staff_user_dirty) {
        // Admin save is authoritative — clear any pending changeset
        if ($is_admin) {
            delete_post_meta($post_id, '_owbn_pending_changes');
        }
        owbn_handle_entity_staff_change($post_id, $config);
    }

    // Sync accessSchema roles for admin saves (both grant and revoke)
    owbn_sync_staff_roles($post_id, $config, $old_values);

    // Send change notification for all saves (staff dirty or not)
    owbn_send_change_notification($post_id, $config, $old_values);
}

/**
 * Save an individual entity field with type-based sanitization.
 *
 * @param int    $post_id          The post ID.
 * @param string $key              The meta key.
 * @param array  $meta             The field definition array (must include 'type').
 * @param mixed  $raw              The raw value from owbn_safe_post_value().
 * @param bool   $staff_user_dirty Whether staff user changes have been detected so far.
 * @return bool Updated $staff_user_dirty value.
 */
function owbn_save_entity_field(int $post_id, string $key, array $meta, $raw, bool $staff_user_dirty): bool
{
    switch ($meta['type']) {
        case 'readonly_history':
            // Read-only — never saved from POST data.
            break;

        case 'slug':
            update_post_meta($post_id, $key, strtolower(sanitize_text_field($raw)));
            break;

        case 'select':
            update_post_meta($post_id, $key, sanitize_text_field($raw));
            break;

        case 'multi_select':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $value = isset($_POST[$key]) && is_array($_POST[$key])
                ? array_map('sanitize_text_field', wp_unslash($_POST[$key]))
                : [];
            update_post_meta($post_id, $key, $value);
            break;

        case 'ast_group':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $previous = get_post_meta($post_id, $key, true);
            $previous_users = is_array($previous) ? array_column($previous, 'user') : [];

            $cleaned = owbn_sanitize_ast_group($group_data, $meta['fields']);

            // Check for new user flag
            foreach ($cleaned as $row) {
                if (!empty($row['user']) && $row['user'] === '__new__') {
                    $staff_user_dirty = true;
                    break;
                }
            }

            $new_users = array_column($cleaned, 'user');
            if (owbn_users_changed($previous_users, $new_users)) {
                $staff_user_dirty = true;
            }

            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'session_group':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_session_group($group_data, $meta['fields']);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'ooc_location':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_ooc_location($group_data, $meta['fields']);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'location_group':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_location_group($group_data, $meta['fields']);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'repeatable_group':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_repeatable_group($group_data, $meta['fields']);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'document_links_group':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $existing = get_post_meta($post_id, $key, true);
            $cleaned = owbn_sanitize_document_links($group_data, $post_id, $key, $existing);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'social_links_group':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_social_links($group_data);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'email_lists_group':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_email_lists($group_data);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'player_lists_group':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_player_lists($group_data);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'user_info':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $info = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_user_info($info);

            $previous = get_post_meta($post_id, $key, true);
            $previous_user = $previous['user'] ?? '';

            // Only flag dirty if user actually changed to a different value
            if (!empty($cleaned['user']) && $cleaned['user'] !== '__new__') {
                if (owbn_users_changed([$previous_user], [$cleaned['user']])) {
                    $staff_user_dirty = true;
                }
            } elseif (!empty($cleaned['user']) && $cleaned['user'] === '__new__') {
                $staff_user_dirty = true;
            }

            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'chronicle_select':
            $parent_id = intval($raw);
            if ($parent_id > 0 && get_post_type($parent_id) === 'owbn_chronicle') {
                update_post_meta($post_id, $key, $parent_id);
            } else {
                delete_post_meta($post_id, $key);
            }
            break;

        case 'boolean':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            update_post_meta($post_id, $key, isset($_POST[$key]) ? '1' : '0');
            break;

        case 'wysiwyg':
            update_post_meta($post_id, $key, wp_kses_post($raw));
            break;

        case 'json':
            $value = json_decode($raw, true);
            update_post_meta($post_id, $key, is_array($value) ? $value : sanitize_text_field($raw));
            break;

        case 'url':
            update_post_meta($post_id, $key, esc_url_raw($raw));
            break;

        default:
            update_post_meta($post_id, $key, sanitize_text_field($raw));
            break;
    }

    return $staff_user_dirty;
}

/**
 * Handle staff user changes for any entity type.
 *
 * Applies exclusive field rules, detects self-promotion, and sets
 * the post to draft if the change requires admin review.
 *
 * @param int   $post_id The post ID.
 * @param array $config  The entity type configuration array.
 * @return void
 */
function owbn_handle_entity_staff_change(int $post_id, array $config): void
{
    $entity_key = $config['entity_key'];
    $current_user = wp_get_current_user();
    $current_user_id = (string) $current_user->ID;
    $is_allowed = owbn_is_admin_user($current_user);

    // Admin save is authoritative — clear any pending changeset
    if ($is_allowed) {
        delete_post_meta($post_id, '_owbn_pending_changes');
    }

    // Apply exclusive_fields rules from config
    $exclusive_fields = $config['exclusive_fields'] ?? [];
    foreach ($exclusive_fields as $rule) {
        $condition_field = $rule['condition'][0] ?? '';
        $condition_value = $rule['condition'][1] ?? '';
        $clear_fields    = $rule['clear'] ?? [];

        if ($condition_field && get_post_meta($post_id, $condition_field, true) === $condition_value) {
            foreach ($clear_fields as $field_to_clear) {
                delete_post_meta($post_id, $field_to_clear);
            }
        }
    }

    // Self-promotion detection: check if current user assigned themselves to a staff field
    $staff_fields = $config['staff_fields'] ?? [];
    $self_promoted = false;

    foreach ($staff_fields as $staff_field_key) {
        $staff_meta = get_post_meta($post_id, $staff_field_key, true);
        if (is_array($staff_meta) && isset($staff_meta['user']) && $staff_meta['user'] === $current_user_id) {
            $self_promoted = true;
            break;
        }
    }

    // Flag for admin review but keep the post published — old data stays visible
    if (!$is_allowed || $self_promoted) {
        set_transient("owbn_{$entity_key}_dirty_notice_{$post_id}", true, 60);
    }
}

/**
 * Sanitize a staff field value for pending storage without writing to post meta.
 *
 * Mirrors the sanitization logic from owbn_save_entity_field() for user_info
 * and ast_group types but returns the sanitized value instead of persisting it.
 *
 * @param string $key  The meta key.
 * @param array  $meta The field definition array.
 * @return mixed The sanitized value.
 */
function owbn_sanitize_staff_field_for_pending(string $key, array $meta)
{
    switch ($meta['type']) {
        case 'user_info':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $info = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            return owbn_sanitize_user_info($info);

        case 'ast_group':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            return owbn_sanitize_ast_group($group_data, $meta['fields']);

        default:
            return owbn_safe_post_value($key);
    }
}

/**
 * Detect if a staff field's user assignment has changed.
 *
 * Compares the user IDs between the new sanitized value and the existing
 * meta value. Returns true if the user assignment is different.
 *
 * @param string $key       The meta key.
 * @param array  $meta      The field definition array.
 * @param mixed  $new_value The new sanitized value.
 * @param mixed  $previous  The existing meta value from the database.
 * @return bool True if the user assignment changed.
 */
function owbn_detect_staff_field_dirty(string $key, array $meta, $new_value, $previous): bool
{
    switch ($meta['type']) {
        case 'user_info':
            $previous_user = $previous['user'] ?? '';
            $new_user      = $new_value['user'] ?? '';

            if (!empty($new_user) && $new_user === '__new__') {
                return true;
            }
            return owbn_users_changed([$previous_user], [$new_user]);

        case 'ast_group':
            $previous_users = is_array($previous) ? array_column($previous, 'user') : [];
            $new_users      = is_array($new_value) ? array_column($new_value, 'user') : [];

            foreach ((array) $new_value as $row) {
                if (!empty($row['user']) && $row['user'] === '__new__') {
                    return true;
                }
            }
            return owbn_users_changed($previous_users, $new_users);

        default:
            return false;
    }
}

if (!function_exists('owbn_sanitize_session_group')) {
    function owbn_sanitize_session_group($group_data, $meta_fields)
    {
        $cleaned = [];
        if (!is_array($group_data)) return $cleaned;

        foreach ($group_data as $row) {
            $row_cleaned = [];
            foreach ($meta_fields as $sub_key => $sub_meta) {
                if (!isset($row[$sub_key])) continue;
                $raw = $row[$sub_key];

                switch ($sub_meta['type']) {
                    case 'wysiwyg':
                        $row_cleaned[$sub_key] = wp_kses_post($raw);
                        break;
                    case 'multi_select':
                        $row_cleaned[$sub_key] = is_array($raw) ? array_map('sanitize_text_field', $raw) : [];
                        break;
                    case 'email':
                        $row_cleaned[$sub_key] = sanitize_email($raw);
                        break;
                    default:
                        $row_cleaned[$sub_key] = is_array($raw) ? array_map('sanitize_text_field', $raw) : sanitize_text_field($raw);
                        break;
                }
            }
            $cleaned[] = $row_cleaned;
        }
        return $cleaned;
    }
}

if (!function_exists('owbn_sanitize_ooc_location')) {
    function owbn_sanitize_ooc_location($group_data, $meta_fields)
    {
        $row_cleaned = [];
        if (!is_array($group_data)) return $row_cleaned;

        foreach ($meta_fields as $sub_key => $sub_meta) {
            if (!isset($group_data[$sub_key])) continue;
            $raw = $group_data[$sub_key];

            switch ($sub_meta['type']) {
                case 'wysiwyg':
                    $row_cleaned[$sub_key] = wp_kses_post($raw);
                    break;
                case 'multi_select':
                    $row_cleaned[$sub_key] = is_array($raw) ? array_map('sanitize_text_field', $raw) : [];
                    break;
                case 'email':
                    $row_cleaned[$sub_key] = sanitize_email($raw);
                    break;
                case 'boolean':
                    $row_cleaned[$sub_key] = ($raw === '1' || $raw === 1 || $raw === true) ? '1' : '0';
                    break;
                default:
                    $row_cleaned[$sub_key] = is_array($raw) ? array_map('sanitize_text_field', $raw) : sanitize_text_field($raw);
                    break;
            }
        }
        return $row_cleaned;
    }
}

if (!function_exists('owbn_sanitize_location_group')) {
    function owbn_sanitize_location_group($group_data, $meta_fields)
    {
        $cleaned = [];
        if (!is_array($group_data)) return $cleaned;

        foreach ($group_data as $row) {
            $row_cleaned = [];
            foreach ($meta_fields as $sub_key => $sub_meta) {
                if (!isset($row[$sub_key])) continue;
                $raw = $row[$sub_key];

                switch ($sub_meta['type']) {
                    case 'wysiwyg':
                        $row_cleaned[$sub_key] = wp_kses_post($raw);
                        break;
                    case 'multi_select':
                        $row_cleaned[$sub_key] = is_array($raw) ? array_map('sanitize_text_field', $raw) : [];
                        break;
                    case 'email':
                        $row_cleaned[$sub_key] = sanitize_email($raw);
                        break;
                    case 'boolean':
                        $row_cleaned[$sub_key] = ($raw === '1' || $raw === 1 || $raw === true) ? '1' : '0';
                        break;
                    default:
                        $row_cleaned[$sub_key] = is_array($raw) ? array_map('sanitize_text_field', $raw) : sanitize_text_field($raw);
                        break;
                }
            }
            $cleaned[] = $row_cleaned;
        }

        $allowed_keys = array_keys($meta_fields);
        foreach ($cleaned as &$row) {
            $row = array_intersect_key($row, array_flip($allowed_keys));
        }

        return $cleaned;
    }
}

if (!function_exists('owbn_sanitize_repeatable_group')) {
    function owbn_sanitize_repeatable_group($group_data, $meta_fields)
    {
        $cleaned = [];
        if (!is_array($group_data)) return $cleaned;

        foreach ($group_data as $row) {
            $row_cleaned = [];
            foreach ($meta_fields as $sub_key => $sub_meta) {
                if (!isset($row[$sub_key])) continue;
                $raw = $row[$sub_key];
                $row_cleaned[$sub_key] = is_array($raw) ? array_map('sanitize_text_field', $raw) : sanitize_text_field($raw);
            }
            $cleaned[] = $row_cleaned;
        }
        return $cleaned;
    }
}

/**
 * Sync entity slug fields with WordPress post_name.
 *
 * For any registered entity post type, checks field definitions for a 'slug'
 * type field. If found and the field is being set for the first time (empty
 * existing value), syncs $data['post_name'] with the slug value.
 *
 * @param array $data    An array of slashed, sanitized post data.
 * @param array $postarr An array of sanitized (and slashed) but otherwise unmodified post data.
 * @return array Modified post data.
 */
function owbn_sync_entity_slug_with_post_name($data, $postarr)
{
    $post_type = $data['post_type'] ?? '';

    $config = owbn_get_entity_config($post_type);
    if (!$config) return $data;

    $field_definitions_callable = $config['field_definitions'] ?? null;
    if (!is_callable($field_definitions_callable)) return $data;

    $definitions = call_user_func($field_definitions_callable);

    foreach ($definitions as $fields) {
        foreach ($fields as $key => $meta) {
            if ($meta['type'] === 'slug' && !empty($postarr[$key])) {
                // Only sync if slug doesn't already exist
                $post_id = $postarr['ID'] ?? 0;
                $existing = $post_id ? get_post_meta($post_id, $key, true) : '';
                if (empty($existing)) {
                    $data['post_name'] = sanitize_title($postarr[$key]);
                }
                break 2;
            }
        }
    }

    return $data;
}

/**
 * Diff old vs new field values and send change notification.
 *
 * @param int    $post_id     The post ID.
 * @param array  $config      Entity config.
 * @param array  $old_values  Old field values keyed by meta key.
 * @param array  $new_values  New field values keyed by meta key (if null, reads current meta).
 * @param bool   $pending     Whether this is a pending changeset.
 */
function owbn_send_change_notification( int $post_id, array $config, array $old_values, ?array $new_values = null, bool $pending = false ): void {
    if ( ! function_exists( 'owc_send_change_notification' ) ) {
        return;
    }

    $entity_type  = $config['singular'];
    $entity_key   = $config['entity_key'];
    $slug_key     = $config['slug_meta_key'];
    $slug         = get_post_meta( $post_id, $slug_key, true );
    $entity_title = get_the_title( $post_id );
    if ( empty( $entity_title ) || $entity_title === 'Auto Draft' ) {
        $entity_title = $slug ?: "#{$post_id}";
    }

    // Get field definitions for labels
    $field_defs_callable = $config['field_definitions'] ?? null;
    $definitions = is_callable( $field_defs_callable ) ? call_user_func( $field_defs_callable ) : [];

    // Build label lookup
    $labels = [];
    foreach ( $definitions as $fields ) {
        foreach ( $fields as $key => $meta ) {
            $labels[ $key ] = $meta['label'] ?? $key;
        }
    }

    $changes = [];
    $keys_to_check = array_keys( $old_values );

    // If new_values provided (pending changeset), use those
    // Otherwise read current meta for each key
    foreach ( $keys_to_check as $key ) {
        $old = $old_values[ $key ];
        $new = $new_values !== null ? ( $new_values[ $key ] ?? $old ) : get_post_meta( $post_id, $key, true );

        // Normalize for comparison — treat empty arrays, empty strings, and null as equivalent
        $old_empty = empty( $old ) || ( is_array( $old ) && count( array_filter( $old, function( $v ) { return ! empty( $v ); } ) ) === 0 );
        $new_empty = empty( $new ) || ( is_array( $new ) && count( array_filter( $new, function( $v ) { return ! empty( $v ); } ) ) === 0 );
        if ( $old_empty && $new_empty ) {
            continue;
        }
        $old_compare = is_array( $old ) ? wp_json_encode( $old ) : (string) $old;
        $new_compare = is_array( $new ) ? wp_json_encode( $new ) : (string) $new;

        if ( $old_compare !== $new_compare ) {
            $label = $labels[ $key ] ?? $key;
            $changes[ $label ] = [
                'before' => $old,
                'after'  => $new,
            ];
        }
    }

    if ( empty( $changes ) ) {
        return;
    }

    $current_user = wp_get_current_user();
    $changed_by   = $current_user->ID ? $current_user->display_name . ' (' . $current_user->user_email . ')' : 'Unknown';

    owc_send_change_notification( $entity_type, $entity_title, $slug, $changes, $changed_by, $post_id, $pending );
}

/**
 * Sync accessSchema roles when staff fields change.
 *
 * Compares old vs new user IDs in staff fields and calls
 * owc_asc_revoke_role() for removed users and owc_asc_grant_role() for added users.
 * Revokes fire immediately. Grants fire immediately for admin saves.
 *
 * @param int   $post_id    The post ID.
 * @param array $config     Entity config.
 * @param array $old_values Old field values (from before save).
 * @param array $new_values New field values (if null, reads current meta).
 * @param bool  $revoke_only If true, only process revokes (for non-admin pending saves).
 */
function owbn_sync_staff_roles( int $post_id, array $config, array $old_values, ?array $new_values = null, bool $revoke_only = false ): void {
    if ( ! function_exists( 'owc_asc_revoke_role' ) || ! function_exists( 'owc_asc_grant_role' ) ) {
        return;
    }

    $staff_role_map = $config['staff_role_map'] ?? [];
    if ( empty( $staff_role_map ) ) {
        return;
    }

    $slug_key = $config['slug_meta_key'];
    $slug     = get_post_meta( $post_id, $slug_key, true );
    if ( empty( $slug ) ) {
        return;
    }

    foreach ( $staff_role_map as $field_key => $role_pattern ) {
        $role_path = str_replace( '{slug}', $slug, $role_pattern );

        $old = $old_values[ $field_key ] ?? null;
        $new = $new_values !== null ? ( $new_values[ $field_key ] ?? $old ) : get_post_meta( $post_id, $field_key, true );

        $old_user_ids = owbn_extract_user_ids( $old );
        $new_user_ids = owbn_extract_user_ids( $new );

        // Revoke removed users (always immediate)
        $removed = array_diff( $old_user_ids, $new_user_ids );
        foreach ( $removed as $user_id ) {
            $user = get_user_by( 'id', $user_id );
            if ( $user && $user->user_email ) {
                $result = owc_asc_revoke_role( 'owbn-cc', $user->user_email, $role_path );
                if ( is_wp_error( $result ) ) {
                    error_log( "[OWBN CC] Role revoke failed for {$user->user_email} / {$role_path}: " . $result->get_error_message() );
                }
            }
        }

        // Grant added users (skip if revoke_only)
        if ( ! $revoke_only ) {
            $added = array_diff( $new_user_ids, $old_user_ids );
            foreach ( $added as $user_id ) {
                $user = get_user_by( 'id', $user_id );
                if ( $user && $user->user_email ) {
                    $result = owc_asc_grant_role( 'owbn-cc', $user->user_email, $role_path );
                    if ( is_wp_error( $result ) ) {
                        error_log( "[OWBN CC] Role grant failed for {$user->user_email} / {$role_path}: " . $result->get_error_message() );
                    }
                }
            }
        }
    }
}

/**
 * Extract user IDs from a staff field value.
 *
 * Handles both user_info (single user) and ast_group (list of users) field types.
 *
 * @param mixed $value The field value.
 * @return array Array of non-empty user ID strings.
 */
function owbn_extract_user_ids( $value ): array {
    if ( empty( $value ) || ! is_array( $value ) ) {
        return [];
    }

    // user_info type: { user: "123", display_name: "..." }
    if ( isset( $value['user'] ) ) {
        $uid = $value['user'];
        return ( ! empty( $uid ) && $uid !== '__new__' ) ? [ $uid ] : [];
    }

    // ast_group type: [ { user: "123", ... }, { user: "456", ... } ]
    if ( isset( $value[0] ) && is_array( $value[0] ) ) {
        $ids = [];
        foreach ( $value as $row ) {
            $uid = $row['user'] ?? '';
            if ( ! empty( $uid ) && $uid !== '__new__' ) {
                $ids[] = $uid;
            }
        }
        return $ids;
    }

    return [];
}

add_action('save_post', 'owbn_save_entity_meta', 10, 2);
add_filter('wp_insert_post_data', 'owbn_sync_entity_slug_with_post_name', 15, 2);
