<?php
/** File: includes/hooks/chronicle-save.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.3.1
 * @author greghacke
 * Function: Chronicle post meta save handler
 */

if (!defined('ABSPATH')) exit;

add_action('save_post', 'owbn_save_chronicle_meta');

/**
 * Save Chronicle meta fields
 */
function owbn_save_chronicle_meta($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (get_post_type($post_id) !== 'owbn_chronicle') return;
    if (
        !isset($_POST['owbn_chronicle_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['owbn_chronicle_nonce'])), 'owbn_chronicle_save')
    ) {
        return;
    }

    $staff_user_dirty = false;
    $definitions = owbn_get_chronicle_field_definitions();

    // Fields that can NEVER be changed after initial creation
    $immutable_fields = ['chronicle_slug'];

    // Fields that only admin/exec can modify
    $restricted_fields = [
        'chronicle_slug',
        'chronicle_start_date',
        'chronicle_region',
        'chronicle_probationary',
        'chronicle_satellite',
        'chronicle_parent',
    ];

    $is_admin = owbn_is_admin_user();

    foreach ($definitions as $fields) {
        foreach ($fields as $key => $meta) {

            // IMMUTABLE: Never change once set
            if (in_array($key, $immutable_fields, true)) {
                $existing = get_post_meta($post_id, $key, true);
                if (!empty($existing)) {
                    continue;
                }
            }

            // RESTRICTED: Skip if field wasn't submitted (was disabled)
            if (in_array($key, $restricted_fields, true) && !$is_admin) {
                if (!isset($_POST[$key])) {
                    continue;
                }
            }

            $raw = owbn_safe_post_value($key);
            $staff_user_dirty = owbn_save_chronicle_field($post_id, $key, $meta, $raw, $staff_user_dirty);
        }
    }

    // Handle staff user changes
    if ($staff_user_dirty) {
        owbn_handle_chronicle_staff_change($post_id);
    }
}

/**
 * Save individual chronicle field by type
 */
function owbn_save_chronicle_field($post_id, $key, $meta, $raw, $staff_user_dirty)
{
    switch ($meta['type']) {
        case 'slug':
            update_post_meta($post_id, $key, strtolower(sanitize_text_field($raw)));
            break;

        case 'select':
            update_post_meta($post_id, $key, sanitize_text_field($raw));
            break;

        case 'multi_select':
            $value = isset($_POST[$key]) && is_array($_POST[$key]) 
                ? array_map('sanitize_text_field', wp_unslash($_POST[$key])) 
                : [];
            update_post_meta($post_id, $key, $value);
            break;

        case 'ast_group':
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
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_session_group($group_data, $meta['fields']);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'ooc_location':
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_ooc_location($group_data, $meta['fields']);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'location_group':
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_location_group($group_data, $meta['fields']);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'repeatable_group':
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_repeatable_group($group_data, $meta['fields']);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'document_links_group':
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $existing = get_post_meta($post_id, $key, true);
            $cleaned = owbn_sanitize_document_links($group_data, $post_id, $key, $existing);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'social_links_group':
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_social_links($group_data);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'email_lists_group':
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_email_lists($group_data);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'player_lists_group':
            $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_player_lists($group_data);
            update_post_meta($post_id, $key, $cleaned);
            break;

        case 'user_info':
            $info = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
            $cleaned = owbn_sanitize_user_info($info);
            
            $previous = get_post_meta($post_id, $key, true);
            $previous_user = $previous['user'] ?? '';
            
            // Only flag dirty if user actually changed to a different value
            // Ignore if cleaned user is empty (no change submitted)
            if (!empty($cleaned['user']) && $cleaned['user'] !== '__new__') {
                if (owbn_users_changed([$previous_user], [$cleaned['user']])) {
                    $staff_user_dirty = true;
                }
            } elseif ($cleaned['user'] === '__new__') {
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
 * Handle staff user change - draft post if needed
 */
function owbn_handle_chronicle_staff_change($post_id)
{
    $current_user = wp_get_current_user();
    $current_user_id = (string) $current_user->ID;
    $is_allowed = owbn_is_admin_user($current_user);

    // Enforce CM/Parent Chronicle exclusivity
    $is_satellite = get_post_meta($post_id, 'chronicle_satellite', true) === '1';
    if ($is_satellite) {
        delete_post_meta($post_id, 'cm_info');
    } else {
        delete_post_meta($post_id, 'chronicle_parent');
    }

    // Check for self-promotion
    $hst_info = get_post_meta($post_id, 'hst_info', true);
    $cm_info = get_post_meta($post_id, 'cm_info', true);
    $self_promoted = false;

    if ((is_array($hst_info) && isset($hst_info['user']) && $hst_info['user'] === $current_user_id) ||
        (is_array($cm_info) && isset($cm_info['user']) && $cm_info['user'] === $current_user_id)
    ) {
        $self_promoted = true;
    }

    if (!$is_allowed || $self_promoted) {
        $post = get_post($post_id);
        if ($post->post_status !== 'draft') {
            wp_update_post([
                'ID' => $post_id,
                'post_status' => 'draft',
            ]);
            set_transient("owbn_chronicle_dirty_notice_{$post_id}", true, 60);
        }
    }
}

/**
 * Sanitize session_group field
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

/**
 * Sanitize ooc_location field
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

/**
 * Sanitize location_group field
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

/**
 * Sanitize repeatable_group field
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

// Sync custom slug field with post_name
add_filter('wp_insert_post_data', 'owbn_sync_custom_slug_with_post_name', 5, 2);
function owbn_sync_custom_slug_with_post_name($data, $postarr)
{
    if ($data['post_type'] !== 'owbn_chronicle') {
        return $data;
    }

    $definitions = owbn_get_chronicle_field_definitions();
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