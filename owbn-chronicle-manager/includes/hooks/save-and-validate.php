<?php
if (!defined('ABSPATH')) exit;

// Save the Chronicle meta fields
function owbn_save_chronicle_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (get_post_type($post_id) !== 'owbn_chronicle') return;
    $staff_user_dirty = false;

    // $user_id = get_current_user_id();
    // if (!owbn_user_can_edit_chronicle($user_id, $post_id)) return;

    $definitions = owbn_get_chronicle_field_definitions();
    $errors = get_transient("owbn_chronicle_errors_{$post_id}") ?: [];

    foreach ($definitions as $fields) {
        foreach ($fields as $key => $meta) {
            if (
                !isset($_POST['owbn_chronicle_nonce']) || 
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['owbn_chronicle_nonce'])), 'owbn_chronicle_save')
            )
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

            $raw = owbn_safe_post_value($key);

            switch ($meta['type']) {
                case 'slug':
                    $sanitized = strtolower(sanitize_text_field($raw));
                    update_post_meta($post_id, $key, $sanitized);
                    break;

                case 'select':
                    update_post_meta($post_id, $key, sanitize_text_field($raw));
                    break;

                case 'multi_select':
                    $value = isset($_POST[$key]) && is_array($_POST[$key]) ? array_map('sanitize_text_field', wp_unslash($_POST[$key])) : [];
                    update_post_meta($post_id, $key, $value);
                    break;

                case 'ast_group':
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
                    $cleaned = [];

                    $previous = get_post_meta($post_id, $key, true);
                    $previous_users = is_array($previous) ? array_column($previous, 'user') : [];

                    if (is_array($group_data)) {
                        foreach ($group_data as $index => $row) {
                            // Skip template or completely empty rows
                            if (
                                $index === '__INDEX__' ||
                                (empty($row['user']) && empty($row['display_name']) &&
                                empty($row['email']) && empty($row['role']))
                            ) {
                                continue;
                            }

                            $row_cleaned = [];
                            foreach ($meta['fields'] as $sub_key => $sub_meta) {
                                if (!isset($row[$sub_key])) continue;
                                $raw = $row[$sub_key];

                                if (in_array($sub_key, ['email', 'actual_email', 'display_email'], true)) {
                                    $row_cleaned[$sub_key] = sanitize_email($raw);
                                } else {
                                    $row_cleaned[$sub_key] = is_array($raw)
                                        ? array_map('sanitize_text_field', $raw)
                                        : sanitize_text_field($raw);
                                }
                            }

                            // 🔸 Detect [New User] case here
                            if (!empty($row_cleaned['user']) && $row_cleaned['user'] === '__new__') {
                                $staff_user_dirty = true; // flag as needing admin review
                            }

                            $cleaned[] = $row_cleaned;
                        }
                    }

                    $new_users = array_column($cleaned, 'user');

                    if (owbn_users_changed($previous_users, $new_users)) {
                        $staff_user_dirty = true;
                    }

                    update_post_meta($post_id, $key, $cleaned);
                    break;

                case 'session_group':
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
                    $cleaned = [];

                    if (is_array($group_data)) {
                        foreach ($group_data as $row) {
                            $row_cleaned = [];
                            foreach ($meta['fields'] as $sub_key => $sub_meta) {
                                if (!isset($row[$sub_key])) continue;
                                $raw = $row[$sub_key];

                                switch ($sub_meta['type']) {
                                    case 'wysiwyg':
                                        $row_cleaned[$sub_key] = wp_kses_post($raw);
                                        break;

                                    case 'multi_select':
                                        $row_cleaned[$sub_key] = is_array($raw)
                                            ? array_map('sanitize_text_field', $raw)
                                            : [];
                                        break;

                                    case 'email':
                                        $row_cleaned[$sub_key] = sanitize_email($raw);
                                        break;

                                    case 'time':
                                    case 'select':
                                    default:
                                        $row_cleaned[$sub_key] = is_array($raw)
                                            ? array_map('sanitize_text_field', $raw)
                                            : sanitize_text_field($raw);
                                        break;
                                }
                            }
                            $cleaned[] = $row_cleaned;
                        }
                    }

                    update_post_meta($post_id, $key, $cleaned);
                    break;

                case 'ooc_location':
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
                    $row_cleaned = [];

                    if (is_array($group_data)) {
                        foreach ($meta['fields'] as $sub_key => $sub_meta) {
                            if (!isset($group_data[$sub_key])) continue;
                            $raw = $group_data[$sub_key];

                            switch ($sub_meta['type']) {
                                case 'wysiwyg':
                                    $row_cleaned[$sub_key] = wp_kses_post($raw);
                                    break;

                                case 'multi_select':
                                    $row_cleaned[$sub_key] = is_array($raw)
                                        ? array_map('sanitize_text_field', $raw)
                                        : [];
                                    break;

                                case 'email':
                                    $row_cleaned[$sub_key] = sanitize_email($raw);
                                    break;

                                case 'boolean':
                                    $row_cleaned[$sub_key] = ($raw === '1' || $raw === 1 || $raw === true) ? '1' : '0';
                                    break;

                                default:
                                    $row_cleaned[$sub_key] = is_array($raw)
                                        ? array_map('sanitize_text_field', $raw)
                                        : sanitize_text_field($raw);
                                    break;
                            }
                        }
                    }

                    update_post_meta($post_id, $key, $row_cleaned);
                    break;

                case 'location_group':
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
                    $cleaned = [];

                    if (is_array($group_data)) {
                        foreach ($group_data as $row) {
                            $row_cleaned = [];
                            foreach ($meta['fields'] as $sub_key => $sub_meta) {
                                if (!isset($row[$sub_key])) continue;
                                $raw = $row[$sub_key];

                                switch ($sub_meta['type']) {
                                    case 'wysiwyg':
                                        $row_cleaned[$sub_key] = wp_kses_post($raw);
                                        break;

                                    case 'multi_select':
                                        $row_cleaned[$sub_key] = is_array($raw)
                                            ? array_map('sanitize_text_field', $raw)
                                            : [];
                                        break;

                                    case 'email':
                                        $row_cleaned[$sub_key] = sanitize_email($raw);
                                        break;

                                    case 'boolean':
                                        $row_cleaned[$sub_key] = ($raw === '1' || $raw === 1 || $raw === true) ? '1' : '0';
                                        break;

                                    default:
                                        $row_cleaned[$sub_key] = is_array($raw)
                                            ? array_map('sanitize_text_field', $raw)
                                            : sanitize_text_field($raw);
                                        break;
                                }
                            }
                            $cleaned[] = $row_cleaned;
                        }
                    }
                    $allowed_keys = array_keys($meta['fields']);
                    foreach ($cleaned as &$row) {
                        $row = array_intersect_key($row, array_flip($allowed_keys));
                    }

                    update_post_meta($post_id, $key, $cleaned);
                    break;

                case 'repeatable_group':
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
                    $cleaned = [];
                    if (is_array($group_data)) {
                        foreach ($group_data as $row) {
                            $row_cleaned = [];
                            foreach ($meta['fields'] as $sub_key => $sub_meta) {
                                if (!isset($row[$sub_key])) continue;
                                $raw = $row[$sub_key];
                                if (is_array($raw)) {
                                    $row_cleaned[$sub_key] = array_map('sanitize_text_field', $raw);
                                } else {
                                    $row_cleaned[$sub_key] = sanitize_text_field($raw);
                                }
                            }
                            $cleaned[] = $row_cleaned;
                        }
                    }
                    update_post_meta($post_id, $key, $cleaned);
                    break;

                case 'document_links_group':
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
                    $cleaned = [];

                    if (is_array($group_data)) {
                        foreach ($group_data as $index => $row) {
                            $row_cleaned = [];

                            // Sanitize title and link
                            $row_cleaned['title'] = isset($row['title']) ? sanitize_text_field($row['title']) : '';
                            $row_cleaned['link']  = isset($row['link']) ? esc_url_raw($row['link']) : '';

                            // Handle uploaded file
                            $file_field = "{$key}_{$index}_upload";
                            if (!empty($_FILES[$file_field]) && !empty($_FILES[$file_field]['tmp_name'])) {
                                require_once ABSPATH . 'wp-admin/includes/file.php';
                                require_once ABSPATH . 'wp-admin/includes/media.php';
                                require_once ABSPATH . 'wp-admin/includes/image.php';

                                $attachment_id = media_handle_upload($file_field, $post_id);
                                if (!is_wp_error($attachment_id)) {
                                    $row_cleaned['file_id'] = $attachment_id;
                                }
                            } else {
                                // Preserve existing file_id if already saved
                                $existing_meta = get_post_meta($post_id, $key, true);
                                $existing_file_id = $existing_meta[$index]['file_id'] ?? '';
                                if ($existing_file_id) {
                                    $row_cleaned['file_id'] = $existing_file_id;
                                }
                            }

                            $cleaned[] = $row_cleaned;
                        }
                    }

                    update_post_meta($post_id, $key, $cleaned);
                    break;

                case 'social_links_group':
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
                    $cleaned = [];

                    if (is_array($group_data)) {
                        foreach ($group_data as $index => $row) {
                            // Skip template or fully empty rows
                            if (
                                $index === '__INDEX__' ||
                                (empty($row['platform']) && empty($row['url']))
                            ) {
                                continue;
                            }

                            $platform = isset($row['platform']) ? sanitize_text_field($row['platform']) : '';
                            $url = isset($row['url']) ? esc_url_raw($row['url']) : '';

                            $cleaned[] = [
                                'platform' => $platform,
                                'url'      => $url,
                            ];
                        }
                    }

                    update_post_meta($post_id, $key, $cleaned);
                    break;

                case 'email_lists_group':
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $group_data = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
                    $cleaned = [];

                    if (is_array($group_data)) {
                        foreach ($group_data as $row) {
                            $row_cleaned = [];

                            $row_cleaned['list_name'] = isset($row['list_name']) ? sanitize_text_field($row['list_name']) : '';
                            $row_cleaned['email_address'] = isset($row['email_address']) ? sanitize_email($row['email_address']) : '';
                            $row_cleaned['description'] = isset($row['description']) ? wp_kses_post($row['description']) : '';

                            if ($row_cleaned['list_name'] || $row_cleaned['email_address'] || $row_cleaned['description']) {
                                $cleaned[] = $row_cleaned;
                            }
                        }
                    }

                    update_post_meta($post_id, $key, $cleaned);
                    break;

                case 'user_info':
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $info = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];

                    $user_value = isset($info['user']) ? sanitize_text_field($info['user']) : '';
                    $cleaned = [
                        'user' => $user_value,
                        'display_name' => sanitize_text_field($info['display_name'] ?? ''),
                        'email' => sanitize_email($info['email'] ?? ''),
                        'actual_email' => sanitize_email($info['actual_email'] ?? ''),
                        'display_email' => sanitize_email($info['display_email'] ?? ''),
                    ];

                    $previous = get_post_meta($post_id, $key, true);
                    $previous_user = $previous['user'] ?? '';

                    if (owbn_users_changed([$previous_user], [$user_value])) {
                        $staff_user_dirty = true;
                    }

                    if ($user_value === '__new__') {
                        $staff_user_dirty = true; // mark draft
                    }

                    update_post_meta($post_id, $key, $cleaned);
                    break;
                    
                case 'chronicle_select':
                    update_post_meta($post_id, $key, sanitize_text_field($raw));
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
        }
    }
    // After all post_meta fields processed
    if ($staff_user_dirty) {
        $current_user = wp_get_current_user();
        $current_user_id = (string) $current_user->ID;
        $user_roles = (array) $current_user->roles;
        $allowed_roles = ['administrator', 'exec_team', 'web_team'];
        $is_allowed = array_intersect($allowed_roles, $user_roles);

        // Enforce CM/Parent Chronicle exclusivity
        $is_satellite = get_post_meta($post_id, 'chronicle_satellite', true) === '1';

        if ($is_satellite) {
            // Remove CM info if it's a satellite
            delete_post_meta($post_id, 'cm_info');
        } else {
            // Remove parent_chronicle if it's not a satellite
            delete_post_meta($post_id, 'chronicle_parent');
        }

        // Check for self-promotion
        $hst = get_post_meta($post_id, 'hst', true);
        $cm_info = get_post_meta($post_id, 'cm_info', true);
        $self_promoted = false;

        if ((is_array($hst) && isset($hst['user']) && $hst['user'] === $current_user_id) ||
            (is_array($cm_info) && isset($cm_info['user']) && $cm_info['user'] === $current_user_id)) {
            $self_promoted = true;
        }

        if (empty($is_allowed) || $self_promoted) {
            // Only change status if not already a draft
            $post = get_post($post_id);
            if ($post->post_status !== 'draft') {
                // Draft the post
                wp_update_post([
                    'ID' => $post_id,
                    'post_status' => 'draft',
                ]);
                // Set a flag to show the admin notice
                set_transient("owbn_chronicle_dirty_notice_{$post_id}", true, 60);
            }
        }
    }
}
add_action('save_post', 'owbn_save_chronicle_meta');

