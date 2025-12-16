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

// ─── Register Chronicle Meta for REST/Elementor ─────────
function owbn_register_chronicle_meta_fields()
{
    $fields = [
        'record_type'             => 'string',
        'chronicle_slug'          => 'string',
        'web_url'                 => 'string',
        'active_player_count'     => 'string',
        'hst_selection'           => 'string',
        'cm_selection'            => 'string',
        'ast_selection'           => 'string',
        'chronicle_start_date'    => 'string',
        'chronicle_region'        => 'string',
        'chronicle_probationary'  => 'boolean',
        'chronicle_satellite'     => 'boolean',
    ];

    foreach ($fields as $field => $type) {
        register_meta('post', $field, [
            'type'          => $type,
            'description'   => ucfirst(str_replace('_', ' ', $field)),
            'single'        => true,
            'show_in_rest'  => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}
add_action('init', 'owbn_register_chronicle_meta_fields');
