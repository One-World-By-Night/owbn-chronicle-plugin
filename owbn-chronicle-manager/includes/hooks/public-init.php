<?php
if (!defined('ABSPATH')) exit;

// ─── Template Override for Single Chronicle ─────────────
add_filter('template_include', function ($template) {
    if (is_singular('owbn_chronicle')) {
        $plugin_template = plugin_dir_path(__FILE__) . '/../templates/single-owbn_chronicle.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
});

// ─── Enqueue Admin Assets ───────────────────────────────
function owbn_enqueue_admin_assets($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    global $post;
    if (!isset($post->post_type) || $post->post_type !== 'owbn_chronicle') {
        return;
    }

    owbn_enqueue_plugin_assets();
}
add_action('admin_enqueue_scripts', 'owbn_enqueue_admin_assets');

// ─── Plugin-Wide Asset Enqueue (Shared Use) ─────────────
function owbn_enqueue_plugin_assets() {
    $base_url = plugin_dir_url(dirname(__FILE__, 2)) . 'css/';
    $base_js  = plugin_dir_url(dirname(__FILE__, 2)) . 'js/';

    wp_enqueue_style('owbn-chronicle-style', $base_url . 'style.css', [], '1.0.0');
    wp_enqueue_style('select2-css', $base_url . 'select2.min.css', [], '4.0.13');

    wp_enqueue_script('select2-js', $base_js . 'select2.min.js', ['jquery'], '4.0.13', true);
    wp_enqueue_script('owbn-chronicle-js', $base_js . 'owbn-chronicle-plugin.js', ['jquery', 'select2-js'], '1.0.0', true);
}

// ─── Frontend Asset Loader ──────────────────────────────
function owbn_enqueue_frontend_assets() {
    global $post;

    if (
        is_a($post, 'WP_Post') &&
        (
            has_shortcode($post->post_content, 'owbn-chronicles') ||
            has_shortcode($post->post_content, 'owbn-chronicle')
        )
    ) {
        owbn_enqueue_plugin_assets();
    }

    // Load single post CSS conditionally
    if (is_singular('owbn_chronicle')) {
        wp_enqueue_style(
            'owbn-chronicle-single',
            plugin_dir_url(dirname(__FILE__, 2)) . 'assets/css/owbn-chronicle-single.css',
            [],
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'owbn_enqueue_frontend_assets');

function owbn_register_chronicle_meta_fields() {
    $fields = [
        'chronicle_slug' => 'string',
        'web_url' => 'string',
        'active_player_count' => 'string',
        'hst_selection' => 'string',
        'cm_selection' => 'string',
        'ast_selection' => 'string',
        'chronicle_start_date' => 'string',
        'chronicle_region' => 'string',
        'chronicle_probationary' => 'boolean',
        'chronicle_satellite' => 'boolean',
    ];

    foreach ($fields as $field => $type) {
        register_meta('post', $field, [
            'type' => $type,
            'description' => ucfirst(str_replace('_', ' ', $field)),
            'single' => true,
            'show_in_rest' => true, // Necessary for Elementor dynamic tags
            'auth_callback' => function() { return current_user_can('edit_posts'); },
        ]);
    }
}
add_action('init', 'owbn_register_chronicle_meta_fields');