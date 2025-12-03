<?php
if (!defined('ABSPATH')) exit;

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR POST TYPE
// ══════════════════════════════════════════════════════════════════════════════

function owbn_register_coordinator_cpt()
{
    if (!owbn_coordinators_enabled()) return;

    register_post_type('owbn_coordinator', [
        'labels' => [
            'name'                     => _x('Coordinators', 'Post type general name', 'owbn-chronicle-manager'),
            'singular_name'            => _x('Coordinator', 'Post type singular name', 'owbn-chronicle-manager'),
            'menu_name'                => _x('Coordinators', 'Admin Menu text', 'owbn-chronicle-manager'),
            'name_admin_bar'           => _x('Coordinator', 'Add New on Toolbar', 'owbn-chronicle-manager'),
            'add_new'                  => _x('Add New', 'Coordinator', 'owbn-chronicle-manager'),
            'add_new_item'             => __('Add New Coordinator', 'owbn-chronicle-manager'),
            'new_item'                 => __('New Coordinator', 'owbn-chronicle-manager'),
            'edit_item'                => __('Edit Coordinator', 'owbn-chronicle-manager'),
            'view_item'                => __('View Coordinator', 'owbn-chronicle-manager'),
            'view_items'               => __('View Coordinators', 'owbn-chronicle-manager'),
            'all_items'                => __('All Coordinators', 'owbn-chronicle-manager'),
            'search_items'             => __('Search Coordinators', 'owbn-chronicle-manager'),
            'parent_item_colon'        => __('Parent Coordinators:', 'owbn-chronicle-manager'),
            'not_found'                => __('No coordinators found.', 'owbn-chronicle-manager'),
            'not_found_in_trash'       => __('No coordinators found in Trash.', 'owbn-chronicle-manager'),
            'archives'                 => __('Coordinator Archives', 'owbn-chronicle-manager'),
            'insert_into_item'         => __('Insert into coordinator', 'owbn-chronicle-manager'),
            'uploaded_to_this_item'    => __('Uploaded to this coordinator', 'owbn-chronicle-manager'),
            'filter_items_list'        => __('Filter coordinators list', 'owbn-chronicle-manager'),
            'items_list_navigation'    => __('Coordinators list navigation', 'owbn-chronicle-manager'),
            'items_list'               => __('Coordinators list', 'owbn-chronicle-manager'),
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'coords', 'with_front' => false],
        'supports'           => ['title', 'editor', 'author', 'revisions'],
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-groups',
        'capability_type'    => 'owbn_coordinator',
        'map_meta_cap'       => true,
        'capabilities'       => [
            'edit_post'              => 'edit_owbn_coordinator',
            'read_post'              => 'read_owbn_coordinator',
            'delete_post'            => 'delete_owbn_coordinator',
            'edit_posts'             => 'ocm_view_list',
            'edit_others_posts'      => 'edit_owbn_coordinator',
            'publish_posts'          => 'ocm_create_coordinator',
            'read_private_posts'     => 'read_owbn_coordinator',
            'delete_posts'           => 'delete_owbn_coordinator',
            'delete_others_posts'    => 'delete_owbn_coordinator',
            'delete_published_posts' => 'delete_owbn_coordinator',
            'delete_private_posts'   => 'delete_owbn_coordinator',
            'edit_published_posts'   => 'edit_owbn_coordinator',
            'edit_private_posts'     => 'edit_owbn_coordinator',
        ],
    ]);
}
add_action('init', 'owbn_register_coordinator_cpt');

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR PERMALINKS & REWRITES
// ══════════════════════════════════════════════════════════════════════════════

function owbn_custom_coordinator_permalink($post_link, $post)
{
    if (!owbn_coordinators_enabled() || $post->post_type !== 'owbn_coordinator') return $post_link;

    $slug = get_post_meta($post->ID, 'coordinator_slug', true);
    $slug = $slug ? sanitize_title($slug) : sanitize_title($post->post_title);

    return home_url("/coords/{$slug}/");
}
add_filter('post_type_link', 'owbn_custom_coordinator_permalink', 10, 2);

