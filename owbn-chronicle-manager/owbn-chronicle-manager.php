<?php

/**
 * Plugin Name: OWBN Chronicle & Coordinator Manager
 * Description: Manage OWBN Chronicle & Coordinator information using structured custom post types, shortcodes, and approval workflows.
 * Version: 1.5.0
 * Author: greghacke
 * Author URI: https://www.owbn.net
 * Text Domain: owbn-chronicle-manager
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/One-World-By-Night/owbn-chronicle-plugin
 * GitHub Branch: main
 */

// ─── Core Includes ───────────────────────────────────────────────────────────
require_once plugin_dir_path(__FILE__) . 'includes/hooks/admin-init.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/webhooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/api-chronicles.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/api-coordinators.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-chronicle-box.php';
require_once plugin_dir_path(__FILE__) . 'includes/fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/save-and-validate.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers/countries.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/cc-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/enqueue-scripts.php';
require_once plugin_dir_path(__FILE__) . 'includes/editor/editor-init.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-metabox-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-location-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-session-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-user-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-links-uploads-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-chronicle-full.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/chronicles-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/chronicle-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/coordinator-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/coordinators-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/accessschema-client/accessSchema-client.php';
// require_once plugin_dir_path(__FILE__) . 'languages/i18n.php';

// ─── Activation Hook ─────────────────────────────────────────────────────────
register_activation_hook(__FILE__, function () {
    add_option('owbn_needs_activation', true);
});

// ─── Deactivation Hook ───────────────────────────────────────────────────────
register_deactivation_hook(__FILE__, 'owbn_deactivate_plugin');

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

    // Always ensure roles exist (safe to check)
    $roles = ['chron_staff', 'web_team', 'exec_team'];
    foreach ($roles as $role) {
        if (!get_role($role)) {
            owbn_create_custom_roles();
            break;
        }
    }

    owbn_grant_admin_chronicle_caps();

    // Flush rewrite rules once if needed
    if (get_option('owbn_flush_rewrite_rules')) {
        flush_rewrite_rules();
        delete_option('owbn_flush_rewrite_rules');
    }
}, 5);
