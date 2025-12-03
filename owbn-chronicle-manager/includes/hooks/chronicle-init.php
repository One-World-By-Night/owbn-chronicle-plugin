<?php
if (!defined('ABSPATH')) exit;

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE POST TYPE
// ══════════════════════════════════════════════════════════════════════════════

function owbn_register_chronicle_cpt()
{
    if (!owbn_chronicles_enabled()) return;

    register_post_type('owbn_chronicle', [
        'labels' => [
            'name'                     => _x('Chronicles', 'Post type general name', 'owbn-chronicle-manager'),
            'singular_name'            => _x('Chronicle', 'Post type singular name', 'owbn-chronicle-manager'),
            'menu_name'                => _x('Chronicles', 'Admin Menu text', 'owbn-chronicle-manager'),
            'name_admin_bar'           => _x('Chronicle', 'Add New on Toolbar', 'owbn-chronicle-manager'),
            'add_new'                  => _x('Add New', 'Chronicle', 'owbn-chronicle-manager'),
            'add_new_item'             => __('Add New Chronicle', 'owbn-chronicle-manager'),
            'new_item'                 => __('New Chronicle', 'owbn-chronicle-manager'),
            'edit_item'                => __('Edit Chronicle', 'owbn-chronicle-manager'),
            'view_item'                => __('View Chronicle', 'owbn-chronicle-manager'),
            'view_items'               => __('View Chronicles', 'owbn-chronicle-manager'),
            'all_items'                => __('All Chronicles', 'owbn-chronicle-manager'),
            'search_items'             => __('Search Chronicles', 'owbn-chronicle-manager'),
            'parent_item_colon'        => __('Parent Chronicles:', 'owbn-chronicle-manager'),
            'not_found'                => __('No chronicles found.', 'owbn-chronicle-manager'),
            'not_found_in_trash'       => __('No chronicles found in Trash.', 'owbn-chronicle-manager'),
            'archives'                 => __('Chronicle Archives', 'owbn-chronicle-manager'),
            'insert_into_item'         => __('Insert into chronicle', 'owbn-chronicle-manager'),
            'uploaded_to_this_item'    => __('Uploaded to this chronicle', 'owbn-chronicle-manager'),
            'filter_items_list'        => __('Filter chronicles list', 'owbn-chronicle-manager'),
            'items_list_navigation'    => __('Chronicles list navigation', 'owbn-chronicle-manager'),
            'items_list'               => __('Chronicles list', 'owbn-chronicle-manager'),
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'chronicles', 'with_front' => false],
        'supports'           => ['title', 'editor', 'author', 'revisions'],
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-location-alt',
        'capability_type'    => 'owbn_chronicle',
        'map_meta_cap'       => true,
        'capabilities'       => [
            'edit_post'             => 'edit_owbn_chronicle',
            'read_post'             => 'read_owbn_chronicle',
            'delete_post'           => 'delete_owbn_chronicle',
            'edit_posts'            => 'ocm_view_list',
            'edit_others_posts'     => 'edit_owbn_chronicle',
            'publish_posts'         => 'ocm_create_chronicle',
            'read_private_posts'    => 'read_owbn_chronicle',
            'delete_posts'          => 'delete_owbn_chronicle',
            'delete_others_posts'   => 'delete_owbn_chronicle',
            'delete_published_posts' => 'delete_owbn_chronicle',
            'delete_private_posts'  => 'delete_owbn_chronicle',
            'edit_published_posts'  => 'edit_owbn_chronicle',
            'edit_private_posts'    => 'edit_owbn_chronicle',
        ],
    ]);
}
add_action('init', 'owbn_register_chronicle_cpt');

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE PERMALINKS & REWRITES
// ══════════════════════════════════════════════════════════════════════════════

function owbn_custom_chronicle_permalink($post_link, $post)
{
    if (!owbn_chronicles_enabled() || $post->post_type !== 'owbn_chronicle') return $post_link;

    $slug = get_post_meta($post->ID, 'chronicle_slug', true);
    $slug = $slug ? sanitize_title($slug) : sanitize_title($post->post_title);

    return home_url("/chronicles/{$slug}/");
}
add_filter('post_type_link', 'owbn_custom_chronicle_permalink', 10, 2);

function owbn_custom_chronicle_rewrite_rules()
{
    if (!owbn_chronicles_enabled()) return;

    add_rewrite_rule('^chronicles/([^/]+)/?$', 'index.php?post_type=owbn_chronicle&name=$matches[1]', 'top');
}
add_action('init', 'owbn_custom_chronicle_rewrite_rules');

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE META FIELDS
// ══════════════════════════════════════════════════════════════════════════════

function owbn_register_chronicle_meta()
{
    if (!owbn_chronicles_enabled()) return;

    $complex = ['ooc_locations', 'ic_location_list', 'game_site_list', 'genres', 'social_urls', 'session_list', 'admin_contact', 'document_links', 'email_lists', 'player_lists'];
    $simple = ['chronicle_slug', 'premise', 'game_theme', 'game_mood', 'traveler_info', 'active_player_count', 'hst_user', 'hst_display_name', 'hst_email', 'cm_user', 'cm_display_name', 'cm_email', 'web_url', 'hst_selection', 'cm_selection', 'ast_selection', 'chronicle_start_date', 'chronicle_region', 'chronicle_probationary', 'chronicle_satellite', 'chronicle_parent'];

    foreach ($complex as $field) {
        register_post_meta('owbn_chronicle', $field, ['type' => 'array', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => null]);
    }
    foreach ($simple as $field) {
        register_post_meta('owbn_chronicle', $field, ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => null]);
    }
}
add_action('init', 'owbn_register_chronicle_meta');

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE METABOX
// ══════════════════════════════════════════════════════════════════════════════

