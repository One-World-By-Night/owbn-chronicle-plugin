<?php
/**
 * Entity Type Initialization
 *
 * Generic CPT registration, meta registration, metabox setup, permission
 * mapping, permalinks, rewrites, and template loading -- all driven by
 * entity config arrays from the registry.
 *
 * @package OWBN Chronicle Manager
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ══════════════════════════════════════════════════════════════════════════════
// CPT REGISTRATION
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Register a WordPress Custom Post Type from an entity config.
 *
 * @param array $config Entity type configuration array.
 */
function owbn_register_entity_cpt(array $config): void
{
    if (!owbn_is_entity_enabled($config['post_type'])) return;

    $singular = $config['singular'];
    $plural   = $config['plural'];
    $lc_singular = strtolower($singular);
    $lc_plural   = strtolower($plural);

    $menu_name = $config['menu_name'] ?? $plural;

    $labels = [
        'name'                     => _x($plural, 'Post type general name', 'owbn-chronicle-manager'),
        'singular_name'            => _x($singular, 'Post type singular name', 'owbn-chronicle-manager'),
        'menu_name'                => _x($menu_name, 'Admin Menu text', 'owbn-chronicle-manager'),
        'name_admin_bar'           => _x($singular, 'Add New on Toolbar', 'owbn-chronicle-manager'),
        'add_new'                  => _x('Add New', $singular, 'owbn-chronicle-manager'),
        'add_new_item'             => __("Add New {$singular}", 'owbn-chronicle-manager'),
        'new_item'                 => __("New {$singular}", 'owbn-chronicle-manager'),
        'edit_item'                => __("Edit {$singular}", 'owbn-chronicle-manager'),
        'view_item'                => __("View {$singular}", 'owbn-chronicle-manager'),
        'view_items'               => __("View {$plural}", 'owbn-chronicle-manager'),
        'all_items'                => __("All {$plural}", 'owbn-chronicle-manager'),
        'search_items'             => __("Search {$plural}", 'owbn-chronicle-manager'),
        'parent_item_colon'        => __("Parent {$plural}:", 'owbn-chronicle-manager'),
        'not_found'                => __("No {$lc_plural} found.", 'owbn-chronicle-manager'),
        'not_found_in_trash'       => __("No {$lc_plural} found in Trash.", 'owbn-chronicle-manager'),
        'archives'                 => __("{$singular} Archives", 'owbn-chronicle-manager'),
        'insert_into_item'         => __("Insert into {$lc_singular}", 'owbn-chronicle-manager'),
        'uploaded_to_this_item'    => __("Uploaded to this {$lc_singular}", 'owbn-chronicle-manager'),
        'filter_items_list'        => __("Filter {$lc_plural} list", 'owbn-chronicle-manager'),
        'items_list_navigation'    => __("{$plural} list navigation", 'owbn-chronicle-manager'),
        'items_list'               => __("{$plural} list", 'owbn-chronicle-manager'),
    ];

    // Build the full capabilities map from the config's base capabilities.
    $base_caps = $config['capabilities'] ?? [];
    $capabilities = array_merge([
        'edit_post'              => $base_caps['edit_post'] ?? "edit_{$config['post_type']}",
        'read_post'              => $base_caps['read_post'] ?? "read_{$config['post_type']}",
        'delete_post'            => $base_caps['delete_post'] ?? "delete_{$config['post_type']}",
        'edit_posts'             => $base_caps['edit_posts'] ?? 'ocm_view_list',
        'edit_others_posts'      => $base_caps['edit_post'] ?? "edit_{$config['post_type']}",
        'publish_posts'          => $base_caps['publish_posts'] ?? "publish_{$config['post_type']}",
        'read_private_posts'     => $base_caps['read_post'] ?? "read_{$config['post_type']}",
        'delete_posts'           => $base_caps['delete_post'] ?? "delete_{$config['post_type']}",
        'delete_others_posts'    => $base_caps['delete_post'] ?? "delete_{$config['post_type']}",
        'delete_published_posts' => $base_caps['delete_post'] ?? "delete_{$config['post_type']}",
        'delete_private_posts'   => $base_caps['delete_post'] ?? "delete_{$config['post_type']}",
        'edit_published_posts'   => $base_caps['edit_post'] ?? "edit_{$config['post_type']}",
        'edit_private_posts'     => $base_caps['edit_post'] ?? "edit_{$config['post_type']}",
    ], $base_caps);

    $cpt_args = [
        'labels'          => $labels,
        'public'          => true,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'has_archive'     => false,
        'rewrite'         => ['slug' => $config['url_slug'], 'with_front' => false],
        'supports'        => ['title', 'editor', 'author', 'revisions'],
        'show_in_rest'    => true,
        'menu_icon'       => $config['menu_icon'] ?? 'dashicons-admin-generic',
        'capability_type' => $config['post_type'],
        'map_meta_cap'    => true,
        'capabilities'    => $capabilities,
    ];

    if (isset($config['menu_position'])) {
        $cpt_args['menu_position'] = (int) $config['menu_position'];
    }

    register_post_type($config['post_type'], $cpt_args);
}

