<?php
/**
 * OWBN C&C Admin Menu
 *
 * Registers the top-level OWBN C&C menu. Chronicle and Coordinator CPTs
 * attach to this menu via show_in_menu in their entity configs.
 *
 */

defined('ABSPATH') || exit;

add_action('admin_menu', 'owbn_cc_register_admin_menu');

function owbn_cc_register_admin_menu()
{
    // Parent menu uses ocm_view_list so non-admin users with ASC entity roles
    // can access the chronicle/coordinator submenus underneath.
    add_menu_page(
        __('OWBN C&C', 'owbn-chronicle-manager'),
        __('OWBN C&C', 'owbn-chronicle-manager'),
        'ocm_view_list',
        'owbn-cc',
        'owbn_render_cc_settings_page',
        'dashicons-groups',
        30
    );

    // Settings submenu — admin only
    add_submenu_page(
        'owbn-cc',
        __('C&C Settings', 'owbn-chronicle-manager'),
        __('Settings', 'owbn-chronicle-manager'),
        'manage_options',
        'owbn-cc',
        'owbn_render_cc_settings_page'
    );

    // Pending Staff Changes — central approval queue. Shown only to admins
    // (administrator / exec_team / web_team via owbn_is_admin_user()), with a
    // count badge so pending changes can't pile up unseen.
    if (function_exists('owbn_is_admin_user') && owbn_is_admin_user()) {
        $label = __('Pending Changes', 'owbn-chronicle-manager');
        if (function_exists('owbn_pending_changes_count')) {
            $count = owbn_pending_changes_count();
            if ($count > 0) {
                $label .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $count . '</span></span>';
            }
        }
        add_submenu_page(
            'owbn-cc',
            __('Pending Staff Changes', 'owbn-chronicle-manager'),
            $label,
            'read',
            'owbn-cc-pending',
            'owbn_render_pending_changes_page'
        );
    }
}
