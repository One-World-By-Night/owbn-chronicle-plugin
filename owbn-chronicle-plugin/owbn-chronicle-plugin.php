<?php
/**
 * Plugin Name: OWBN Chronicle Manager
 * Text Domain: owbn-chronicle-manager
 * Description: Manage OWBN Chronicle information using structured custom post types, shortcodes, and approval workflows. Includes support for nested locations, staff roles, genre declarations, and versioned updates via Gravity Forms and Gravity Flow.
 * Version: 1.0.0
 * Author: greghacke
 * Author URI: https://www.owbn.net
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/One-World-By-Night/owbn-chronicle-plugin
 * GitHub Branch: main
 */

 // Register Custom Post Type
function owbn_register_chronicle_cpt() {
    register_post_type('owbn_chronicle', [
        'labels' => [
            'name' => 'Chronicles',
            'singular_name' => 'Chronicle',
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

// Filter the permalink structure for owbn_chronicle
function owbn_custom_chronicle_permalink($post_link, $post) {
    if ($post->post_type !== 'owbn_chronicle') return $post_link;

    $plug = get_post_meta($post->ID, 'chronicle_plug', true);
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
        'game_site_virtual',
        'game_site_physical',
        'genres',
        'social_urls',
        'session_list',
        'hst_info',
        'cm_info',
        'ast_list',
        'admin_contact',
        'document_links',
        'email_lists',
    ];

    $simple_fields = [
        'chronicle_plug',
        'premise',
        'game_theme',
        'game_mood',
        'traveler_info',
        'active_player_count',
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

// Ensure prerequisites are met
function owbn_check_required_plugins() {
    if (!class_exists('GFForms')) {
        add_action('admin_notices', 'owbn_notice_gf_missing');
        return;
    }

    if (!class_exists('Gravity_Flow')) {
        add_action('admin_notices', 'owbn_notice_gf_missing_gf');
        return;
    }
}
add_action('admin_init', 'owbn_check_required_plugins');

// Display admin notice for missing Gravity Forms
function owbn_notice_gf_missing() {
    echo '<div class="notice notice-error"><p><strong>OWBN Chronicle Manager</strong> requires <strong>Gravity Forms</strong> to function properly. Please install and activate Gravity Forms.</p></div>';
}

// Display admin notice for missing Gravity Flow
function owbn_notice_gf_missing_gf() {
    echo '<div class="notice notice-error"><p><strong>OWBN Chronicle Manager</strong> requires <strong>Gravity Flow</strong> for workflow approval. Please install and activate Gravity Flow.</p></div>';
}
add_action('admin_init', 'owbn_check_required_plugins');


///// Closing Content /////
// Flush permalinks on activation/deactivation
function owbn_activate_plugin() {
    owbn_register_chronicle_cpt();
    owbn_custom_chronicle_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'owbn_activate_plugin');

function owbn_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'owbn_deactivate_plugin');