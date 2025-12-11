<?php
/** File: includes/hooks/chronicle-validate.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.3.1
 * @author greghacke
 * Function: Chronicle validation functions
 */

if (!defined('ABSPATH')) exit;

/**
 * Validate Chronicle submission fields
 */
function owbn_validate_chronicle_submission($postarr)
{
    $definitions = owbn_get_chronicle_field_definitions();
    $post_id = $postarr['ID'] ?? 0;

    // Fields that are immutable once set - skip validation if DB has value
    $immutable_fields = ['chronicle_slug'];

    // Normalize all boolean checkbox fields to '0' if not set
    foreach ($definitions as $fields) {
        foreach ($fields as $key => $meta) {
            if ($meta['type'] === 'boolean' && !isset($postarr[$key])) {
                $postarr[$key] = '0';
            }
        }
    }

    $errors = [];

    foreach ($definitions as $fields) {
        foreach ($fields as $key => $meta) {
            
            // For immutable fields, check DB value if not in submission
            if (in_array($key, $immutable_fields, true) && $post_id) {
                $existing = get_post_meta($post_id, $key, true);
                if (!empty($existing)) {
                    // Field has value in DB, skip validation
                    continue;
                }
            }

            $raw = owbn_safe_post_value($key, $postarr);
            $raw_string = is_array($raw) ? '' : $raw;

            // Required field check
            if (!empty($meta['required']) && (is_array($raw) ? empty($raw) : trim($raw_string) === '')) {
                // Check if value exists in DB (for disabled fields)
                if ($post_id) {
                    $existing = get_post_meta($post_id, $key, true);
                    if (!empty($existing)) {
                        continue; // Has value in DB, not an error
                    }
                }
                $errors[] = $key;
                continue;
            }

            // Slug format validation
            if ($meta['type'] === 'slug' && !empty($raw_string)) {
                if (!preg_match('/^[a-z0-9]{2,8}$/', strtolower($raw_string))) {
                    $errors[] = $key;
                }
            }

            // User info validation
            if ($meta['type'] === 'user_info') {
                $user_info = is_array($raw) ? $raw : [];

                // If no data submitted, check DB
                if (empty($user_info) && $post_id) {
                    $user_info = get_post_meta($post_id, $key, true);
                    $user_info = is_array($user_info) ? $user_info : [];
                }

                $user = trim($user_info['user'] ?? '');
                $display_name = trim($user_info['display_name'] ?? '');
                $display_email = trim($user_info['display_email'] ?? '');

                $is_required = !empty($meta['required']);

                // Check conditional required
                if (!$is_required && isset($meta['conditional_required'])) {
                    [$dep_key, $dep_value] = explode('=', $meta['conditional_required']);
                    $actual = owbn_safe_post_value($dep_key, $postarr);
                    $actual = is_array($actual) ? '' : trim($actual);
                    $dep_value = trim($dep_value);

                    if ((string)$actual === $dep_value) {
                        $is_required = true;
                    }
                }

                if ($is_required && ($user === '' || $display_name === '' || $display_email === '')) {
                    $errors[] = $key;
                }
            }

            // Select validation
            if ($meta['type'] === 'select' && $raw === '--Select Option--') {
                $errors[] = $key;
            }
        }
    }

    return $errors;
}

/**
 * Force Chronicle posts to draft status if validation fails
 */
add_filter('wp_insert_post_data', 'owbn_force_draft_on_error', 10, 2);
function owbn_force_draft_on_error($data, $postarr)
{
    if ($data['post_type'] !== 'owbn_chronicle') {
        return $data;
    }

    // Allow trashing or deleting
    if (isset($data['post_status']) && in_array($data['post_status'], ['trash', 'auto-draft'], true)) {
        return $data;
    }

    // Skip validation if being deleted
    if (
        (isset($_POST['action']) && $_POST['action'] === 'delete') ||
        (isset($_POST['action2']) && $_POST['action2'] === 'delete')
    ) {
        return $data;
    }

    // Skip if dirty changes already flagged
    if (!empty($postarr['ID']) && get_transient("owbn_chronicle_dirty_notice_{$postarr['ID']}")) {
        return $data;
    }

    $errors = owbn_validate_chronicle_submission($postarr);
    if (!empty($errors)) {
        $data['post_status'] = 'draft';
        if (!empty($postarr['ID'])) {
            set_transient("owbn_chronicle_errors_{$postarr['ID']}", $errors, 60);
        }
    }

    return $data;
}