function owbn_custom_coordinator_rewrite_rules()
{
    if (!owbn_coordinators_enabled()) return;

    add_rewrite_rule('^coords/([^/]+)/?$', 'index.php?post_type=owbn_coordinator&name=$matches[1]', 'top');
}
add_action('init', 'owbn_custom_coordinator_rewrite_rules');

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR META FIELDS
// ══════════════════════════════════════════════════════════════════════════════

function owbn_register_coordinator_meta()
{
    if (!owbn_coordinators_enabled()) return;

    $complex = ['coord_info', 'subcoord_list', 'document_links', 'email_lists', 'player_lists'];
    $simple  = ['coordinator_slug', 'coordinator_title', 'office_description', 'term_start_date', 'term_end_date', 'web_url'];

    foreach ($complex as $field) {
        register_post_meta('owbn_coordinator', $field, ['type' => 'array', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => null]);
    }
    foreach ($simple as $field) {
        register_post_meta('owbn_coordinator', $field, ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => null]);
    }
}
add_action('init', 'owbn_register_coordinator_meta');

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR FIELD DEFINITIONS (mirrors chronicle fields.php pattern)
// ══════════════════════════════════════════════════════════════════════════════

function owbn_get_coordinator_field_definitions()
{
    return [
        'Basic Info' => [
            'coordinator_slug' => [
                'label' => __('Coordinator Slug', 'owbn-chronicle-manager'),
                'type'  => 'text',
            ],
            'coordinator_title' => [
                'label' => __('Office Title', 'owbn-chronicle-manager'),
                'type'  => 'text',
            ],
            'term_start_date' => [
                'label' => __('Term Start Date', 'owbn-chronicle-manager'),
                'type'  => 'date',
            ],
            'web_url' => [
                'label' => __('Website URL', 'owbn-chronicle-manager'),
                'type'  => 'text',
            ],
            'office_description' => [
                'label' => __('Office Description', 'owbn-chronicle-manager'),
                'type'  => 'wysiwyg',
            ],
        ],
        'Coordinator' => [
            'coord_info' => [
                'label' => __('Coordinator', 'owbn-chronicle-manager'),
                'type'  => 'user_info',
            ],
        ],
        'Staff' => [
            'subcoord_list' => [
                'label' => __('Sub-Coordinators', 'owbn-chronicle-manager'),
                'type'  => 'ast_group',
                'fields' => [
                    'user' => [
                        'label' => __('User', 'owbn-chronicle-manager'),
                        'type'  => 'user',
                    ],
                    'display_name' => [
                        'label' => __('Display Name', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'role' => [
                        'label' => __('Role/Title', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'actual_email' => [
                        'label' => __('Actual Email', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                    'display_email' => [
                        'label' => __('Display Email', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                ],
            ],
        ],
        'Links' => [
            'document_links' => [
                'label' => __('Document Links', 'owbn-chronicle-manager'),
                'type'  => 'document_links_group',
                'fields' => [
                    'title' => [
                        'label' => __('Title', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'link' => [
                        'label' => __('External URL', 'owbn-chronicle-manager'),
                        'type'  => 'url',
                    ],
                    'upload' => [
                        'label' => __('Upload File', 'owbn-chronicle-manager'),
                        'type'  => 'file',
                    ],
                ],
            ],
            'email_lists' => [
                'label' => __('Staff Lists', 'owbn-chronicle-manager'),
                'type'  => 'email_lists_group',
                'fields' => [
                    'list_name' => [
                        'label' => __('List Name', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'email_address' => [
                        'label' => __('Email Address', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                    'description' => [
                        'label' => __('Description', 'owbn-chronicle-manager'),
                        'type'  => 'textarea',
                    ],
                ],
            ],
            'player_lists' => [
                'label' => __('Player Lists', 'owbn-chronicle-manager'),
                'type'  => 'player_lists_group',
                'fields' => [
                    'list_name' => [
                        'label' => __('List Name', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'access' => [
                        'label'   => __('Access', 'owbn-chronicle-manager'),
                        'type'    => 'select',
                        'options' => ['Public' => 'Public', 'Private' => 'Private'],
                    ],
                    'address' => [
                        'label' => __('Address', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                    'ic_ooc' => [
                        'label'   => __('IC/OOC', 'owbn-chronicle-manager'),
                        'type'    => 'select',
                        'options' => ['IC' => 'In Character', 'OOC' => 'Out of Character'],
                    ],
                    'moderate_address' => [
                        'label' => __('Moderator Email', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                    'signup_url' => [
                        'label' => __('Sign Up URL', 'owbn-chronicle-manager'),
                        'type'  => 'url',
                    ],
                ],
            ],
        ],
    ];
}

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR METABOX
// ══════════════════════════════════════════════════════════════════════════════

function owbn_add_coordinator_meta_box()
{
    if (!owbn_coordinators_enabled()) return;

    add_meta_box('owbn_coordinator_fields', 'Coordinator Fields', 'owbn_render_coordinator_fields_metabox', 'owbn_coordinator', 'normal', 'default');
}
add_action('add_meta_boxes', 'owbn_add_coordinator_meta_box');

function owbn_render_coordinator_fields_metabox($post)
{
    wp_nonce_field('owbn_coordinator_meta_nonce', 'owbn_coordinator_nonce');

    $field_groups = owbn_get_coordinator_field_definitions();

    echo '<div class="owbn-coordinator-metabox">';

    foreach ($field_groups as $group_label => $fields) {
        echo '<div class="owbn-field-group">';
        echo '<h3>' . esc_html($group_label) . '</h3>';
        echo '<table class="form-table"><tbody>';

        foreach ($fields as $key => $meta) {
            $value = get_post_meta($post->ID, $key, true);
            $label = $meta['label'] ?? $key;
            $type  = $meta['type'] ?? 'text';

            echo '<tr>';
            echo '<th><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
            echo '<td>';

            switch ($type) {
                case 'wysiwyg':
                    wp_editor(
                        is_scalar($value) ? $value : '',
                        $key,
                        [
                            'textarea_name' => $key,
                            'textarea_rows' => 6,
                            'media_buttons' => false,
                        ]
                    );
                    break;

                case 'date':
                    echo '<input type="date" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="regular-text">';
                    break;

                case 'user_info':
                    echo '</td></tr><tr><td colspan="2">';
                    if (function_exists('owbn_render_user_info')) {
                        owbn_render_user_info($key, is_array($value) ? $value : [], $meta);
                    }
                    break;

                case 'ast_group':
                    echo '</td></tr><tr><td colspan="2">';
                    if (function_exists('owbn_render_ast_group')) {
                        owbn_render_ast_group($key, is_array($value) ? $value : [], $meta, $key);
                    }
                    break;

                case 'document_links_group':
                    echo '</td></tr><tr><td colspan="2">';
                    if (function_exists('owbn_render_document_links_field')) {
                        owbn_render_document_links_field($key, is_array($value) ? $value : [], $meta);
                    }
                    break;

                case 'email_lists_group':
                    echo '</td></tr><tr><td colspan="2">';
                    if (function_exists('owbn_render_email_lists_field')) {
                        owbn_render_email_lists_field($key, is_array($value) ? $value : [], $meta);
                    }
                    break;

                case 'player_lists_group':
                    echo '</td></tr><tr><td colspan="2">';
                    if (function_exists('owbn_render_player_lists_field')) {
                        owbn_render_player_lists_field($key, is_array($value) ? $value : [], $meta);
                    }
                    break;

                default:
                    echo '<input type="text" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="regular-text">';
                    break;
            }

            if ($key === 'coordinator_slug') {
                echo '<p class="description">URL identifier (e.g., "assamite"). Used for AccessSchema: Coordinator/{slug}/Coordinator</p>';
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    echo '</div>';
}

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR PERMISSIONS
// ══════════════════════════════════════════════════════════════════════════════

add_filter('map_meta_cap', 'owbn_coordinator_map_meta_cap', 10, 4);
function owbn_coordinator_map_meta_cap($caps, $cap, $user_id, $args)
{
    if (!owbn_coordinators_enabled()) return $caps;
    if (!in_array($cap, ['edit_post', 'delete_post', 'read_post'], true)) return $caps;

    $post_id = !empty($args[0]) ? (int) $args[0] : 0;
    if (!$post_id && !empty($_REQUEST['post'])) $post_id = (int) $_REQUEST['post'];
    if (!$post_id && !empty($_POST['post_ID'])) $post_id = (int) $_POST['post_ID'];

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'owbn_coordinator') return $caps;

    $user = get_userdata($user_id);
    if (!$user instanceof WP_User) return ['do_not_allow'];

    // Admin/exec always allowed
    if (array_intersect($user->roles, ['administrator', 'exec_team'])) {
        return [$cap === 'edit_post' ? 'edit_owbn_coordinator' : ($cap === 'delete_post' ? 'delete_owbn_coordinator' : 'read_owbn_coordinator')];
    }

    // Check AccessSchema: Coordinator/{slug}/Coordinator
    $coord_slug = get_post_meta($post_id, 'coordinator_slug', true);
    if ($coord_slug && function_exists('current_user_can') && current_user_can('asc_has_access_to_group', "Coordinator/{$coord_slug}/Coordinator")) {
        return [$cap === 'edit_post' ? 'edit_owbn_coordinator' : ($cap === 'delete_post' ? 'delete_owbn_coordinator' : 'read_owbn_coordinator')];
    }

    // Fallback: coord_info user
    $coord_info = get_post_meta($post_id, 'coord_info', true);
    $coord_user_id = isset($coord_info['user']) ? (int) $coord_info['user'] : 0;
    if ($coord_user_id && $user_id === $coord_user_id) {
        return [$cap === 'edit_post' ? 'edit_owbn_coordinator' : ($cap === 'delete_post' ? 'delete_owbn_coordinator' : 'read_owbn_coordinator')];
    }

    return ['do_not_allow'];
}

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════════

function owbn_user_can_edit_coordinator($user_id, $post_id)
{
    if (!owbn_coordinators_enabled()) return false;

    $user = get_userdata($user_id);
    $post = get_post($post_id);
    if (!$user || !$post) return false;

    if (array_intersect($user->roles, ['administrator', 'exec_team'])) return true;

    // Check AccessSchema
    $coord_slug = get_post_meta($post_id, 'coordinator_slug', true);
    if ($coord_slug && function_exists('current_user_can')) {
        $old_user = wp_get_current_user();
        wp_set_current_user($user_id);
        $has_access = current_user_can('asc_has_access_to_group', "Coordinator/{$coord_slug}/Coordinator");
        wp_set_current_user($old_user->ID);
        if ($has_access) return true;
    }

    // Fallback: coord_info user
    if (in_array('coord_staff', $user->roles, true)) {
        $coord_info = get_post_meta($post_id, 'coord_info', true);
        $coord_user_id = isset($coord_info['user']) ? (int) $coord_info['user'] : 0;
        return $coord_user_id && (int)$user_id === $coord_user_id;
    }

    return false;
}

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR TEMPLATE
// ══════════════════════════════════════════════════════════════════════════════

add_filter('template_include', function ($template) {
    if (is_singular('owbn_coordinator') && owbn_coordinators_enabled()) {
        $plugin_template = plugin_dir_path(__FILE__) . '../templates/single-owbn_coordinator.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
});

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR SAVE HANDLER
// ══════════════════════════════════════════════════════════════════════════════

add_action('save_post_owbn_coordinator', 'owbn_save_coordinator_meta', 10, 2);
function owbn_save_coordinator_meta($post_id, $post)
{
    // Verify nonce
    if (!isset($_POST['owbn_coordinator_nonce']) || !wp_verify_nonce($_POST['owbn_coordinator_nonce'], 'owbn_coordinator_meta_nonce')) {
        return;
    }

    // Skip autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) return;

    // Simple text/date fields
    $simple_fields = ['coordinator_slug', 'coordinator_title', 'term_start_date', 'web_url'];
    foreach ($simple_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Office description (WYSIWYG)
    if (isset($_POST['office_description'])) {
        update_post_meta($post_id, 'office_description', wp_kses_post($_POST['office_description']));
    }

    // Coordinator info (user_info pattern)
    if (isset($_POST['coord_info']) && is_array($_POST['coord_info'])) {
        $info = $_POST['coord_info'];
        $cleaned = [
            'user'          => sanitize_text_field($info['user'] ?? ''),
            'display_name'  => sanitize_text_field($info['display_name'] ?? ''),
            'actual_email'  => sanitize_email($info['actual_email'] ?? ''),
            'display_email' => sanitize_email($info['display_email'] ?? ''),
        ];
        update_post_meta($post_id, 'coord_info', $cleaned);
    }

    // Subcoord list (ast_group pattern)
    if (isset($_POST['subcoord_list']) && is_array($_POST['subcoord_list'])) {
        $cleaned = [];
        foreach ($_POST['subcoord_list'] as $index => $row) {
            if ($index === '__INDEX__') continue;
            if (empty($row['display_name']) && empty($row['user'])) continue;

            $cleaned[] = [
                'user'          => sanitize_text_field($row['user'] ?? ''),
                'display_name'  => sanitize_text_field($row['display_name'] ?? ''),
                'role'          => sanitize_text_field($row['role'] ?? ''),
                'actual_email'  => sanitize_email($row['actual_email'] ?? ''),
                'display_email' => sanitize_email($row['display_email'] ?? ''),
            ];
        }
        update_post_meta($post_id, 'subcoord_list', $cleaned);
    }

    // Document links (document_links_group pattern)
    if (isset($_POST['document_links']) && is_array($_POST['document_links'])) {
        $cleaned = [];
        foreach ($_POST['document_links'] as $index => $row) {
            if ($index === '__INDEX__') continue;
            if (empty($row['title']) && empty($row['link'])) continue;

            $entry = [
                'title' => sanitize_text_field($row['title'] ?? ''),
                'link'  => esc_url_raw($row['link'] ?? ''),
            ];

            // Preserve existing file_id
            $existing = get_post_meta($post_id, 'document_links', true);
            if (is_array($existing) && isset($existing[$index]['file_id'])) {
                $entry['file_id'] = $existing[$index]['file_id'];
            }

            $cleaned[] = $entry;
        }
        update_post_meta($post_id, 'document_links', $cleaned);
    }

    // Email lists (email_lists_group pattern)
    if (isset($_POST['email_lists']) && is_array($_POST['email_lists'])) {
        $cleaned = [];
        foreach ($_POST['email_lists'] as $index => $row) {
            if ($index === '__INDEX__') continue;
            if (empty($row['list_name']) && empty($row['email_address'])) continue;

            $cleaned[] = [
                'list_name'     => sanitize_text_field($row['list_name'] ?? ''),
                'email_address' => sanitize_email($row['email_address'] ?? ''),
                'description'   => wp_kses_post($row['description'] ?? ''),
            ];
        }
        update_post_meta($post_id, 'email_lists', $cleaned);
    }
    // Player lists
    if (isset($_POST['player_lists']) && is_array($_POST['player_lists'])) {
        $cleaned = [];
        foreach ($_POST['player_lists'] as $index => $row) {
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
        update_post_meta($post_id, 'player_lists', $cleaned);
    }
}
