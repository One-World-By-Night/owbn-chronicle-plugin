<?php
/**
 * Plugin Name: OWBN Chronicle & Coordinator Manager
 * Description: Manage OWBN Chronicle & Coordinator information using structured custom post types, shortcodes, and approval workflows.
 * Version: 2.15.1
 * Author: greghacke
 * Author URI: https://www.owbn.net
 * Text Domain: owbn-chronicle-manager
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/One-World-By-Night/owbn-chronicle-plugin
 * GitHub Branch: main
 */

if (!defined('ABSPATH')) exit;

define('OWBN_CM_VERSION', '2.15.1');

require_once plugin_dir_path(__FILE__) . 'includes/core/entity-registry.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/entity-init.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/entity-save.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/entity-validate.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/post-statuses.php';

require_once plugin_dir_path(__FILE__) . 'includes/fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers/countries.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers/sessions.php';

require_once plugin_dir_path(__FILE__) . 'includes/entities/chronicle-config.php';
require_once plugin_dir_path(__FILE__) . 'includes/entities/coordinator-config.php';

require_once plugin_dir_path(__FILE__) . 'includes/hooks/admin-init.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/admin-list-filters.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/admin-notices.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/compliance.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/admin-remove-add.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/entity-history.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/entity-revisions.php';

require_once plugin_dir_path(__FILE__) . 'includes/render/render-metabox-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-location-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-session-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-user-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-links-uploads-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-chronicle-box.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-chronicle-full.php';

require_once plugin_dir_path(__FILE__) . 'includes/admin/menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/cc-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/enqueue-scripts.php';
require_once plugin_dir_path(__FILE__) . 'includes/editor/editor-init.php';

add_action( 'init', function () {
    if ( function_exists( 'owc_asc_register_client' ) ) {
        owc_asc_register_client( 'ccs', 'C&C Schema' );
    }
} );

$elementor_dir = plugin_dir_path(__FILE__) . 'includes/elementor/';
if (is_dir($elementor_dir)) {
    require_once $elementor_dir . 'theme-builder.php';
    require_once $elementor_dir . 'dynamic-tags/tags-loader.php';
    require_once $elementor_dir . 'widgets-loader.php';
}

add_filter('owbn_gateway_data_sources', function ($sources) {
    $sources['chronicle'] = [
        'label'    => 'Chronicles',
        'provider' => 'owbn-chronicle-manager',
        'types'    => ['chronicle', 'coordinator'],
    ];
    return $sources;
});

register_activation_hook(__FILE__, function () {
    add_option('owbn_needs_activation', true);
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_action('init', function () {
    if (get_option('owbn_needs_activation')) {
        delete_option('owbn_needs_activation');

        file_put_contents(
            WP_CONTENT_DIR . '/owbn-activation.log',
            gmdate('c') . " - Activation running on blog ID: " . get_current_blog_id() . "\n",
            FILE_APPEND
        );

        update_option('owbn_flush_rewrite_rules', true);
        owbn_create_custom_roles();
        owbn_grant_admin_chronicle_caps();
    }

    // Always ensure roles exist
    $roles = ['chron_staff', 'web_team', 'exec_team'];
    foreach ($roles as $role) {
        if (!get_role($role)) {
            owbn_create_custom_roles();
            break;
        }
    }

    owbn_grant_admin_chronicle_caps();
}, 5);

add_action('init', function () {
    $current = get_option('owbn_cm_version', '0');
    if (version_compare($current, OWBN_CM_VERSION, '<')) {
        owbn_run_upgrade($current);
        update_option('owbn_cm_version', OWBN_CM_VERSION);
    }
}, 6);

function owbn_run_upgrade(string $from): void
{
    flush_rewrite_rules();

    // v2.1.0: Refresh stale role caps so chron_staff/coord_staff get required capabilities
    if (version_compare($from, '2.1.0', '<')) {
        owbn_refresh_custom_role_caps();
    }

    // v2.8.1: Initialize history snapshots for coordinator and chronicle staff
    if (version_compare($from, '2.8.1', '<')) {
        owbn_init_coordinator_snapshot();
        owbn_init_chronicle_staff_snapshot();
    }

    // v2.15.0: One-time compliance backfill. Grades every existing chronicle
    // against its required_documents config and writes compliance meta so the
    // new admin list column and dashboard widget are populated immediately.
    // Also clears any leftover legacy `validation_blocked` transients from
    // prior-version saves that might still be in flight.
    if (version_compare($from, '2.15.0', '<')) {
        if (function_exists('owbn_compliance_backfill_all')) {
            owbn_compliance_backfill_all();
        }
        // Best-effort cleanup of the legacy kill-switch transient so nobody's
        // next save after upgrade hits the old silent-drop code path.
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '\\_transient\\_owbn\\_%\\_validation\\_blocked\\_%'
                OR option_name LIKE '\\_transient\\_timeout\\_owbn\\_%\\_validation\\_blocked\\_%'"
        );
    }
}
