<?php
if (!defined('ABSPATH')) exit;

/**
 * C&C Plugin Settings - Settings > C&C Plugin
 * 
 * Includes:
 * - Feature toggles (Chronicles, Coordinators)
 * - API Settings
 * - Genre list management
 * - Region list management
 */

// ══════════════════════════════════════════════════════════════════════════════
// ADMIN MENU
// ══════════════════════════════════════════════════════════════════════════════

add_action('admin_menu', function () {
    add_options_page(
        __('C&C Plugin Settings', 'owbn-chronicle-manager'),
        __('C&C Plugin', 'owbn-chronicle-manager'),
        'manage_options',
        'owbn-cc-settings',
        'owbn_render_cc_settings_page'
    );
});

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER SETTINGS
// ══════════════════════════════════════════════════════════════════════════════

add_action('admin_init', function () {
    // // API Key
    // register_setting('owbn_cc_settings', 'owbn_api_key_readonly', [
    //     'type' => 'string',
    //     'sanitize_callback' => 'sanitize_text_field'
    // ]);

    // Feature toggles
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

    // Feature Toggles Section
    add_settings_section(
        'owbn_cc_features',
        __('Feature Toggles', 'owbn-chronicle-manager'),
        function () {
            echo '<p>' . esc_html__('Enable or disable plugin features. Disabled features hide from admin and API.', 'owbn-chronicle-manager') . '</p>';
        },
        'owbn-cc-settings'
    );

    add_settings_field(
        'owbn_enable_chronicles',
        __('Chronicles', 'owbn-chronicle-manager'),
        'owbn_render_toggle_field',
        'owbn-cc-settings',
        'owbn_cc_features',
        [
            'option_name' => 'owbn_enable_chronicles',
            'label' => __('Enable Chronicle management', 'owbn-chronicle-manager'),
        ]
    );

    add_settings_field(
        'owbn_enable_coordinators',
        __('Coordinators', 'owbn-chronicle-manager'),
        'owbn_render_toggle_field',
        'owbn-cc-settings',
        'owbn_cc_features',
        [
            'option_name' => 'owbn_enable_coordinators',
            'label' => __('Enable Coordinator management', 'owbn-chronicle-manager'),
        ]
    );
});

function owbn_render_toggle_field($args)
{
    $value = get_option($args['option_name'], true);
    echo '<label><input type="checkbox" name="' . esc_attr($args['option_name']) . '" value="1" ' . checked($value, true, false) . ' /> ' . esc_html($args['label']) . '</label>';
}

// ══════════════════════════════════════════════════════════════════════════════
// RENDER SETTINGS PAGE
// ══════════════════════════════════════════════════════════════════════════════