// Safe post value retrieval
function owbn_safe_post_value($key, $source = null) {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $source = $source ?? $_POST;
    if (!isset($source[$key])) return '';
    return is_array($source[$key]) ? $source[$key] : stripslashes($source[$key]);
}

// Validate Chronicle submission fields
function owbn_validate_chronicle_submission($postarr) {
    $definitions = owbn_get_chronicle_field_definitions();
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
            $raw = owbn_safe_post_value($key, $postarr);
            $raw_string = is_array($raw) ? '' : $raw;

            if (!empty($meta['required']) && (is_array($raw) ? empty($raw) : trim($raw_string) === '')) {
                $errors[] = $key;
                continue;
            }

            if ($meta['type'] === 'slug') {
                if (!preg_match('/^[a-z0-9]{2,6}$/', strtolower($raw_string))) {
                    $errors[] = $key;
                }
            }

            if ($meta['type'] === 'user_info') {
                $user_info = is_array($raw) ? $raw : [];

                $user = trim($user_info['user'] ?? '');
                $display_name = trim($user_info['display_name'] ?? '');
                $display_email = trim($user_info['display_email'] ?? '');

                $is_required = !empty($meta['required']);

                if (!$is_required && isset($meta['conditional_required'])) {
                    [$dep_key, $dep_value] = explode('=', $meta['conditional_required']);
                    $actual = owbn_safe_post_value($dep_key, $postarr);

                    // Normalize checkbox value
                    $actual = is_array($actual) ? '' : trim($actual);
                    $dep_value = trim($dep_value);

                    if ((string)$actual === $dep_value) {
                        $is_required = true;
                    }
                }

                if (
                    $is_required &&
                    ($user === '' || $display_name === '' || $display_email === '')
                ) {
                    $errors[] = $key;
                }
            }

            if ($meta['type'] === 'select' && $raw === '--Select Option--') {
                $errors[] = $key;
            }
        }
    }
    // error_log("CM REQUIRED CHECK — Sat value: " . print_r(owbn_safe_post_value('chronicle_satellite', $postarr), true));
    // error_log("CM REQUIRED? " . (in_array('cm_info', $errors, true) ? 'YES' : 'NO'));
    return $errors;
}

