<?php
/** File: includes/admin/cc-settings.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.3.0
 * @author greghacke
 * Function: C&C Plugin Settings - Settings > C&C Plugin
 */

if (!defined('ABSPATH')) exit;

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
    // Chronicles
    register_setting('owbn_cc_settings', 'owbn_enable_chronicles', [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);

    // Coordinators
    register_setting('owbn_cc_settings', 'owbn_enable_coordinators', [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);

    // Genre list
    register_setting('owbn_cc_settings', 'owbn_genre_list', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => 'owbn_sanitize_list_option'
    ]);

    // Region list
    register_setting('owbn_cc_settings', 'owbn_region_list', [
        'type' => 'array',
        'default' => [],
        'sanitize_callback' => 'owbn_sanitize_list_option'
    ]);
});

function owbn_sanitize_list_option($input)
{
    if (!is_array($input)) {
        $input = array_filter(array_map('trim', explode("\n", $input)));
    }
    return array_values(array_filter(array_map('sanitize_text_field', $input)));
}

// ══════════════════════════════════════════════════════════════════════════════
// RENDER SETTINGS PAGE
// ══════════════════════════════════════════════════════════════════════════════

function owbn_render_cc_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $chron_enabled = get_option('owbn_enable_chronicles', false);
    $coord_enabled = get_option('owbn_enable_coordinators', false);
    $genre_list    = get_option('owbn_genre_list', []);
    $region_list   = get_option('owbn_region_list', []);

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('C&C Plugin Settings', 'owbn-chronicle-manager'); ?></h1>

        <div class="notice notice-info inline">
            <p>
                <?php esc_html_e('Chronicle and Coordinator data is managed locally on this site and served through the OWBN Client API Gateway under the', 'owbn-chronicle-manager'); ?>
                <code>owbn/v1/</code>
                <?php esc_html_e('namespace. Configure the gateway in the OWBN Client settings.', 'owbn-chronicle-manager'); ?>
            </p>
        </div>

        <?php settings_errors(); ?>

        <form method="post" action="options.php">
            <?php settings_fields('owbn_cc_settings'); ?>

            <!-- CHRONICLES -->
            <h2><?php esc_html_e('Chronicles', 'owbn-chronicle-manager'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="owbn_enable_chronicles" value="0" />
                            <input type="checkbox" name="owbn_enable_chronicles" id="owbn_enable_chronicles" value="1" <?php checked($chron_enabled); ?> />
                            <?php esc_html_e('Enable Chronicle management', 'owbn-chronicle-manager'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <hr />

            <!-- COORDINATORS -->
            <h2><?php esc_html_e('Coordinators', 'owbn-chronicle-manager'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="owbn_enable_coordinators" value="0" />
                            <input type="checkbox" name="owbn_enable_coordinators" id="owbn_enable_coordinators" value="1" <?php checked($coord_enabled); ?> />
                            <?php esc_html_e('Enable Coordinator management', 'owbn-chronicle-manager'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <hr />

            <!-- GENRE LIST -->
            <h2><?php esc_html_e('Genre List', 'owbn-chronicle-manager'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Available Genres', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <textarea name="owbn_genre_list" rows="10" class="large-text code"><?php echo esc_textarea(implode("\n", $genre_list)); ?></textarea>
                        <p class="description"><?php esc_html_e('One genre per line.', 'owbn-chronicle-manager'); ?></p>
                    </td>
                </tr>
            </table>

            <hr />

            <!-- REGION LIST -->
            <h2><?php esc_html_e('Region List', 'owbn-chronicle-manager'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Available Regions', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <textarea name="owbn_region_list" rows="10" class="large-text code"><?php echo esc_textarea(implode("\n", $region_list)); ?></textarea>
                        <p class="description"><?php esc_html_e('One region per line.', 'owbn-chronicle-manager'); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