function owbn_render_cc_settings_page()
{
    if (!current_user_can('manage_options')) return;

    // Handle settings-updated flag
    if (isset($_GET['settings-updated'])) {
        update_option('owbn_flush_rewrite_rules', true);
        add_settings_error('owbn_cc_messages', 'owbn_cc_message', __('Settings saved.', 'owbn-chronicle-manager'), 'updated');
    }

    // Process Genre form submission
    if (
        isset($_POST['owbn_genres_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['owbn_genres_nonce'])), 'save_owbn_genres')
    ) {
        $lines = array_filter(array_map(
            'sanitize_text_field',
            array_map('trim', explode("\n", sanitize_textarea_field(wp_unslash($_POST['owbn_genres'] ?? ''))))
        ));
        update_option('owbn_genre_list', $lines);
        add_settings_error('owbn_cc_messages', 'owbn_genres_message', __('Genres updated.', 'owbn-chronicle-manager'), 'updated');
    }

    // Process Region form submission
    if (
        isset($_POST['owbn_regions_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['owbn_regions_nonce'])), 'save_owbn_regions')
    ) {
        $lines = array_filter(array_map(
            'sanitize_text_field',
            array_map('trim', explode("\n", sanitize_textarea_field(wp_unslash($_POST['owbn_regions'] ?? ''))))
        ));
        update_option('owbn_region_list', $lines);
        add_settings_error('owbn_cc_messages', 'owbn_regions_message', __('Regions updated.', 'owbn-chronicle-manager'), 'updated');
    }

    // Process API Key form submission
    if (
        isset($_POST['owbn_api_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['owbn_api_nonce'])), 'save_owbn_api')
    ) {
        $api_key = sanitize_text_field(wp_unslash($_POST['owbn_api_key_readonly'] ?? ''));
        update_option('owbn_api_key_readonly', $api_key);
        add_settings_error('owbn_cc_messages', 'owbn_api_message', __('API settings saved.', 'owbn-chronicle-manager'), 'updated');
    }

    // Fetch current values
    $genres = get_option('owbn_genre_list', []);
    $regions = get_option('owbn_region_list', []);
    $genres_text = is_array($genres) ? implode("\n", $genres) : '';
    $regions_text = is_array($regions) ? implode("\n", $regions) : '';
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors('owbn_cc_messages'); ?>

        <!-- Feature Toggles -->
        <form action="options.php" method="post">
            <?php
            settings_fields('owbn_cc_settings');
            do_settings_sections('owbn-cc-settings');
            submit_button(__('Save Features', 'owbn-chronicle-manager'));
            ?>
        </form>

        <hr />

        <!-- API Settings -->
        <h2><?php esc_html_e('API Settings', 'owbn-chronicle-manager'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('save_owbn_api', 'owbn_api_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('API URL', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <code id="owbn_api_url"><?php echo esc_url(rest_url('owbn-cc/v1/')); ?></code>
                        <button type="button" class="button" onclick="navigator.clipboard.writeText(document.getElementById('owbn_api_url').textContent)"><?php esc_html_e('Copy', 'owbn-chronicle-manager'); ?></button>
                        <p class="description"><?php esc_html_e('Base URL for client connections', 'owbn-chronicle-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Read-Only API Key', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <input type="text" name="owbn_api_key_readonly" id="owbn_api_key_ro"
                            value="<?php echo esc_attr(get_option('owbn_api_key_readonly', '')); ?>"
                            class="regular-text code" readonly />
                        <button type="button" class="button" onclick="owbnGenerateApiKey('owbn_api_key_ro')"><?php esc_html_e('Generate New', 'owbn-chronicle-manager'); ?></button>
                        <p class="description"><?php esc_html_e('Use this key for read-only API access', 'owbn-chronicle-manager'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save API Settings', 'owbn-chronicle-manager')); ?>
        </form>
        <script>
            function owbnGenerateApiKey(fieldId) {
                const field = document.getElementById(fieldId);
                const key = 'cc_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
                field.value = key;
                field.removeAttribute('readonly');
            }
        </script>

        <hr />

        <!-- Status -->
        <h2><?php esc_html_e('Status', 'owbn-chronicle-manager'); ?></h2>
        <table class="widefat" style="max-width:300px;">
            <tr>
                <td><strong>Chronicles</strong></td>
                <td><?php echo get_option('owbn_enable_chronicles', true) ? '✅ Enabled' : '❌ Disabled'; ?></td>
            </tr>
            <tr>
                <td><strong>Coordinators</strong></td>
                <td><?php echo get_option('owbn_enable_coordinators', true) ? '✅ Enabled' : '❌ Disabled'; ?></td>
            </tr>
        </table>

        <hr />

        <!-- Genre List -->
        <h2><?php esc_html_e('Genre List', 'owbn-chronicle-manager'); ?></h2>
        <p class="description"><?php esc_html_e('Used as available genre entries for Chronicles.', 'owbn-chronicle-manager'); ?></p>
        <form method="post">
            <?php wp_nonce_field('save_owbn_genres', 'owbn_genres_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Genres (one per line)', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <textarea name="owbn_genres" rows="12" class="large-text code"><?php echo esc_textarea($genres_text); ?></textarea>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Genres', 'owbn-chronicle-manager')); ?>
        </form>

        <hr />

        <!-- Region List -->
        <h2><?php esc_html_e('Region List', 'owbn-chronicle-manager'); ?></h2>
        <p class="description"><?php esc_html_e('Used to populate the Region dropdown for Chronicles.', 'owbn-chronicle-manager'); ?></p>
        <form method="post">
            <?php wp_nonce_field('save_owbn_regions', 'owbn_regions_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Regions (one per line)', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <textarea name="owbn_regions" rows="12" class="large-text code"><?php echo esc_textarea($regions_text); ?></textarea>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Regions', 'owbn-chronicle-manager')); ?>
        </form>
    </div>
<?php
}
