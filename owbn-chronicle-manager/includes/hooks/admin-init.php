<?php
if (!defined('ABSPATH')) exit;

// Register Custom Post Type
function owbn_register_chronicle_cpt() {
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
        'has_archive'        => true,
        'rewrite'            => [
            'slug'       => 'chronicles',
            'with_front' => false,
        ],
        'supports'           => ['title', 'editor', 'author', 'revisions'],
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-location-alt',

        // Capability mapping
        'capability_type'    => 'owbn_chronicle',
        'map_meta_cap'       => true,
        'capabilities'       => [
        'edit_post' => 'edit_owbn_chronicle',
        'read_post' => 'read_owbn_chronicle',
        'delete_post' => 'delete_owbn_chronicle',

        'edit_posts' => 'ocm_view_list',
        'edit_others_posts' => 'edit_owbn_chronicle',
        'publish_posts' => 'ocm_create_chronicle',
        'read_private_posts' => 'read_owbn_chronicle',

        'delete_posts' => 'delete_owbn_chronicle',
        'delete_others_posts' => 'delete_owbn_chronicle',
        'delete_published_posts' => 'delete_owbn_chronicle',
        'delete_private_posts' => 'delete_owbn_chronicle',

        'edit_published_posts' => 'edit_owbn_chronicle',
        'edit_private_posts' => 'edit_owbn_chronicle',
        ],
    ]);
}
add_action('init', 'owbn_register_chronicle_cpt');

function owbn_register_options_menu() {
    add_submenu_page(
        'edit.php?post_type=owbn_chronicle', // Parent menu under your CPT
        __('OWbN Options', 'owbn-chronicle-manager'), // Page title
        __('Options', 'owbn-chronicle-manager'),     // Menu title
        'manage_options',                            // Capability
        'owbn-options',                              // Menu slug
        'owbn_render_options_page'                   // Callback function
    );
}
add_action('admin_menu', 'owbn_register_options_menu');

// Filter the permalink structure for owbn_chronicle
function owbn_custom_chronicle_permalink($post_link, $post) {
    if ($post->post_type !== 'owbn_chronicle') return $post_link;

    $plug = get_post_meta($post->ID, 'chronicle_slug', true);
    $plug = $plug ? sanitize_title($plug) : sanitize_title($post->post_title);

    return home_url("/chronicles/{$plug}/");
}
add_filter('post_type_link', 'owbn_custom_chronicle_permalink', 10, 2);

// Add custom rewrite rule for owbn_chronicle
function owbn_custom_chronicle_rewrite_rules() {
    add_rewrite_rule(
        '^chronicles/([^/]+)/?$',
        'index.php?post_type=owbn_chronicle&name=$matches[1]',
        'top'
    );
}
add_action('init', 'owbn_custom_chronicle_rewrite_rules');

// Flush rewrite rules on plugin activation
add_action('init', function () {
    if (get_option('owbn_flush_rewrite_rules')) {
        flush_rewrite_rules();
        delete_option('owbn_flush_rewrite_rules');
    }
}, 99);

// Register chronicle meta fields
function owbn_register_chronicle_meta() {
    $complex_fields = [
        'ooc_locations',
        'ic_location_list',
        'game_site_list',
        'genres',
        'social_urls',
        'session_list',
        'admin_contact',
        'document_links',
        'email_lists',
    ];

    $simple_fields = [
        'chronicle_slug',
        'premise',
        'game_theme',
        'game_mood',
        'traveler_info',
        'active_player_count',
        'hst_user',
        'hst_display_name',
        'hst_email',
        'cm_user',
        'cm_display_name',
        'cm_email',
        'web_url',
        'hst_selection',
        'cm_selection',
        'ast_selection',
        'chronicle_start_date',
        'chronicle_region',
        'chronicle_probationary',
        'chronicle_satellite',
        'chronicle_parent',
    ];

    foreach ($complex_fields as $field) {
        register_post_meta('owbn_chronicle', $field, [
            'type'              => 'array',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => null,
        ]);
    }

    foreach ($simple_fields as $field) {
        register_post_meta('owbn_chronicle', $field, [
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => null,
        ]);
    }
}
add_action('init', 'owbn_register_chronicle_meta');

