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
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'coords', 'with_front' => false],
        'supports'           => ['title', 'editor', 'author', 'revisions'],
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-groups',
        'capability_type'    => 'owbn_coordinator',
        'map_meta_cap'       => true,
        'capabilities'       => [
            'edit_post'             => 'edit_owbn_coordinator',
            'read_post'             => 'read_owbn_coordinator',
            'delete_post'           => 'delete_owbn_coordinator',
            'edit_posts'            => 'ocm_view_list',
            'edit_others_posts'     => 'edit_owbn_coordinator',
            'publish_posts'         => 'ocm_create_coordinator',
            'read_private_posts'    => 'read_owbn_coordinator',
            'delete_posts'          => 'delete_owbn_coordinator',
            'delete_others_posts'   => 'delete_owbn_coordinator',
            'delete_published_posts' => 'delete_owbn_coordinator',
            'delete_private_posts'  => 'delete_owbn_coordinator',
            'edit_published_posts'  => 'edit_owbn_coordinator',
            'edit_private_posts'    => 'edit_owbn_coordinator',
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

    $complex = ['subcoord_list', 'social_urls', 'document_links', 'email_lists'];
    $simple  = ['coordinator_slug', 'coordinator_title', 'coordinator_email', 'coordinator_user', 'coordinator_display_name', 'office_description', 'term_start_date', 'term_end_date', 'web_url'];

    foreach ($complex as $field) {
        register_post_meta('owbn_coordinator', $field, ['type' => 'array', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => null]);
    }
    foreach ($simple as $field) {
        register_post_meta('owbn_coordinator', $field, ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => null]);
    }
}
add_action('init', 'owbn_register_coordinator_meta');

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

    $slug         = get_post_meta($post->ID, 'coordinator_slug', true);
    $title        = get_post_meta($post->ID, 'coordinator_title', true);
    $email        = get_post_meta($post->ID, 'coordinator_email', true);
    $display_name = get_post_meta($post->ID, 'coordinator_display_name', true);
    $description  = get_post_meta($post->ID, 'office_description', true);
    $term_start   = get_post_meta($post->ID, 'term_start_date', true);
    $web_url      = get_post_meta($post->ID, 'web_url', true);

    echo '<table class="form-table"><tbody>';

    echo '<tr><th><label for="coordinator_slug">Coordinator Slug</label></th>';
    echo '<td><input type="text" id="coordinator_slug" name="coordinator_slug" value="' . esc_attr($slug) . '" class="regular-text" />';
    echo '<p class="description">URL identifier (e.g., "assamite"). Used for AccessSchema: Coordinator/{slug}/Coordinator</p></td></tr>';

    echo '<tr><th><label for="coordinator_title">Office Title</label></th>';
    echo '<td><input type="text" id="coordinator_title" name="coordinator_title" value="' . esc_attr($title) . '" class="regular-text" />';
    echo '<p class="description">e.g., "Assamite Coordinator"</p></td></tr>';

    echo '<tr><th><label for="coordinator_display_name">Coordinator Name</label></th>';
    echo '<td><input type="text" id="coordinator_display_name" name="coordinator_display_name" value="' . esc_attr($display_name) . '" class="regular-text" /></td></tr>';

    echo '<tr><th><label for="coordinator_email">Contact Email</label></th>';
    echo '<td><input type="email" id="coordinator_email" name="coordinator_email" value="' . esc_attr($email) . '" class="regular-text" /></td></tr>';

    echo '<tr><th><label for="term_start_date">Term Start Date</label></th>';
    echo '<td><input type="date" id="term_start_date" name="term_start_date" value="' . esc_attr($term_start) . '" /></td></tr>';

    echo '<tr><th><label for="web_url">Website URL</label></th>';
    echo '<td><input type="url" id="web_url" name="web_url" value="' . esc_attr($web_url) . '" class="regular-text" /></td></tr>';

    echo '<tr><th><label for="office_description">Office Description</label></th><td>';
    wp_editor($description, 'office_description', ['textarea_name' => 'office_description', 'media_buttons' => false, 'textarea_rows' => 8, 'teeny' => true]);
    echo '</td></tr>';

    echo '</tbody></table>';
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

    // Check AccessSchema
    $coord_slug = get_post_meta($post_id, 'coordinator_slug', true);
    if ($coord_slug && current_user_can('asc_has_access_to_group', "Coordinator/{$coord_slug}")) {
        return [$cap === 'edit_post' ? 'edit_owbn_coordinator' : ($cap === 'delete_post' ? 'delete_owbn_coordinator' : 'read_owbn_coordinator')];
    }

    // Fallback: coordinator_user meta
    $coord_user = get_post_meta($post_id, 'coordinator_user', true);
    if ($coord_user && $user_id === (int) $coord_user) {
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
    if ($coord_slug) {
        $old_user = wp_get_current_user();
        wp_set_current_user($user_id);
        $has_access = current_user_can('asc_has_access_to_group', "Coordinator/{$coord_slug}");
        wp_set_current_user($old_user->ID);
        if ($has_access) return true;
    }

    // Fallback: coordinator_user meta
    if (in_array('coord_staff', $user->roles, true)) {
        $coord_user = get_post_meta($post_id, 'coordinator_user', true);
        return $coord_user && (int)$user_id === (int)$coord_user;
    }

    return false;
}

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR TEMPLATE
// ══════════════════════════════════════════════════════════════════════════════

add_filter('template_include', function ($template) {
    if (is_singular('owbn_coordinator') && owbn_coordinators_enabled()) {
        $plugin_template = plugin_dir_path(__FILE__) . '../templates/single-owbn_coordinator.php';
        if (file_exists($plugin_template)) return $plugin_template;
    }
    return $template;
});
