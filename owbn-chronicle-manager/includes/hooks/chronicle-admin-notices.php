<?php
/** File: includes/hooks/chronicle-admin-notices.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.3.0
 * @author greghacke
 * Function: Chronicle admin notice handlers
 */

if (!defined('ABSPATH')) exit;

/**
 * Display admin notice for invalid fields
 */
add_action('admin_notices', 'owbn_admin_notice_invalid_fields');
function owbn_admin_notice_invalid_fields()
{
    global $post;
    $post_id = owbn_get_current_post_id();

    if (!$post_id) return;

    $errors = get_transient("owbn_chronicle_errors_{$post_id}");
    if (!$errors) return;

    $definitions = owbn_get_chronicle_field_definitions();
    $labels = [];

    foreach ($errors as $error_key) {
        foreach ($definitions as $section => $fields) {
            if (isset($fields[$error_key])) {
                $labels[] = $fields[$error_key]['label'];
            }
        }
    }

    if (!empty($labels)) {
        echo '<div class="notice notice-error owbn-error-notice">';
        echo '<p><strong>' . esc_html__('Please fix the following required fields:', 'owbn-chronicle-manager') . '</strong></p>';
        echo '<ul>';
        foreach ($labels as $label) {
            echo '<li>' . esc_html($label) . '</li>';
        }
        echo '</ul></div>';
    }

    delete_transient("owbn_chronicle_errors_{$post_id}");
}

/**
 * Display admin notice for dirty user changes
 */
add_action('admin_notices', 'owbn_admin_notice_dirty_user_change');
function owbn_admin_notice_dirty_user_change()
{
    $post_id = owbn_get_current_post_id();

    if (!$post_id) return;

    if (get_transient("owbn_chronicle_dirty_notice_{$post_id}")) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong><span class="dashicons dashicons-lock"></span> ';
        echo esc_html__('Chronicle Staff changes require authentication from Exec or Web Teams.', 'owbn-chronicle-manager');
        echo '<br>';
        echo esc_html__('Upon validation, this change will be published.', 'owbn-chronicle-manager');
        echo '</strong></p></div>';
        delete_transient("owbn_chronicle_dirty_notice_{$post_id}");
    }
}

/**
 * Helper to get current post ID from various sources
 */
function owbn_get_current_post_id()
{
    global $post;

    if (isset($post->ID)) {
        return $post->ID;
    }
    if (!empty($_GET['post'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return intval($_GET['post']);
    }
    if (!empty($_POST['post_ID'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return intval($_POST['post_ID']);
    }

    return 0;
}