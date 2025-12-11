<?php
/**
 * File: includes/hooks/admin-remove-add.php
 * Text Domain: owbn-chronicle-manager
 * @version 1.0.0
 * 
 * Removes "Add New" Chronicle/Coordinator options for non-admins
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
    
    $wp_admin_bar->remove_node('new-owbn_chronicle');
    $wp_admin_bar->remove_node('new-owbn_coordinator');
}, 999);

/**
 * Remove "Add New" submenu items
 */
add_action('admin_menu', function() {
    if (owbn_user_can_create()) return;
    
    remove_submenu_page('edit.php?post_type=owbn_chronicle', 'post-new.php?post_type=owbn_chronicle');
    remove_submenu_page('edit.php?post_type=owbn_coordinator', 'post-new.php?post_type=owbn_coordinator');
}, 999);

/**
 * Hide "Add New" button on list pages via CSS
 */
add_action('admin_head', function() {
    if (owbn_user_can_create()) return;
    
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        return;
    }
    
    echo '<style>.page-title-action { display: none !important; }</style>';
});

/**
 * Block direct access to post-new.php for these types
 */
add_action('load-post-new.php', function() {
    if (owbn_user_can_create()) return;
    
    $post_type = $_GET['post_type'] ?? '';
    if (in_array($post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        wp_die(__('You do not have permission to create new items.', 'owbn-chronicle-manager'));
    }
});