// Display admin notice for invalid fields
function owbn_admin_notice_invalid_fields() {
    global $post;
    $post_id = 0;

    if (isset($post->ID)) {
        $post_id = $post->ID; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    } elseif (!empty($_GET['post'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_id = intval($_GET['post']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    } elseif (!empty($_POST['post_ID'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $post_id = intval($_POST['post_ID']); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }

    if (!$post_id) return;

    $errors = get_transient("owbn_chronicle_errors_{$post_id}");
    if ($errors) {
        $definitions = owbn_get_chronicle_field_definitions();
        $labels = [];

        foreach ($errors as $error_key) {
            foreach ($definitions as $section => $fields) {
                if (isset($fields[$error_key])) {
                    $labels[] = $fields[$error_key]['label'];
                }
            }
        }

        if (!empty($labels)) {
            echo '<div class="notice notice-error owbn-error-notice"><p><strong>' . esc_html__('Please fix the following required fields:', 'owbn-chronicle-manager') . '</strong></p>';
            echo '<ul>';
            foreach ($labels as $label) {
                echo '<li>' . esc_html($label) . '</li>';
            }
            echo '</ul></div>';
        }

        delete_transient("owbn_chronicle_errors_{$post_id}");
    }
}
add_action('admin_notices', 'owbn_admin_notice_invalid_fields');

// Force Chronicle posts to draft status if validation fails
function owbn_force_draft_on_error($data, $postarr) {
    // Only apply to our custom post type
    if ($data['post_type'] !== 'owbn_chronicle') {
        return $data;
    }

    // Allow trashing or deleting
    if (isset($data['post_status']) && in_array($data['post_status'], ['trash', 'auto-draft'], true)) {
        return $data;
    }

    // Skip validation if the post is being trashed or deleted
    if (
        (isset($_POST['action']) && $_POST['action'] === 'delete') || // phpcs:ignore WordPress.Security.NonceVerification.Missing
        (isset($_POST['action2']) && $_POST['action2'] === 'delete')  // phpcs:ignore WordPress.Security.NonceVerification.Missing
    ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return $data;
    }

    // Skip validation if dirty changes already flagged
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
add_filter('wp_insert_post_data', 'owbn_force_draft_on_error', 10, 2);

function owbn_users_changed($old, $new) {
    $old_users = array_filter(array_map(fn($u) => trim(strtolower((string)$u)), is_array($old) ? $old : []));
    $new_users = array_filter(array_map(fn($u) => trim(strtolower((string)$u)), is_array($new) ? $new : []));
    sort($old_users);
    sort($new_users);
    return $old_users !== $new_users;
}

function owbn_admin_notice_dirty_user_change() {
    global $post;
    $post_id = 0;

    if (isset($post->ID)) {
        $post_id = $post->ID; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    } elseif (!empty($_GET['post'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_id = intval($_GET['post']);
    }

    if (!$post_id) return;

    if (get_transient("owbn_chronicle_dirty_notice_{$post_id}")) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo "<p>\n<strong>\n<span class='dashicons dashicons-lock'></span> Chronicle Staff changes require authentication from Exec or Web Teams.<br>\nUpon validation, this change will be published.\n</strong>\n</p>\n";
        echo '</div>';
        delete_transient("owbn_chronicle_dirty_notice_{$post_id}");
    }
}
add_action('admin_notices', 'owbn_admin_notice_dirty_user_change');

// Sync custom slug field with post_name
function owbn_sync_custom_slug_with_post_name($data, $postarr) {
    if ($data['post_type'] !== 'owbn_chronicle') {
        return $data;
    }

    $definitions = owbn_get_chronicle_field_definitions();
    foreach ($definitions as $fields) {
        foreach ($fields as $key => $meta) {
            if ($meta['type'] === 'slug' && !empty($postarr[$key])) {
                $data['post_name'] = sanitize_title($postarr[$key]);
                break 2;
            }
        }
    }

    return $data;
}
add_filter('wp_insert_post_data', 'owbn_sync_custom_slug_with_post_name', 5, 2); // Priority 5 so it runs BEFORE validation at 10