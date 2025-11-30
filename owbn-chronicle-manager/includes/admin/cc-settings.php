<?php
if (!defined('ABSPATH')) exit;

/**
 * C&C Plugin Settings - Settings > C&C Plugin
 */

add_action('admin_menu', function () {
    add_options_page(
        __('C&C Plugin Settings', 'owbn-chronicle-manager'),
        __('C&C Plugin', 'owbn-chronicle-manager'),
        'manage_options',
        'owbn-cc-settings',
        'owbn_render_cc_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('owbn_cc_settings', 'owbn_enable_chronicles', [
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    register_setting('owbn_cc_settings', 'owbn_enable_coordinators', [
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);

    add_settings_section('owbn_cc_features', __('Feature Toggles', 'owbn-chronicle-manager'), function () {
        echo '<p>' . esc_html__('Enable or disable plugin features. Disabled features hide from admin and API.', 'owbn-chronicle-manager') . '</p>';
    }, 'owbn-cc-settings');

    add_settings_field('owbn_enable_chronicles', __('Chronicles', 'owbn-chronicle-manager'), 'owbn_render_toggle_field', 'owbn-cc-settings', 'owbn_cc_features', [
        'option_name' => 'owbn_enable_chronicles',
        'label' => __('Enable Chronicle management', 'owbn-chronicle-manager'),
    ]);

    add_settings_field('owbn_enable_coordinators', __('Coordinators', 'owbn-chronicle-manager'), 'owbn_render_toggle_field', 'owbn-cc-settings', 'owbn_cc_features', [
        'option_name' => 'owbn_enable_coordinators',
        'label' => __('Enable Coordinator management', 'owbn-chronicle-manager'),
    ]);
});

function owbn_render_toggle_field($args)
{
    $value = get_option($args['option_name'], true);
    echo '<label><input type="checkbox" name="' . esc_attr($args['option_name']) . '" value="1" ' . checked($value, true, false) . ' /> ' . esc_html($args['label']) . '</label>';
}

function owbn_render_cc_settings_page()
{
    if (!current_user_can('manage_options')) return;

    if (isset($_GET['settings-updated'])) {
        update_option('owbn_flush_rewrite_rules', true);
        add_settings_error('owbn_cc_messages', 'owbn_cc_message', __('Settings saved.', 'owbn-chronicle-manager'), 'updated');
    }
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors('owbn_cc_messages'); ?>
        <form action="options.php" method="post">
            <?php settings_fields('owbn_cc_settings');
            do_settings_sections('owbn-cc-settings');
            submit_button(); ?>
        </form>
        <hr />
        <h2><?php esc_html_e('Status', 'owbn-chronicle-manager'); ?></h2>
        <table class="widefat" style="max-width:300px;">
            <tr>
                <td><strong>Chronicles</strong></td>
                <td><?php echo get_option('owbn_enable_chronicles', true) ? '<span style="color:green;">✓ Enabled</span>' : '<span style="color:red;">✗ Disabled</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Coordinators</strong></td>
                <td><?php echo get_option('owbn_enable_coordinators', true) ? '<span style="color:green;">✓ Enabled</span>' : '<span style="color:red;">✗ Disabled</span>'; ?></td>
            </tr>
        </table>
    </div>
<?php
}
