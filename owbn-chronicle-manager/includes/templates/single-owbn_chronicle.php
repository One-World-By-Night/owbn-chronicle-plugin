<?php
if (!defined('ABSPATH')) exit;

get_header(); // Load theme header

$post_id = get_the_ID();
echo '<div class="owbn-chronicle-single">';
echo owbn_render_chronicle_card($post_id); // Or full view function if preferred
echo '</div>';

get_footer(); // Load theme footer

add_action('wp_enqueue_scripts', function () {
    if (is_singular('owbn_chronicle')) {
        wp_enqueue_style(
            'owbn-chronicle-single',
            plugin_dir_url(__FILE__) . 'assets/css/owbn-chronicle-single.css',
            [],
            '1.0.0'
        );
    }
});