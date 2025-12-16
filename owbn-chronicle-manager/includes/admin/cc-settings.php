<?php
/** File: includes/admin/cc-settings.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.4.0
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
    register_setting('owbn_cc_settings', 'owbn_chronicles_mode', [
        'type' => 'string',
        'default' => 'local',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('owbn_cc_settings', 'owbn_chronicles_api_key', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('owbn_cc_settings', 'owbn_chronicles_remote_url', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'esc_url_raw'
    ]);
    register_setting('owbn_cc_settings', 'owbn_chronicles_remote_key', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    // Coordinators
    register_setting('owbn_cc_settings', 'owbn_enable_coordinators', [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    register_setting('owbn_cc_settings', 'owbn_coordinators_mode', [
        'type' => 'string',
        'default' => 'local',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('owbn_cc_settings', 'owbn_coordinators_api_key', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('owbn_cc_settings', 'owbn_coordinators_remote_url', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'esc_url_raw'
    ]);
    register_setting('owbn_cc_settings', 'owbn_coordinators_remote_key', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
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

    // Current values
    $chron_enabled    = get_option('owbn_enable_chronicles', false);
    $chron_mode       = get_option('owbn_chronicles_mode', 'local');
    $chron_api_key    = get_option('owbn_chronicles_api_key', '');
    $chron_remote_url = get_option('owbn_chronicles_remote_url', '');
    $chron_remote_key = get_option('owbn_chronicles_remote_key', '');

    $coord_enabled    = get_option('owbn_enable_coordinators', false);
    $coord_mode       = get_option('owbn_coordinators_mode', 'local');
    $coord_api_key    = get_option('owbn_coordinators_api_key', '');
    $coord_remote_url = get_option('owbn_coordinators_remote_url', '');
    $coord_remote_key = get_option('owbn_coordinators_remote_key', '');

    $genre_list  = get_option('owbn_genre_list', []);
    $region_list = get_option('owbn_region_list', []);

    // Generate local API URL
    $local_api_base = rest_url('owbn-cc/v1/');

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('C&C Plugin Settings', 'owbn-chronicle-manager'); ?></h1>

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
                <tr class="owbn-chronicles-options" <?php echo $chron_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Data Source', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="owbn_chronicles_mode" class="owbn-chronicles-mode" value="local" <?php checked($chron_mode, 'local'); ?> />
                                <?php esc_html_e('Local (this site manages chronicles)', 'owbn-chronicle-manager'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="owbn_chronicles_mode" class="owbn-chronicles-mode" value="remote" <?php checked($chron_mode, 'remote'); ?> />
                                <?php esc_html_e('Remote (fetch from another site)', 'owbn-chronicle-manager'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <!-- Local: Show API URL and Key -->
                <tr class="owbn-chronicles-options owbn-chronicles-local" <?php echo ($chron_enabled && $chron_mode === 'local') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API Endpoint', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <code><?php echo esc_html($local_api_base); ?>chronicles</code>
                        <p class="description"><?php esc_html_e('Share this URL with sites that need to fetch chronicle data.', 'owbn-chronicle-manager'); ?></p>
                    </td>
                </tr>
                <tr class="owbn-chronicles-options owbn-chronicles-local" <?php echo ($chron_enabled && $chron_mode === 'local') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API Key', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <input type="text" name="owbn_chronicles_api_key" value="<?php echo esc_attr($chron_api_key); ?>" class="regular-text code" />
                        <button type="button" class="button owbn-generate-key" data-target="owbn_chronicles_api_key"><?php esc_html_e('Generate', 'owbn-chronicle-manager'); ?></button>
                        <p class="description"><?php esc_html_e('Secret key for API authentication. Share with authorized remote sites.', 'owbn-chronicle-manager'); ?></p>
                    </td>
                </tr>
                <!-- Remote: Enter remote URL and Key -->
                <tr class="owbn-chronicles-options owbn-chronicles-remote" <?php echo ($chron_enabled && $chron_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Remote API URL', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <input type="url" name="owbn_chronicles_remote_url" value="<?php echo esc_url($chron_remote_url); ?>" class="regular-text" placeholder="https://example.com/wp-json/owbn-cc/v1/" />
                    </td>
                </tr>
                <tr class="owbn-chronicles-options owbn-chronicles-remote" <?php echo ($chron_enabled && $chron_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Remote API Key', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <input type="text" name="owbn_chronicles_remote_key" value="<?php echo esc_attr($chron_remote_key); ?>" class="regular-text code" />
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
                <tr class="owbn-coordinators-options" <?php echo $coord_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Data Source', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="owbn_coordinators_mode" class="owbn-coordinators-mode" value="local" <?php checked($coord_mode, 'local'); ?> />
                                <?php esc_html_e('Local (this site manages coordinators)', 'owbn-chronicle-manager'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="owbn_coordinators_mode" class="owbn-coordinators-mode" value="remote" <?php checked($coord_mode, 'remote'); ?> />
                                <?php esc_html_e('Remote (fetch from another site)', 'owbn-chronicle-manager'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <!-- Local: Show API URL and Key -->
                <tr class="owbn-coordinators-options owbn-coordinators-local" <?php echo ($coord_enabled && $coord_mode === 'local') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API Endpoint', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <code><?php echo esc_html($local_api_base); ?>coordinators</code>
                        <p class="description"><?php esc_html_e('Share this URL with sites that need to fetch coordinator data.', 'owbn-chronicle-manager'); ?></p>
                    </td>
                </tr>
                <tr class="owbn-coordinators-options owbn-coordinators-local" <?php echo ($coord_enabled && $coord_mode === 'local') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('API Key', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <input type="text" name="owbn_coordinators_api_key" value="<?php echo esc_attr($coord_api_key); ?>" class="regular-text code" />
                        <button type="button" class="button owbn-generate-key" data-target="owbn_coordinators_api_key"><?php esc_html_e('Generate', 'owbn-chronicle-manager'); ?></button>
                        <p class="description"><?php esc_html_e('Secret key for API authentication. Share with authorized remote sites.', 'owbn-chronicle-manager'); ?></p>
                    </td>
                </tr>
                <!-- Remote: Enter remote URL and Key -->
                <tr class="owbn-coordinators-options owbn-coordinators-remote" <?php echo ($coord_enabled && $coord_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Remote API URL', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <input type="url" name="owbn_coordinators_remote_url" value="<?php echo esc_url($coord_remote_url); ?>" class="regular-text" placeholder="https://example.com/wp-json/owbn-cc/v1/" />
                    </td>
                </tr>
                <tr class="owbn-coordinators-options owbn-coordinators-remote" <?php echo ($coord_enabled && $coord_mode === 'remote') ? '' : 'style="display:none;"'; ?>>
                    <th scope="row"><?php esc_html_e('Remote API Key', 'owbn-chronicle-manager'); ?></th>
                    <td>
                        <input type="text" name="owbn_coordinators_remote_key" value="<?php echo esc_attr($coord_remote_key); ?>" class="regular-text code" />
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

    <script>
    jQuery(function($) {
        // Toggle visibility based on enable checkbox
        function toggleOptions(type) {
            var enabled = $('#owbn_enable_' + type).is(':checked');
            var mode = $('input[name="owbn_' + type + '_mode"]:checked').val() || 'local';
            
            $('.owbn-' + type + '-options').toggle(enabled);
            $('.owbn-' + type + '-local').toggle(enabled && mode === 'local');
            $('.owbn-' + type + '-remote').toggle(enabled && mode === 'remote');
        }

        // Enable checkbox change
        $('#owbn_enable_chronicles, #owbn_enable_coordinators').on('change', function() {
            var type = $(this).attr('id').replace('owbn_enable_', '');
            toggleOptions(type);
        });

        // Mode radio change
        $('.owbn-chronicles-mode, .owbn-coordinators-mode').on('change', function() {
            var type = $(this).hasClass('owbn-chronicles-mode') ? 'chronicles' : 'coordinators';
            toggleOptions(type);
        });

        // Generate API key
        $('.owbn-generate-key').on('click', function() {
            var target = $(this).data('target');
            var key = 'owbn_' + Math.random().toString(36).substr(2, 9) + '_' + Math.random().toString(36).substr(2, 9);
            $('input[name="' + target + '"]').val(key);
        });
    });
    </script>
    <?php
}