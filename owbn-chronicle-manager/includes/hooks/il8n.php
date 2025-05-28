<?php
if (!defined('ABSPATH')) exit;


if (!defined('ABSPATH')) exit; // Exit if accessed directly

function owbn_chronicle_manager_load_textdomain() {
    load_plugin_textdomain(
        'owbn-chronicle-manager',
        false,
        dirname(plugin_basename(__FILE__), 2) . '/languages'
    );
}
add_action('plugins_loaded', 'owbn_chronicle_manager_load_textdomain');