<?php
// Register Custom Post Type
function owbn_register_chronicle_cpt() {
    register_post_type('owbn_chronicle', [
        'labels' => [
            'name' => __('Chronicles', 'owbn-chronicle-manager'),
            'singular_name' => __('Chronicle', 'owbn-chronicle-manager'),
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => [
            'slug' => 'chronicles',
            'with_front' => false,
        ],
        'supports' => ['title', 'editor', 'author', 'revisions'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-location-alt',
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
            'type'              => 'object',
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
