<?php
/**
 * Plugin Name: OWBN Chronicle Manager
 * Description: Manage OWBN Chronicle information using structured custom post types, shortcodes, and approval workflows.
 * Version: 1.0.2
 * Author: greghacke
 * Author URI: https://www.owbn.net
 * Text Domain: owbn-chronicle-manager
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/One-World-By-Night/owbn-chronicle-plugin
 * GitHub Branch: main
 */

// Hooks and lifecycle
require_once plugin_dir_path(__FILE__) . 'includes/hooks/admin-init.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/activation.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks/save-and-validate.php';

// Core fields + helpers
require_once plugin_dir_path(__FILE__) . 'includes/fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers/countries.php';

// Admin and editor UI
require_once plugin_dir_path(__FILE__) . 'includes/admin/enqueue-scripts.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/options-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/editor/editor-init.php';

// Render field groups
require_once plugin_dir_path(__FILE__) . 'includes/render/render-metabox-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-location-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-session-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render/render-user-fields.php';

// Shortcodes
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/chronicle-shortcode.php';

// Activation / deactivation
register_activation_hook(__FILE__, 'owbn_activate_plugin');
register_deactivation_hook(__FILE__, 'owbn_deactivate_plugin');