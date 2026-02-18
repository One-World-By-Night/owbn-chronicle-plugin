<?php
/**
 * Plugin Name: OWBN Chronicle & Coordinator Manager
 * Description: Manage OWBN Chronicle & Coordinator information using structured custom post types, shortcodes, and approval workflows.
 * Version: 2.2.3
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

define('OWBN_CM_VERSION', '2.2.3');

// ─── Core Engine ─────────────────────────────────────────────────────────────
require_once plugin_dir_path(__FILE__) . 'includes/core/entity-registry.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/entity-init.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/entity-save.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/entity-validate.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/entity-api.php';

// ─── Field Definitions (unchanged) ──────────────────────────────────────────
require_once plugin_dir_path(__FILE__) . 'includes/fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers/countries.php';

// ─── Entity Configs ─────────────────────────────────────────────────────────
require_once plugin_dir_path(__FILE__) . 'includes/entities/chronicle-config.php';
require_once plugin_dir_path(__FILE__) . 'includes/entities/coordinator-config.php';

// ─── Hooks ──────────────────────────────────────────────────────────────────
require_once plugin_dir_path(__FILE__) . 'includes/hooks/admin-init.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/admin-list-filters.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/admin-notices.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/admin-remove-add.php';

// ─── Rendering ──────────────────────────────────────────────────────────────
require_once plugin_dir_path(__FILE__) . 'includes/render/render-metabox-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-location-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-session-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-user-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-links-uploads-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-chronicle-box.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-chronicle-full.php';

// ─── Admin ──────────────────────────────────────────────────────────────────
require_once plugin_dir_path(__FILE__) . 'includes/admin/cc-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/enqueue-scripts.php';
require_once plugin_dir_path(__FILE__) . 'includes/editor/editor-init.php';

// ─── AccessSchema Client ────────────────────────────────────────────────────
require_once plugin_dir_path(__FILE__) . 'includes/accessschema-client/accessSchema-client.php';

// ─── Elementor Integration (optional — only loads if files exist) ───────────
$elementor_dir = plugin_dir_path(__FILE__) . 'includes/elementor/';
if (is_dir($elementor_dir)) {
    require_once $elementor_dir . 'theme-builder.php';
    require_once $elementor_dir . 'dynamic-tags/tags-loader.php';
    require_once $elementor_dir . 'widgets-loader.php';
}

// ─── Activation Hook ─────────────────────────────────────────────────────────
register_activation_hook(__FILE__, function () {
    add_option('owbn_needs_activation', true);
});

// ─── Deactivation Hook ───────────────────────────────────────────────────────
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// ─── Safe Activation + Role Setup ────────────────────────────────────────────
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

// ─── Upgrade Routine ─────────────────────────────────────────────────────────
add_action('init', function () {
    $current = get_option('owbn_cm_version', '0');
    if (version_compare($current, OWBN_CM_VERSION, '<')) {
        owbn_run_upgrade($current);
        update_option('owbn_cm_version', OWBN_CM_VERSION);
    }
}, 6);

function owbn_run_upgrade(string $from): void
{
    // Flush rewrite rules for new API routes
    flush_rewrite_rules();

    // v2.1.0: Refresh stale role caps so chron_staff/coord_staff get required capabilities
    if (version_compare($from, '2.1.0', '<')) {
        owbn_refresh_custom_role_caps();
    }
}
