<?php
/**
 * File: includes/hooks/admin-notices.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.0.0
 *
 * Generic admin notices for all entity types.
 * Replaces chronicle-admin-notices.php and coordinator-admin-notices.php.
 */

if (!defined('ABSPATH')) exit;

/**
 * Display admin notice for entity validation errors (generic).
 */
add_action('admin_notices', 'owbn_admin_notice_entity_errors');
function owbn_admin_notice_entity_errors()
{
    $post_id = owbn_get_current_post_id();
    if (!$post_id) return;

    $post = get_post($post_id);
    if (!$post) return;

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return;

    $entity_key = $config['entity_key'];
    $errors = get_transient("owbn_{$entity_key}_errors_{$post_id}");
    if (!$errors) return;

    $callable = $config['field_definitions'] ?? null;
    if (!is_callable($callable)) return;
    $definitions = call_user_func($callable);

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

    delete_transient("owbn_{$entity_key}_errors_{$post_id}");
}

/**
 * Display admin notice for dirty staff user changes (generic).
 */
add_action('admin_notices', 'owbn_admin_notice_entity_dirty_change');
function owbn_admin_notice_entity_dirty_change()
{
    $post_id = owbn_get_current_post_id();
    if (!$post_id) return;

    $post = get_post($post_id);
    if (!$post) return;

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return;

    $entity_key = $config['entity_key'];
    $singular = $config['singular'];

    if (get_transient("owbn_{$entity_key}_dirty_notice_{$post_id}")) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong><span class="dashicons dashicons-lock"></span> ';
        printf(
            esc_html__('%s Staff changes require authentication from Exec or Web Teams.', 'owbn-chronicle-manager'),
            esc_html($singular)
        );
        echo '<br>';
        echo esc_html__('Upon validation, this change will be published.', 'owbn-chronicle-manager');
        echo '</strong></p></div>';
        delete_transient("owbn_{$entity_key}_dirty_notice_{$post_id}");
    }
}

/**
 * Helper to get current post ID from various sources.
 */
if (!function_exists('owbn_get_current_post_id')) {
    function owbn_get_current_post_id()
    {
        global $post;
        if (isset($post->ID)) return $post->ID;
        if (!empty($_GET['post'])) return intval($_GET['post']);
        if (!empty($_POST['post_ID'])) return intval($_POST['post_ID']);
        return 0;
    }
}