// Register Chronicle Fields metabox
function owbn_add_chronicle_meta_box() {
    add_meta_box(
        'owbn_chronicle_fields',
        'Chronicle Fields',
        'owbn_render_chronicle_fields_metabox',
        'owbn_chronicle',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'owbn_add_chronicle_meta_box');


// PERMISSIONS AND CAPABILITIES

add_filter('map_meta_cap', function ($caps, $cap, $user_id, $args) {
    // Try to extract post ID from args[0], fallback to request
    $post_id = 0;

    if (!empty($args[0])) {
        $post_id = (int) $args[0];
    } elseif (!empty($_REQUEST['post'])) {
        $post_id = (int) sanitize_text_field(wp_unslash($_REQUEST['post']));
    } elseif (!empty($_POST['post_ID'])) {
        $post_id = (int) sanitize_text_field(wp_unslash($_POST['post_ID']));
    }

    if (!in_array($cap, ['edit_post', 'delete_post', 'read_post'], true)) {
        return $caps;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'owbn_chronicle') {
        return $caps;
    }

    $user = get_userdata($user_id);
    if (!$user instanceof WP_User) {
        return ['do_not_allow'];
    }

    $hst_info = get_post_meta($post_id, 'hst_info', true);
    $cm_info  = get_post_meta($post_id, 'cm_info', true);

    $hst_id = isset($hst_info['user']) ? (int) $hst_info['user'] : 0;
    $cm_id  = isset($cm_info['user']) ? (int) $cm_info['user'] : 0;

    $is_exec_or_admin = array_intersect($user->roles, ['administrator', 'exec_team']);
    $is_cm_or_hst = ($user_id === $hst_id || $user_id === $cm_id);

    if (!empty($is_exec_or_admin) || $is_cm_or_hst) {
        switch ($cap) {
            case 'edit_post':
                return ['edit_owbn_chronicle'];
            case 'delete_post':
                return ['delete_owbn_chronicle'];
            case 'read_post':
                return ['read_owbn_chronicle'];
        }
    }

    return ['do_not_allow'];
}, 10, 4);

function owbn_grant_admin_chronicle_caps() {
    $role = get_role('administrator');
    if (!$role) return;

    $caps = [
        // Base capabilities
        'ocm_view_chronicle',
        'ocm_view_list',
        'ocm_edit_chronicle',
        'ocm_delete_chronicle',
        'ocm_create_chronicle',

        // Mapped capabilities that WordPress uses when map_meta_cap => true
        'edit_owbn_chronicle',
        'read_owbn_chronicle',
        'delete_owbn_chronicle',
        'edit_owbn_chronicles',
        'edit_others_owbn_chronicles',
        'publish_owbn_chronicles',
        'read_private_owbn_chronicles',
        'create_owbn_chronicles',
    ];

    foreach ($caps as $cap) {
        if (!$role->has_cap($cap)) {
            $role->add_cap($cap);
        }
    }
}
add_action('admin_init', 'owbn_grant_admin_chronicle_caps');

function owbn_create_custom_roles() {
    add_role('web_team', 'Web Team', [
        'read' => true,
        'ocm_view_chronicle' => true,
        'ocm_view_list' => true,
        'ocm_edit_chronicle' => true,
        'ocm_delete_chronicle' => true,
        'ocm_create_chronicle' => true,

        'edit_owbn_chronicle' => true,
        'read_owbn_chronicle' => true,
        'delete_owbn_chronicle' => true,
        'edit_owbn_chronicles' => true,
        'edit_others_owbn_chronicles' => true,
        'publish_owbn_chronicles' => true,
        'read_private_owbn_chronicles' => true,
        'create_owbn_chronicles' => true,
        'manage_options' => true,
    ]);

    add_role('exec_team', 'Exec Team', [
        'read' => true,
        'ocm_view_chronicle' => true,
        'ocm_view_list' => true,
        'ocm_edit_chronicle' => true,
        'ocm_delete_chronicle' => true,
        'ocm_create_chronicle' => true,
        'edit_owbn_chronicle' => true,
        'read_owbn_chronicle' => true,
        'delete_owbn_chronicle' => true,

        'edit_owbn_chronicle' => true,
        'read_owbn_chronicle' => true,
        'delete_owbn_chronicle' => true,
        'edit_owbn_chronicles' => true,
        'edit_others_owbn_chronicles' => true,
        'publish_owbn_chronicles' => true,
        'read_private_owbn_chronicles' => true,
        'create_owbn_chronicles' => true,
    ]);

    add_role('chron_staff', 'Chronicle Staff', [
        'read' => true,
        'ocm_view_chronicle' => true,
        'ocm_view_list' => true,
        'ocm_edit_chronicle' => true,
        'ocm_delete_chronicle' => true,
        'ocm_create_chronicle' => true,
        'edit_owbn_chronicle' => true,
        'read_owbn_chronicle' => true,
    ]);
    // Clone ADMIN capabilities to web_team
    $admin_role = get_role('administrator');
    $web_team = get_role('web_team');
    if ($admin_role && $web_team) {
        foreach ($admin_role->capabilities as $cap => $grant) {
            $web_team->add_cap($cap);
        }
    }
    // Ensure chron_staff can pass WP core author check
    $chron_staff = get_role('chron_staff');
    if ($chron_staff) {
        if (!$chron_staff->has_cap('edit_posts')) {
            $chron_staff->add_cap('edit_posts');
        }
        if (!$chron_staff->has_cap('edit_others_posts')) {
            $chron_staff->add_cap('edit_others_posts');
        }
    }
}


/**
 * Check if a user can edit a given Chronicle post
 */
function owbn_user_can_edit_chronicle($user_id, $post_id) {
    $user = get_userdata($user_id);
    if (!$user || !($post = get_post($post_id))) return false;

    // Allow administrators and exec_team full access
    if (array_intersect($user->roles, ['administrator', 'exec_team'])) {
        return true;
    }

    // Chronicle Staff can edit only if they're the HST or CM
    if (in_array('chron_staff', $user->roles, true)) {
        $hst_info = get_post_meta($post_id, 'hst_info', true);
        $cm_info  = get_post_meta($post_id, 'cm_info', true);

        $hst_id = isset($hst_info['user']) ? (int) $hst_info['user'] : 0;
        $cm_id  = isset($cm_info['user']) ? (int) $cm_info['user'] : 0;

        return (int)$user_id === $hst_id || (int)$user_id === $cm_id;
    }

    // Otherwise, no access
    return false;
}

/**
 * Check if a user can edit metadata fields in the Chronicle post editor.
 * Defaults to current user if no user ID is passed.
 */
function owbn_user_can_edit_metadata_fields($user_id = null) {
    $user = $user_id ? get_userdata($user_id) : wp_get_current_user();
    if (!$user || !($user instanceof WP_User)) return false;

    // Admins and exec_team can always edit
    if (array_intersect($user->roles, ['administrator', 'exec_team'])) {
        return true;
    }

    // Chronicle Staff can only edit if they're HST or CM on the current post
    global $post;
    if ($post instanceof WP_Post && $post->post_type === 'owbn_chronicle') {
        $hst_info = get_post_meta($post->ID, 'hst_info', true);
        $cm_info  = get_post_meta($post->ID, 'cm_info', true);

        $hst_id = isset($hst_info['user']) ? (int) $hst_info['user'] : 0;
        $cm_id  = isset($cm_info['user']) ? (int) $cm_info['user'] : 0;

        return in_array('chron_staff', $user->roles, true) &&
               ((int)$user->ID === $hst_id || (int)$user->ID === $cm_id);
    }

    return false;
}

add_action('init', function () {
    // One-time flush of rewrite rules if flagged
    if (get_option('owbn_flush_rewrite_rules')) {
        flush_rewrite_rules();
        delete_option('owbn_flush_rewrite_rules');
    }

    // One-time setup for genres
    if (!get_option('owbn_genre_list')) {
        update_option('owbn_genre_list', array(
            'Vampire - Anarch',
            'Vampire - Camarilla',
            'Vampire - Sabbat',
            'Vampire - Giovanni',
            'Vampire - Clan Specific',
            'Changeling',
            'Changing Breeds',
            'Demon',
            'Hunter',
            'Kuei-Jin',
            'Mage',
            'Other',
            'Wraith'
        ));
    }
});

add_filter('template_include', function ($template) {
    if (is_singular('owbn_chronicle')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'includes/templates/single-owbn_chronicle.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
});