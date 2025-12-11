<?php
/** File: includes/hooks/coordinator-admin-notices.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.3.0
 * @author greghacke
 * Function: Coordinator admin notice handlers
 */

if (!defined('ABSPATH')) exit;

/**
 * Display admin notice for coordinator validation errors
 */
add_action('admin_notices', 'owbn_admin_notice_coordinator_errors');
function owbn_admin_notice_coordinator_errors()
{
    global $post;
    
    if (!$post || $post->post_type !== 'owbn_coordinator') return;

    $errors = get_transient("owbn_coordinator_errors_{$post->ID}");
    if (!$errors) return;

    $definitions = owbn_get_coordinator_field_definitions();
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

    delete_transient("owbn_coordinator_errors_{$post->ID}");
}