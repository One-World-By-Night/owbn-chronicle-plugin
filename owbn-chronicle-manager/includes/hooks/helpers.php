<?php
/** File: includes/hooks/helpers.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.3.0
 * @author greghacke
 * Function: Shared helper functions for save handlers and validation
 */

if (!defined('ABSPATH')) exit;

// ══════════════════════════════════════════════════════════════════════════════
// POST VALUE HELPERS
// ══════════════════════════════════════════════════════════════════════════════

if (!function_exists('owbn_safe_post_value')) {
    /**
     * Safe post value retrieval
     */
    function owbn_safe_post_value($key, $source = null)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $source = $source ?? $_POST;
        if (!isset($source[$key])) return '';
        return is_array($source[$key]) ? $source[$key] : stripslashes($source[$key]);
    }
}

if (!function_exists('owbn_users_changed')) {
    /**
     * Check if user assignments have changed
     */
    function owbn_users_changed($old, $new)
    {
        $old_users = array_filter(array_map(fn($u) => trim(strtolower((string)$u)), is_array($old) ? $old : []));
        $new_users = array_filter(array_map(fn($u) => trim(strtolower((string)$u)), is_array($new) ? $new : []));
        sort($old_users);
        sort($new_users);
        return $old_users !== $new_users;
    }
}

if (!function_exists('owbn_is_admin_user')) {
    /**
     * Check if user has admin/exec privileges
     */
    function owbn_is_admin_user($user = null)
    {
        if ($user === null) {
            $user = wp_get_current_user();
        } elseif (is_int($user)) {
            $user = get_userdata($user);
        }
        
        if (!$user instanceof WP_User) return false;
        
        return !empty(array_intersect($user->roles, ['administrator', 'exec_team', 'web_team']));
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// FIELD SANITIZATION HELPERS
// ══════════════════════════════════════════════════════════════════════════════

if (!function_exists('owbn_sanitize_user_info')) {
    /**
     * Sanitize user_info field data
     */
    function owbn_sanitize_user_info($info)
    {
        if (!is_array($info)) return [];
        
        return [
            'user'          => sanitize_text_field($info['user'] ?? ''),
            'display_name'  => sanitize_text_field($info['display_name'] ?? ''),
            'email'         => sanitize_email($info['email'] ?? ''),
            'actual_email'  => sanitize_email($info['actual_email'] ?? ''),
            'display_email' => sanitize_email($info['display_email'] ?? ''),
        ];
    }
}

if (!function_exists('owbn_sanitize_ast_group')) {
    /**
     * Sanitize ast_group (staff list) field data
     */
    function owbn_sanitize_ast_group($group_data, $meta_fields)
    {
        $cleaned = [];
        
        if (!is_array($group_data)) return $cleaned;
        
        foreach ($group_data as $index => $row) {
            if ($index === '__INDEX__') continue;
            if (empty($row['user']) && empty($row['display_name']) && empty($row['email']) && empty($row['role'])) {
                continue;
            }
            
            $row_cleaned = [];
            foreach ($meta_fields as $sub_key => $sub_meta) {
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
            
            $cleaned[] = $row_cleaned;
        }
        
        return $cleaned;
    }
}

if (!function_exists('owbn_sanitize_document_links')) {
    /**
     * Sanitize document_links_group field data with file upload handling
     */
    function owbn_sanitize_document_links($group_data, $post_id, $field_key, $existing_meta = null)
    {
        $cleaned = [];
        
        if (!is_array($group_data)) return $cleaned;
        
        foreach ($group_data as $index => $row) {
            if ($index === '__INDEX__') continue;
            
            $row_cleaned = [
                'title'        => isset($row['title']) ? sanitize_text_field($row['title']) : '',
                'link'         => isset($row['link']) ? esc_url_raw($row['link']) : '',
                'last_updated' => isset($row['last_updated']) ? sanitize_text_field($row['last_updated']) : '',
            ];
            
            // Handle uploaded file
            $file_field = "{$field_key}_{$index}_upload";
            if (!empty($_FILES[$file_field]) && !empty($_FILES[$file_field]['tmp_name'])) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';
                
                $attachment_id = media_handle_upload($file_field, $post_id);
                if (!is_wp_error($attachment_id)) {
                    $row_cleaned['file_id'] = $attachment_id;
                    if (empty($row_cleaned['last_updated'])) {
                        $row_cleaned['last_updated'] = current_time('Y-m-d');
                    }
                }
            } elseif (is_array($existing_meta) && isset($existing_meta[$index]['file_id'])) {
                $row_cleaned['file_id'] = $existing_meta[$index]['file_id'];
            }
            
            $cleaned[] = $row_cleaned;
        }
        
        return $cleaned;
    }
}

if (!function_exists('owbn_sanitize_email_lists')) {
    /**
     * Sanitize email_lists_group field data
     */
    function owbn_sanitize_email_lists($group_data)
    {
        $cleaned = [];
        
        if (!is_array($group_data)) return $cleaned;
        
        foreach ($group_data as $index => $row) {
            if ($index === '__INDEX__') continue;
            
            $row_cleaned = [
                'list_name'     => isset($row['list_name']) ? sanitize_text_field($row['list_name']) : '',
                'email_address' => isset($row['email_address']) ? sanitize_email($row['email_address']) : '',
                'description'   => isset($row['description']) ? wp_kses_post($row['description']) : '',
            ];
            
            if ($row_cleaned['list_name'] || $row_cleaned['email_address'] || $row_cleaned['description']) {
                $cleaned[] = $row_cleaned;
            }
        }
        
        return $cleaned;
    }
}

if (!function_exists('owbn_sanitize_player_lists')) {
    /**
     * Sanitize player_lists_group field data
     */
    function owbn_sanitize_player_lists($group_data)
    {
        $cleaned = [];
        
        if (!is_array($group_data)) return $cleaned;
        
        foreach ($group_data as $index => $row) {
            if ($index === '__INDEX__') continue;
            if (empty($row['list_name'])) continue;
            
            $cleaned[] = [
                'list_name'        => sanitize_text_field($row['list_name'] ?? ''),
                'access'           => sanitize_text_field($row['access'] ?? 'Public'),
                'address'          => sanitize_email($row['address'] ?? ''),
                'ic_ooc'           => sanitize_text_field($row['ic_ooc'] ?? 'OOC'),
                'moderate_address' => sanitize_email($row['moderate_address'] ?? ''),
                'signup_url'       => esc_url_raw($row['signup_url'] ?? ''),
            ];
        }
        
        return $cleaned;
    }
}

if (!function_exists('owbn_sanitize_social_links')) {
    /**
     * Sanitize social_links_group field data
     */
    function owbn_sanitize_social_links($group_data)
    {
        $cleaned = [];
        
        if (!is_array($group_data)) return $cleaned;
        
        foreach ($group_data as $index => $row) {
            if ($index === '__INDEX__' || (empty($row['platform']) && empty($row['url']))) {
                continue;
            }
            
            $cleaned[] = [
                'platform' => isset($row['platform']) ? sanitize_text_field($row['platform']) : '',
                'url'      => isset($row['url']) ? esc_url_raw($row['url']) : '',
            ];
        }
        
        return $cleaned;
    }
}