function owbn_add_chronicle_meta_box()
{
    if (!owbn_chronicles_enabled()) return;

    add_meta_box('owbn_chronicle_fields', 'Chronicle Fields', 'owbn_render_chronicle_fields_metabox', 'owbn_chronicle', 'normal', 'default');
}
add_action('add_meta_boxes', 'owbn_add_chronicle_meta_box');

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE PERMISSIONS
// ══════════════════════════════════════════════════════════════════════════════

add_filter('map_meta_cap', 'owbn_chronicle_map_meta_cap', 10, 4);
function owbn_chronicle_map_meta_cap($caps, $cap, $user_id, $args)
{
    if (!owbn_chronicles_enabled()) return $caps;
    if (!in_array($cap, ['edit_post', 'delete_post', 'read_post'], true)) return $caps;

    $post_id = !empty($args[0]) ? (int) $args[0] : 0;
    if (!$post_id && !empty($_REQUEST['post'])) $post_id = (int) $_REQUEST['post'];
    if (!$post_id && !empty($_POST['post_ID'])) $post_id = (int) $_POST['post_ID'];

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'owbn_chronicle') return $caps;

    $user = get_userdata($user_id);
    if (!$user instanceof WP_User) return ['do_not_allow'];

    $hst_info = get_post_meta($post_id, 'hst_info', true);
    $cm_info  = get_post_meta($post_id, 'cm_info', true);
    $hst_id   = isset($hst_info['user']) ? (int) $hst_info['user'] : 0;
    $cm_id    = isset($cm_info['user']) ? (int) $cm_info['user'] : 0;

    $is_admin = array_intersect($user->roles, ['administrator', 'exec_team']);
    $is_staff = ($user_id === $hst_id || $user_id === $cm_id);

    // Check AccessSchema: Chronicle/{slug}/HST or Chronicle/{slug}/CM
    $chronicle_slug = get_post_meta($post_id, 'chronicle_slug', true);
    $has_asc_access = false;
    if ($chronicle_slug && function_exists('current_user_can')) {
        $has_asc_access = current_user_can('asc_has_access_to_group', "Chronicle/{$chronicle_slug}/HST")
            || current_user_can('asc_has_access_to_group', "Chronicle/{$chronicle_slug}/CM");
    }

    if (!empty($is_admin) || $is_staff || $has_asc_access) {
        return [$cap === 'edit_post' ? 'edit_owbn_chronicle' : ($cap === 'delete_post' ? 'delete_owbn_chronicle' : 'read_owbn_chronicle')];
    }

    return ['do_not_allow'];
}

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════════

function owbn_user_can_edit_chronicle($user_id, $post_id)
{
    if (!owbn_chronicles_enabled()) return false;

    $user = get_userdata($user_id);
    $post = get_post($post_id);
    if (!$user || !$post) return false;

    if (array_intersect($user->roles, ['administrator', 'exec_team'])) return true;

    // Check AccessSchema
    $chronicle_slug = get_post_meta($post_id, 'chronicle_slug', true);
    if ($chronicle_slug && function_exists('current_user_can')) {
        $old_user = wp_get_current_user();
        wp_set_current_user($user_id);
        $has_access = current_user_can('asc_has_access_to_group', "Chronicle/{$chronicle_slug}/HST")
            || current_user_can('asc_has_access_to_group', "Chronicle/{$chronicle_slug}/CM");
        wp_set_current_user($old_user->ID);
        if ($has_access) return true;
    }

    // Fallback: direct user assignment
    if (in_array('chron_staff', $user->roles, true)) {
        $hst_info = get_post_meta($post_id, 'hst_info', true);
        $cm_info  = get_post_meta($post_id, 'cm_info', true);
        $hst_id   = isset($hst_info['user']) ? (int) $hst_info['user'] : 0;
        $cm_id    = isset($cm_info['user']) ? (int) $cm_info['user'] : 0;

        return (int)$user_id === $hst_id || (int)$user_id === $cm_id;
    }

    return false;
}

function owbn_user_can_edit_metadata_fields($user_id = null)
{
    if (!owbn_chronicles_enabled()) return false;

    $user = $user_id ? get_userdata($user_id) : wp_get_current_user();
    if (!$user instanceof WP_User) return false;

    if (array_intersect($user->roles, ['administrator', 'exec_team'])) return true;

    global $post;
    if ($post instanceof WP_Post && $post->post_type === 'owbn_chronicle') {
        $hst_info = get_post_meta($post->ID, 'hst_info', true);
        $cm_info  = get_post_meta($post->ID, 'cm_info', true);
        $hst_id   = isset($hst_info['user']) ? (int) $hst_info['user'] : 0;
        $cm_id    = isset($cm_info['user']) ? (int) $cm_info['user'] : 0;

        return in_array('chron_staff', $user->roles, true) && ((int)$user->ID === $hst_id || (int)$user->ID === $cm_id);
    }

    return false;
}

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE TEMPLATE
// ══════════════════════════════════════════════════════════════════════════════

add_filter('template_include', function ($template) {
    if (is_singular('owbn_chronicle') && owbn_chronicles_enabled()) {
        $plugin_template = plugin_dir_path(__FILE__) . '../templates/single-owbn_chronicle.php';
        if (file_exists($plugin_template)) return $plugin_template;
    }
    return $template;
});
