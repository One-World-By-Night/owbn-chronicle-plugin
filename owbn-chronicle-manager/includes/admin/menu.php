<?php
/**
 * OWBN C&C Admin Menu
 *
 * Registers the top-level OWBN C&C menu. Chronicle and Coordinator CPTs
 * attach to this menu via show_in_menu in their entity configs.
 *
 * @package OWBN Chronicle Manager
 * @since 2.4.0
 */

defined('ABSPATH') || exit;

add_action('admin_menu', 'owbn_cc_register_admin_menu');

function owbn_cc_register_admin_menu()
{
    add_menu_page(
        __('OWBN C&C', 'owbn-chronicle-manager'),
        __('OWBN C&C', 'owbn-chronicle-manager'),
        'manage_options',
        'owbn-cc',
        'owbn_render_cc_settings_page',
        'dashicons-groups',
        30
    );

    // Rename the auto-generated first submenu entry (same slug as parent = rename trick)
    add_submenu_page(
        'owbn-cc',
        __('C&C Settings', 'owbn-chronicle-manager'),
        __('Settings', 'owbn-chronicle-manager'),
        'manage_options',
        'owbn-cc',
        'owbn_render_cc_settings_page'
    );
}
