<?php

defined('ABSPATH') || exit;

/**
 * -----------------------------------------------------------------------------
 * LOAD INSTANCE-SPECIFIC PREFIX BASE
 * File must define: define('ASC_PREFIX', 'YOURPLUGIN');
 * File must define: define('ASC_LABEL', 'Your Plugin Label');
 * This will be transformed into YOURPLUGIN_ASC_ internally
 * Location: accessschema-client/prefix.php
 * -----------------------------------------------------------------------------
 */
$prefix_file = __DIR__ . '/prefix.php';

if (!file_exists($prefix_file)) {
    wp_die(
        esc_html__('accessSchema-client requires a prefix.php file that defines ASC_PREFIX.', 'owbn-chronicle-manager'),
        esc_html__('Missing File: prefix.php', 'owbn-chronicle-manager'),
        ['response' => 500]
    );
}

require_once $prefix_file;

if (!defined('ASC_PREFIX')) {
    wp_die(
        esc_html__('accessSchema-client requires ASC_PREFIX to be defined in prefix.php.', 'owbn-chronicle-manager'),
        esc_html__('Missing Constant: ASC_PREFIX', 'owbn-chronicle-manager'),
        ['response' => 500]
    );
}

if (!defined('ASC_LABEL')) {
    wp_die(
        esc_html__('accessSchema-client requires ASC_LABEL to be defined in prefix.php.', 'owbn-chronicle-manager'),
        esc_html__('Missing Constant: ASC_LABEL', 'owbn-chronicle-manager'),
        ['response' => 500]
    );
}

// Final computed constant prefix: e.g., 'ANOTHERPLUGIN_ASC_'
$prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', ASC_PREFIX)) . '_ASC_';

// Define path-related constants if not already defined
if (!defined($prefix . 'FILE')) {
    define($prefix . 'FILE', __FILE__);
}
if (!defined($prefix . 'DIR')) {
    define($prefix . 'DIR', plugin_dir_path(__FILE__));
}
if (!defined($prefix . 'URL')) {
    define($prefix . 'URL', plugin_dir_url(__FILE__));
}
if (!defined($prefix . 'VERSION')) {
    define($prefix . 'VERSION', '1.0.0');
}
if (!defined($prefix . 'TEXTDOMAIN')) {
    define($prefix . 'TEXTDOMAIN', 'owbn-chronicle-manager');
}
if (!defined($prefix . 'ASSETS_URL')) {
    define($prefix . 'ASSETS_URL', constant($prefix . 'URL') . 'includes/assets/');
}
if (!defined($prefix . 'CSS_URL')) {
    define($prefix . 'CSS_URL', constant($prefix . 'ASSETS_URL') . 'css/');
}
if (!defined($prefix . 'JS_URL')) {
    define($prefix . 'JS_URL', constant($prefix . 'ASSETS_URL') . 'js/');
}

// Bootstrap the plugin/module
require_once constant($prefix . 'DIR') . 'includes/init.php';
