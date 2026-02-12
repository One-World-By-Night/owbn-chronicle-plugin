<?php
/**
 * File: includes/hooks/admin-remove-add.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.0.0
 *
 * Removes "Add New" options for non-admins across all registered entity types.
 */

if (!defined('ABSPATH')) exit;

function owbn_user_can_create()
{
    $user = wp_get_current_user();
    if (!$user->ID) return false;
    return (bool) array_intersect($user->roles, ['administrator', 'exec_team', 'web_team']);
}

/**
 * Remove from admin bar "+ New" menu
 */
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (owbn_user_can_create()) return;
    foreach (owbn_get_entity_post_types() as $post_type) {
        $wp_admin_bar->remove_node("new-{$post_type}");
    }
}, 999);

/**
 * Remove "Add New" submenu items
 */
add_action('admin_menu', function() {
    if (owbn_user_can_create()) return;
    foreach (owbn_get_entity_post_types() as $post_type) {
        remove_submenu_page("edit.php?post_type={$post_type}", "post-new.php?post_type={$post_type}");
    }
}, 999);

/**
 * Hide "Add New" button on list pages via CSS
 */
add_action('admin_head', function() {
    if (owbn_user_can_create()) return;

    $screen = get_current_screen();
    if (!$screen || !owbn_is_entity_post_type($screen->post_type)) return;

    echo '<style>.page-title-action { display: none !important; }</style>';
});

/**
 * Block direct access to post-new.php for these types
 */
add_action('load-post-new.php', function() {
    if (owbn_user_can_create()) return;

    $post_type = $_GET['post_type'] ?? '';
    if (owbn_is_entity_post_type($post_type)) {
        wp_die(__('You do not have permission to create new items.', 'owbn-chronicle-manager'));
    }
});
