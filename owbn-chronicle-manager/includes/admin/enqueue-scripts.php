<?php
if (!defined('ABSPATH')) exit;

function owbn_enqueue_admin_assets($hook)
{
    // Only load for post edit screen
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    // Load for both chronicle and coordinator post types
    global $post;
    if (!isset($post->post_type) || !in_array($post->post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        return;
    }

    // CSS
    wp_enqueue_style(
        'owbn-chronicle-style',
        plugin_dir_url(dirname(__FILE__, 2)) . 'css/style.css',
        [],
        filemtime(plugin_dir_path(dirname(__FILE__, 2)) . 'css/style.css')
    );

    wp_enqueue_style(
        'select2-css',
        plugin_dir_url(dirname(__FILE__, 2)) . 'css/select2.min.css',
        [],
        '4.0.13'
    );

    // JS
    wp_enqueue_script(
        'select2-js',
        plugin_dir_url(dirname(__FILE__, 2)) . 'js/select2.min.js',
        ['jquery'],
        '4.0.13',
        true
    );

    wp_enqueue_script(
        'owbn-chronicle-js',
        plugin_dir_url(dirname(__FILE__, 2)) . 'js/owbn-chronicle-plugin.js',
        ['jquery', 'select2-js'],
        '1.0.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'owbn_enqueue_admin_assets');

function owbn_enqueue_plugin_assets()
{
    // Prevent duplicate enqueuing
    if (wp_script_is('owbn-chronicle-js', 'enqueued')) {
        return;
    }

    $plugin_url  = plugin_dir_url(dirname(__FILE__, 2));
    $plugin_path = plugin_dir_path(dirname(__FILE__, 2));

    wp_enqueue_style(
        'owbn-chronicle-style',
        $plugin_url . 'css/style.css',
        [],
        filemtime($plugin_path . 'css/style.css')
    );

    wp_enqueue_style(
        'select2-css',
        $plugin_url . 'css/select2.min.css',
        [],
        '4.0.13'
    );

    wp_enqueue_script(
        'select2-js',
        $plugin_url . 'js/select2.min.js',
        ['jquery'],
        '4.0.13',
        true
    );

    wp_enqueue_script(
        'owbn-chronicle-js',
        $plugin_url . 'js/owbn-chronicle-plugin.js',
        ['jquery', 'select2-js'],
        '1.0.0',
        true
    );
}

function owbn_enqueue_frontend_assets()
{
    // Get global post content (if available)
    global $post;

    // Always enqueue if post content includes either shortcode
    if (
        is_a($post, 'WP_Post') &&
        (
            has_shortcode($post->post_content, 'owbn-chronicles') ||
            has_shortcode($post->post_content, 'owbn-chronicle')
        )
    ) {
        owbn_enqueue_plugin_assets();
    }

    // OR: always enqueue unconditionally if content may be injected via template functions or blocks
    // Uncomment this line to force it to load everywhere (if needed)
    // owbn_enqueue_plugin_assets();
}
add_action('wp_enqueue_scripts', 'owbn_enqueue_frontend_assets');
