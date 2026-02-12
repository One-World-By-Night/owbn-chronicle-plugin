<?php
/**
 * Generic Entity Save Handler
 *
 * Provides a single save_post handler for all registered entity types,
 * driven by entity config from the registry. Replaces per-entity save files.
 *
 * @package OWBN Chronicle Manager
 * @since 2.0.0
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

    // Get field definitions by calling the config's callable
    $field_definitions_callable = $config['field_definitions'] ?? null;
    if (!is_callable($field_definitions_callable)) return;

    $definitions = call_user_func($field_definitions_callable);

    // Get immutable and restricted fields from config
    $immutable_fields  = $config['immutable_fields'] ?? [];
    $restricted_fields = $config['restricted_fields'] ?? [];

    $is_admin = owbn_is_admin_user();
    $staff_user_dirty = false;

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
            $staff_user_dirty = owbn_save_entity_field($post_id, $key, $meta, $raw, $staff_user_dirty);
        }
    }

    // Handle staff user changes if any user fields were modified
    if ($staff_user_dirty) {
        owbn_handle_entity_staff_change($post_id, $config);
    }
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

    // If not admin or self-promoted, set post to draft and flag for notice
    if (!$is_allowed || $self_promoted) {
        $post = get_post($post_id);
        if ($post->post_status !== 'draft') {
            wp_update_post([
                'ID'          => $post_id,
                'post_status' => 'draft',
            ]);
            set_transient("owbn_{$entity_key}_dirty_notice_{$post_id}", true, 60);
        }
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// GROUP SANITIZERS (copied from chronicle-save.php, not in helpers.php)
// ══════════════════════════════════════════════════════════════════════════════

if (!function_exists('owbn_sanitize_session_group')) {
    /**
     * Sanitize session_group field data.
     *
     * @param mixed $group_data The raw group data from POST.
     * @param array $meta_fields The sub-field definitions.
     * @return array Sanitized rows.
     */
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
    /**
     * Sanitize ooc_location field data (single row, not repeatable).
     *
     * @param mixed $group_data The raw group data from POST.
     * @param array $meta_fields The sub-field definitions.
     * @return array Sanitized row.
     */
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
    /**
     * Sanitize location_group field data (repeatable rows).
     *
     * @param mixed $group_data The raw group data from POST.
     * @param array $meta_fields The sub-field definitions.
     * @return array Sanitized rows with keys filtered to allowed fields.
     */
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
    /**
     * Sanitize repeatable_group field data.
     *
     * @param mixed $group_data The raw group data from POST.
     * @param array $meta_fields The sub-field definitions.
     * @return array Sanitized rows.
     */
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

// ══════════════════════════════════════════════════════════════════════════════
// SLUG SYNC FILTER
// ══════════════════════════════════════════════════════════════════════════════

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

// ══════════════════════════════════════════════════════════════════════════════
// HOOKS
// ══════════════════════════════════════════════════════════════════════════════

add_action('save_post', 'owbn_save_entity_meta', 10, 2);
add_filter('wp_insert_post_data', 'owbn_sync_entity_slug_with_post_name', 15, 2);