// ══════════════════════════════════════════════════════════════════════════════
// META REGISTRATION
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Register post meta for an entity type based on its field definitions.
 *
 * Complex field types (arrays) are registered with type 'array'; all others
 * are registered as 'string'.
 *
 * @param array $config Entity type configuration array.
 */
function owbn_register_entity_meta(array $config): void
{
    if (!owbn_is_entity_enabled($config['post_type'])) return;

    $callable = $config['field_definitions'] ?? null;
    if (!$callable || !is_callable($callable)) return;

    $field_groups = call_user_func($callable);
    if (!is_array($field_groups)) return;

    // Field types that store array/object data.
    $complex_types = [
        'ooc_location',
        'location_group',
        'session_group',
        'ast_group',
        'repeatable_group',
        'document_links_group',
        'social_links_group',
        'email_lists_group',
        'player_lists_group',
        'user_info',
        'multi_select',
    ];

    foreach ($field_groups as $fields) {
        foreach ($fields as $key => $meta) {
            $type = $meta['type'] ?? 'text';
            $is_complex = in_array($type, $complex_types, true);

            register_post_meta($config['post_type'], $key, [
                'type'              => $is_complex ? 'array' : 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => null,
            ]);
        }
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// METABOX
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Add the admin metabox for an entity type.
 *
 * @param array $config Entity type configuration array.
 */
function owbn_add_entity_meta_box(array $config): void
{
    if (!owbn_is_entity_enabled($config['post_type'])) return;

    $entity_key = $config['entity_key'];
    $singular   = $config['singular'];

    add_meta_box(
        "owbn_{$entity_key}_fields",
        "{$singular} Fields",
        'owbn_render_entity_metabox',
        $config['post_type'],
        'normal',
        'default'
    );
}

/**
 * Generic metabox render callback.
 *
 * Looks up the entity config for the current post type and delegates to
 * the appropriate rendering logic.
 *
 * @param WP_Post $post The post being edited.
 */
function owbn_render_entity_metabox($post)
{
    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return;

    $user_id    = get_current_user_id();
    $entity_key = $config['entity_key'];
    $singular   = $config['singular'];

    // Permission check
    if (!owbn_user_can_edit_entity($user_id, $post->ID)) {
        echo '<p>' . esc_html(
            sprintf(
                /* translators: %s: entity singular name */
                __('You do not have permission to edit this %s.', 'owbn-chronicle-manager'),
                $singular
            )
        ) . '</p>';
        return;
    }

    wp_nonce_field("owbn_{$entity_key}_save", "owbn_{$entity_key}_nonce");

    $callable = $config['field_definitions'] ?? null;
    if (!$callable || !is_callable($callable)) return;

    $field_groups = call_user_func($callable);
    if (!is_array($field_groups)) return;

    $errors            = get_transient("owbn_{$entity_key}_errors_{$post->ID}") ?: [];
    $restricted_fields = $config['restricted_fields'] ?? [];
    $immutable_fields  = $config['immutable_fields'] ?? [];
    $can_edit_metadata = owbn_user_can_edit_entity_metadata($user_id);

    echo "\n<div class=\"owbn-meta-view\">\n";

    // Data source banner — derived from site-level settings
    $url_slug    = $config['url_slug'] ?? '';
    $mode_option = "owbn_{$url_slug}_mode";
    $data_mode   = get_option($mode_option, 'local');

    if ($data_mode === 'remote') {
        $remote_url = get_option("owbn_{$url_slug}_remote_url", '');
        $banner_text = __('REMOTE', 'owbn-chronicle-manager');
        if ($remote_url) {
            $banner_text .= ': ' . $remote_url;
        }
        echo '<div class="owbn-data-source-banner" style="margin-bottom: 15px; padding: 10px 14px; background-color: #fff3cd; border-left: 4px solid #ffc107; font-weight: 600;">';
        echo esc_html($banner_text);
        echo '</div>' . "\n";
    } else {
        echo '<div class="owbn-data-source-banner" style="margin-bottom: 15px; padding: 10px 14px; background-color: #e8f5e9; border-left: 4px solid #4CAF50; font-weight: 600;">';
        echo esc_html__('LOCAL', 'owbn-chronicle-manager');
        echo '</div>' . "\n";
    }

    // Pending changeset banner (inside metabox, above fields)
    $pending = get_post_meta($post->ID, '_owbn_pending_changes', true);
    if (!empty($pending) && !empty($pending['fields'])) {
        $is_current_admin = owbn_is_admin_user();

        if ($is_current_admin) {
            // Admin sees details of pending changes
            $pending_labels = [];
            foreach ($pending['fields'] as $pkey => $pval) {
                foreach ($field_groups as $section => $sfields) {
                    if (isset($sfields[$pkey])) {
                        $pending_labels[] = $sfields[$pkey]['label'];
                    }
                }
            }

            $submitted_by = get_userdata($pending['submitted_by'] ?? 0);
            $submitted_name = $submitted_by ? $submitted_by->display_name : __('Unknown', 'owbn-chronicle-manager');

            echo '<div style="margin-bottom: 15px; padding: 12px 14px; background-color: #fff8e5; border-left: 4px solid #d63638;">';
            echo '<strong><span class="dashicons dashicons-clock" style="vertical-align: text-bottom;"></span> ';
            echo esc_html__('Pending Staff Changes', 'owbn-chronicle-manager');
            echo '</strong>';
            echo '<br><small>';
            printf(
                esc_html__('Submitted by %s on %s', 'owbn-chronicle-manager'),
                esc_html($submitted_name),
                esc_html($pending['submitted_at'] ?? '')
            );
            if (!empty($pending['self_promoted'])) {
                echo ' &mdash; <span style="color: #d63638; font-weight: bold;">' . esc_html__('Self-promotion detected', 'owbn-chronicle-manager') . '</span>';
            }
            echo '</small>';
            if (!empty($pending_labels)) {
                echo '<br><small>' . esc_html__('Fields:', 'owbn-chronicle-manager') . ' ' . esc_html(implode(', ', $pending_labels)) . '</small>';
            }
            echo '<br><small><em>' . esc_html__('Use the Approve/Reject buttons above to act on these changes.', 'owbn-chronicle-manager') . '</em></small>';
            echo '</div>' . "\n";
        } else {
            // Non-admin sees pending status
            echo '<div style="margin-bottom: 15px; padding: 12px 14px; background-color: #fff8e5; border-left: 4px solid #0073aa;">';
            echo '<strong><span class="dashicons dashicons-clock" style="vertical-align: text-bottom;"></span> ';
            echo esc_html__('Pending Staff Changes', 'owbn-chronicle-manager');
            echo '</strong>';
            echo '<br><small><em>' . esc_html__('Your staff changes are awaiting admin approval. Other field changes were saved.', 'owbn-chronicle-manager') . '</em></small>';
            echo '</div>' . "\n";
        }
    }

    foreach ($field_groups as $section_label => $fields) {
        echo '<div class="owbn-field-group">';
        echo '<h3>' . esc_html($section_label) . '</h3>';
        echo '<table class="form-table"><tbody>';

        foreach ($fields as $key => $meta) {
            $value       = get_post_meta($post->ID, $key, true);
            $label       = $meta['label'] ?? $key;
            $type        = $meta['type'] ?? 'text';
            $error_class = in_array($key, $errors, true) ? ' owbn-error-field' : '';

            // Immutable fields are disabled once a value is set.
            $is_immutable = in_array($key, $immutable_fields, true) && !empty($value);
            // Restricted fields are disabled for non-admin users.
            $is_restricted = in_array($key, $restricted_fields, true) && !$can_edit_metadata;

            $disabled_attr = ($is_immutable || $is_restricted) ? ' disabled' : '';

            echo '<tr>';
            echo '<th><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
            echo '<td class="' . esc_attr(trim($error_class)) . '">';

            switch ($type) {
                case 'wysiwyg':
                    owbn_render_wysiwyg_editor($key, $value);
                    break;

                case 'select':
                    owbn_render_select_field($key, $value, $meta, $disabled_attr);
                    break;

                case 'chronicle_select':
                    owbn_render_entity_select_field($key, $value, $meta, $label, $error_class, $disabled_attr);
                    break;

                case 'multi_select':
                    owbn_render_multi_select_field($key, $value, $meta, $disabled_attr);
                    break;

                case 'session_group':
                    owbn_render_session_group($key, $value, $meta);
                    break;

                case 'repeatable_group':
                    owbn_render_repeatable_group($key, $value, $meta);
                    break;

                case 'document_links_group':
                    owbn_render_document_links_field($key, $value, $meta);
                    break;

                case 'social_links_group':
                    owbn_render_social_links_field($key, $value, $meta);
                    break;

                case 'email_lists_group':
                    owbn_render_email_lists_field($key, $value, $meta);
                    break;

                case 'player_lists_group':
                    owbn_render_player_lists_field($key, $value, $meta);
                    break;

                case 'user_info':
                    owbn_render_user_info($key, $value, $meta);
                    break;

                case 'ast_group':
                    owbn_render_ast_group($key, $value, $meta, $key);
                    break;

                case 'boolean':
                    owbn_render_boolean_field($key, $value, $disabled_attr);
                    break;

                case 'ooc_location':
                    owbn_render_ooc_location($key, $value, $meta);
                    break;

                case 'location_group':
                    owbn_render_location_group($key, $value, $meta);
                    break;

                case 'date':
                    echo '<input type="date" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="regular-text"' . esc_attr($disabled_attr) . '>';
                    break;

                case 'number':
                    echo '<input type="number" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '"' . esc_attr($disabled_attr) . '>';
                    break;

                case 'json':
                    echo '<textarea class="large-text code" rows="4" name="' . esc_attr($key) . '"' . esc_attr($disabled_attr) . '>' .
                        esc_textarea(is_scalar($value) ? $value : wp_json_encode($value)) .
                        '</textarea>';
                    break;

                case 'slug':
                    $slug_disabled = !empty($value) ? ' disabled' : $disabled_attr;
                    owbn_render_slug_field($key, $value, $slug_disabled);
                    break;

                default:
                    echo '<input type="text" class="regular-text" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '"' . esc_attr($disabled_attr) . '>';
                    break;
            }

            echo '</td></tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    echo '</div>';
}

// ══════════════════════════════════════════════════════════════════════════════
// PERMISSION MAPPING (map_meta_cap)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Generic map_meta_cap filter for all registered entity types.
 *
 * @param array  $caps    Primitive capabilities required.
 * @param string $cap     Capability being checked.
 * @param int    $user_id User ID.
 * @param array  $args    Additional arguments (post ID, etc.).
 * @return array Filtered capabilities.
 */
function owbn_entity_map_meta_cap($caps, $cap, $user_id, $args)
{
    if (!in_array($cap, ['edit_post', 'delete_post', 'read_post'], true)) return $caps;

    $post_id = !empty($args[0]) ? (int) $args[0] : 0;
    if (!$post_id && !empty($_REQUEST['post'])) $post_id = (int) $_REQUEST['post'];   // phpcs:ignore WordPress.Security.NonceVerification
    if (!$post_id && !empty($_POST['post_ID'])) $post_id = (int) $_POST['post_ID'];   // phpcs:ignore WordPress.Security.NonceVerification

    $post = get_post($post_id);
    if (!$post) return $caps;

    // Only act on registered entity types.
    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return $caps;
    if (!owbn_is_entity_enabled($config['post_type'])) return $caps;

    $user = get_userdata($user_id);
    if (!$user instanceof WP_User) return ['do_not_allow'];

    // Administrators and exec_team always have access.
    if (array_intersect($user->roles, ['administrator', 'exec_team'])) {
        return ['read'];
    }

    // AccessSchema pattern check — use cached roles for performance and consistency.
    $slug_meta_key = $config['slug_meta_key'] ?? '';
    $entity_slug   = $slug_meta_key ? get_post_meta($post_id, $slug_meta_key, true) : '';

    if ($entity_slug) {
        $access_patterns = $config['access_patterns'] ?? [];

        // Check cached roles first (fast, works without live ASC server)
        if (function_exists('owbn_get_cached_user_roles')) {
            $cached_roles = owbn_get_cached_user_roles($user_id, $user->user_email);
            foreach ($access_patterns as $pattern) {
                $resolved = str_replace('{slug}', $entity_slug, $pattern);
                if (in_array($resolved, $cached_roles, true)) {
                    return ['read'];
                }
                // Also check hierarchical (user has a child role)
                if (!empty(preg_grep('#^' . preg_quote($resolved, '#') . '/#', $cached_roles))) {
                    return ['read'];
                }
            }
        }

        // Fallback to direct ASC API call if cached check didn't match
        if (function_exists('accessSchema_client_roles_match_pattern_from_email')) {
            $client_id = defined('ASC_PREFIX') ? strtolower(str_replace('_', '-', ASC_PREFIX)) : 'ccs';
            $email     = $user->user_email;

            foreach ($access_patterns as $pattern) {
                $resolved = str_replace('{slug}', $entity_slug, $pattern);
                if (accessSchema_client_roles_match_pattern_from_email($email, $resolved, $client_id)) {
                    return ['read'];
                }
            }
        }
    }

    // Fallback: check staff_fields for direct user assignment.
    $staff_fields = $config['staff_fields'] ?? [];
    foreach ($staff_fields as $field_key) {
        $field_value = get_post_meta($post_id, $field_key, true);
        $assigned_id = isset($field_value['user']) ? (int) $field_value['user'] : 0;
        if ($assigned_id && $user_id === $assigned_id) {
            return ['read'];
        }
    }

    return ['do_not_allow'];
}

// ══════════════════════════════════════════════════════════════════════════════
// PERMISSION HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Check if a user can edit a specific entity post.
 *
 * Uses the same logic as the map_meta_cap filter but returns a boolean.
 *
 * @param int $user_id WordPress user ID.
 * @param int $post_id WordPress post ID.
 * @return bool
 */
function owbn_user_can_edit_entity(int $user_id, int $post_id): bool
{
    $user = get_userdata($user_id);
    $post = get_post($post_id);
    if (!$user || !$post) return false;

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return false;
    if (!owbn_is_entity_enabled($config['post_type'])) return false;

    // Administrators and exec_team always have access.
    if (array_intersect($user->roles, ['administrator', 'exec_team'])) {
        return true;
    }

    // AccessSchema pattern check — use cached roles for performance and consistency.
    $slug_meta_key = $config['slug_meta_key'] ?? '';
    $entity_slug   = $slug_meta_key ? get_post_meta($post_id, $slug_meta_key, true) : '';

    if ($entity_slug) {
        $access_patterns = $config['access_patterns'] ?? [];

        // Check cached roles first (fast, works without live ASC server)
        if (function_exists('owbn_get_cached_user_roles')) {
            $cached_roles = owbn_get_cached_user_roles($user_id, $user->user_email);
            foreach ($access_patterns as $pattern) {
                $resolved = str_replace('{slug}', $entity_slug, $pattern);
                if (in_array($resolved, $cached_roles, true)) {
                    return true;
                }
                if (!empty(preg_grep('#^' . preg_quote($resolved, '#') . '/#', $cached_roles))) {
                    return true;
                }
            }
        }

        // Fallback to direct ASC API call
        if (function_exists('accessSchema_client_roles_match_pattern_from_email')) {
            $client_id = defined('ASC_PREFIX') ? strtolower(str_replace('_', '-', ASC_PREFIX)) : 'ccs';
            $email     = $user->user_email;

            foreach ($access_patterns as $pattern) {
                $resolved = str_replace('{slug}', $entity_slug, $pattern);
                if (accessSchema_client_roles_match_pattern_from_email($email, $resolved, $client_id)) {
                    return true;
                }
            }
        }
    }

    // Fallback: check staff_fields for direct user assignment.
    $staff_fields = $config['staff_fields'] ?? [];
    foreach ($staff_fields as $field_key) {
        $field_value = get_post_meta($post_id, $field_key, true);
        $assigned_id = isset($field_value['user']) ? (int) $field_value['user'] : 0;
        if ($assigned_id && (int) $user_id === $assigned_id) {
            return true;
        }
    }

    return false;
}

/**
 * Check if a user can edit restricted entity metadata fields (slug, region, etc.).
 *
 * Only administrators, exec_team, and web_team may modify these fields.
 *
 * @param int|null $user_id Optional user ID. Defaults to current user.
 * @return bool
 */
function owbn_user_can_edit_entity_metadata(int $user_id = null): bool
{
    $user = $user_id ? get_userdata($user_id) : wp_get_current_user();
    if (!$user instanceof WP_User) return false;

    // Admin, exec_team, and web_team can always edit restricted metadata.
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return true;
    }

    return false;
}

// ══════════════════════════════════════════════════════════════════════════════
// PERMALINKS & REWRITES
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Custom permalink filter for entity types using their slug meta key.
 *
 * @param string  $post_link Default permalink.
 * @param WP_Post $post      Post object.
 * @return string Filtered permalink.
 */
function owbn_custom_entity_permalink($post_link, $post)
{
    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return $post_link;
    if (!owbn_is_entity_enabled($config['post_type'])) return $post_link;

    $slug_meta_key = $config['slug_meta_key'] ?? '';
    $url_slug      = $config['url_slug'] ?? '';

    if (!$slug_meta_key || !$url_slug) return $post_link;

    $slug = get_post_meta($post->ID, $slug_meta_key, true);
    $slug = $slug ? sanitize_title($slug) : sanitize_title($post->post_title);

    return home_url("/{$url_slug}/{$slug}/");
}

/**
 * Register rewrite rules for all entity types.
 */
function owbn_custom_entity_rewrite_rules()
{
    foreach (owbn_get_entity_types() as $post_type => $config) {
        if (!owbn_is_entity_enabled($post_type)) continue;

        $url_slug = $config['url_slug'] ?? '';
        if (!$url_slug) continue;

        add_rewrite_rule(
            "^{$url_slug}/([^/]+)/?$",
            "index.php?post_type={$post_type}&name=\$matches[1]",
            'top'
        );
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// TEMPLATE INCLUDE
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Load entity-specific single templates from the plugin.
 *
 * Looks for templates/single-{post_type}.php relative to this file.
 *
 * @param string $template Current template path.
 * @return string Filtered template path.
 */
function owbn_entity_template_include($template)
{
    foreach (owbn_get_entity_types() as $post_type => $config) {
        if (!is_singular($post_type)) continue;
        if (!owbn_is_entity_enabled($post_type)) continue;

        $plugin_template = plugin_dir_path(__FILE__) . "../templates/single-{$post_type}.php";
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }

    return $template;
}

// ══════════════════════════════════════════════════════════════════════════════
// FILE UPLOAD SUPPORT
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Add enctype="multipart/form-data" to the post edit form for entity types.
 */
function owbn_add_entity_enctype()
{
    global $post;
    if (!$post) return;

    if (owbn_is_entity_post_type($post->post_type)) {
        echo ' enctype="multipart/form-data"';
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// INITIALIZATION CALLBACKS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Register CPTs and meta for all entity types.
 *
 * Hooked to 'init'.
 */
function owbn_init_entity_types()
{
    foreach (owbn_get_entity_types() as $config) {
        owbn_register_entity_cpt($config);
        owbn_register_entity_meta($config);
    }
}

/**
 * Add metaboxes for all entity types.
 *
 * Hooked to 'add_meta_boxes'.
 */
function owbn_init_entity_meta_boxes()
{
    foreach (owbn_get_entity_types() as $config) {
        owbn_add_entity_meta_box($config);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// HOOK REGISTRATIONS
// ══════════════════════════════════════════════════════════════════════════════

add_action('init', 'owbn_init_entity_types');
add_action('add_meta_boxes', 'owbn_init_entity_meta_boxes');
add_filter('map_meta_cap', 'owbn_entity_map_meta_cap', 10, 4);
add_filter('post_type_link', 'owbn_custom_entity_permalink', 10, 2);
add_action('init', 'owbn_custom_entity_rewrite_rules');
add_filter('template_include', 'owbn_entity_template_include');
add_action('post_edit_form_tag', 'owbn_add_entity_enctype');
