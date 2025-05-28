<?php
if (!defined('ABSPATH')) exit;

function owbn_enqueue_admin_assets($hook) {
    // Optional: restrict to post edit screen
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    // Only load for the correct post type
    global $post;
    if (!isset($post->post_type) || $post->post_type !== 'owbn_chronicle') {
        return;
    }

    // CSS
    wp_enqueue_style(
        'owbn-chronicle-style',
        plugin_dir_url(dirname(__FILE__, 2)) . 'css/style.css',
        [],
        '1.0.0'
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