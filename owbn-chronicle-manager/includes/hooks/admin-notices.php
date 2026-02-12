<?php
/**
 * File: includes/hooks/admin-notices.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.1.0
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
 * Display admin notice for dirty staff user changes on draft posts (generic).
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

// ══════════════════════════════════════════════════════════════════════════════
// PENDING CHANGESET NOTICES
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Display notice after a non-admin submits staff changes on a published post.
 *
 * Triggered by the _pending_notice_ transient set in owbn_save_entity_meta().
 */
add_action('admin_notices', 'owbn_admin_notice_entity_pending_submitted');
function owbn_admin_notice_entity_pending_submitted()
{
    $post_id = owbn_get_current_post_id();
    if (!$post_id) return;

    $post = get_post($post_id);
    if (!$post) return;

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return;

    $entity_key = $config['entity_key'];
    $singular = $config['singular'];

    if (get_transient("owbn_{$entity_key}_pending_notice_{$post_id}")) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong><span class="dashicons dashicons-clock" style="vertical-align: text-bottom;"></span> ';
        printf(
            esc_html__('%s staff changes have been submitted and are pending admin approval.', 'owbn-chronicle-manager'),
            esc_html($singular)
        );
        echo '</strong><br>';
        printf(
            esc_html__('Non-staff field changes were saved immediately. The %s remains published.', 'owbn-chronicle-manager'),
            esc_html(strtolower($singular))
        );
        echo '</p></div>';
        delete_transient("owbn_{$entity_key}_pending_notice_{$post_id}");
    }
}

/**
 * Display pending changeset notice with Approve/Reject buttons for admins.
 *
 * Reads from _owbn_pending_changes post meta (persistent).
 */
add_action('admin_notices', 'owbn_admin_notice_entity_pending_exists');
function owbn_admin_notice_entity_pending_exists()
{
    $post_id = owbn_get_current_post_id();
    if (!$post_id) return;

    if (!owbn_is_admin_user()) return;

    $post = get_post($post_id);
    if (!$post) return;

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return;

    $pending = get_post_meta($post_id, '_owbn_pending_changes', true);
    if (empty($pending) || empty($pending['fields'])) return;

    $entity_key = $config['entity_key'];
    $singular = $config['singular'];

    // Resolve field labels
    $callable = $config['field_definitions'] ?? null;
    $definitions = is_callable($callable) ? call_user_func($callable) : [];
    $field_labels = [];
    foreach ($pending['fields'] as $field_key => $value) {
        foreach ($definitions as $section => $fields) {
            if (isset($fields[$field_key])) {
                $field_labels[$field_key] = $fields[$field_key]['label'];
            }
        }
    }

    $submitted_by = get_userdata($pending['submitted_by'] ?? 0);
    $submitted_name = $submitted_by ? $submitted_by->display_name : __('Unknown user', 'owbn-chronicle-manager');
    $submitted_at = $pending['submitted_at'] ?? '';
    $self_promoted = !empty($pending['self_promoted']);

    echo '<div class="notice notice-warning" style="border-left-color: #d63638;">';
    echo '<p><strong><span class="dashicons dashicons-warning" style="vertical-align: text-bottom;"></span> ';
    printf(
        esc_html__('Pending %s Staff Changes Require Approval', 'owbn-chronicle-manager'),
        esc_html($singular)
    );
    echo '</strong></p>';

    echo '<p>';
    printf(
        esc_html__('Submitted by %s on %s.', 'owbn-chronicle-manager'),
        '<strong>' . esc_html($submitted_name) . '</strong>',
        esc_html($submitted_at)
    );
    if ($self_promoted) {
        echo ' <span style="color: #d63638; font-weight: bold;">';
        echo esc_html__('(Self-promotion detected)', 'owbn-chronicle-manager');
        echo '</span>';
    }
    echo '</p>';

    // Show pending values
    if (!empty($field_labels)) {
        echo '<table class="widefat fixed striped" style="max-width: 600px; margin-bottom: 10px;">';
        echo '<thead><tr><th>' . esc_html__('Field', 'owbn-chronicle-manager') . '</th>';
        echo '<th>' . esc_html__('Pending Value', 'owbn-chronicle-manager') . '</th></tr></thead><tbody>';
        foreach ($pending['fields'] as $field_key => $pval) {
            $label = $field_labels[$field_key] ?? $field_key;
            echo '<tr><td><strong>' . esc_html($label) . '</strong></td><td>';
            if (is_array($pval) && isset($pval['display_name'])) {
                echo esc_html($pval['display_name']);
                if (!empty($pval['display_email'])) {
                    echo ' (' . esc_html($pval['display_email']) . ')';
                }
            } elseif (is_array($pval)) {
                // ast_group — list of users
                $names = array_column($pval, 'display_name');
                echo esc_html(implode(', ', array_filter($names)));
            } else {
                echo esc_html((string) $pval);
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }

    // Approve / Reject form
    echo '<form method="post" style="display:inline-block; margin-bottom: 10px;">';
    wp_nonce_field("owbn_{$entity_key}_pending_action", "owbn_{$entity_key}_pending_nonce");
    echo '<input type="hidden" name="owbn_pending_post_id" value="' . esc_attr($post_id) . '">';
    echo '<button type="submit" name="owbn_pending_action" value="approve" class="button button-primary" style="margin-right: 8px;">';
    echo esc_html__('Approve Changes', 'owbn-chronicle-manager');
    echo '</button>';
    echo '<button type="submit" name="owbn_pending_action" value="reject" class="button">';
    echo esc_html__('Reject Changes', 'owbn-chronicle-manager');
    echo '</button>';
    echo '</form>';

    echo '</div>';
}

/**
 * Handle approve/reject of pending staff changes.
 *
 * Processes the form POST from the pending notice. Admin only.
 */
add_action('admin_init', 'owbn_handle_pending_changeset_action');
function owbn_handle_pending_changeset_action()
{
    if (!isset($_POST['owbn_pending_action']) || !isset($_POST['owbn_pending_post_id'])) {
        return;
    }

    $post_id = intval($_POST['owbn_pending_post_id']);
    $action  = sanitize_text_field($_POST['owbn_pending_action']);

    if (!in_array($action, ['approve', 'reject'], true)) return;
    if (!owbn_is_admin_user()) return;

    $post = get_post($post_id);
    if (!$post) return;

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return;

    $entity_key = $config['entity_key'];

    // Verify nonce
    if (
        !isset($_POST["owbn_{$entity_key}_pending_nonce"]) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST["owbn_{$entity_key}_pending_nonce"])),
            "owbn_{$entity_key}_pending_action"
        )
    ) {
        return;
    }

    $pending = get_post_meta($post_id, '_owbn_pending_changes', true);
    if (empty($pending) || empty($pending['fields'])) return;

    if ($action === 'approve') {
        // Apply each pending field to live post meta
        foreach ($pending['fields'] as $field_key => $field_value) {
            update_post_meta($post_id, $field_key, $field_value);
        }

        // Apply exclusive_fields rules after writing
        $exclusive_fields = $config['exclusive_fields'] ?? [];
        foreach ($exclusive_fields as $rule) {
            $condition_field = $rule['condition'][0] ?? '';
            $condition_value = $rule['condition'][1] ?? '';
            $clear_fields    = $rule['clear'] ?? [];

            if ($condition_field && get_post_meta($post_id, $condition_field, true) === $condition_value) {
                foreach ($clear_fields as $field_to_clear) {
                    delete_post_meta($post_id, $field_to_clear);
                }
            }
        }

        set_transient("owbn_{$entity_key}_pending_approved_{$post_id}", true, 60);
    } else {
        set_transient("owbn_{$entity_key}_pending_rejected_{$post_id}", true, 60);
    }

    // Clear the pending changeset
    delete_post_meta($post_id, '_owbn_pending_changes');

    // Redirect back to the post edit screen
    wp_safe_redirect(get_edit_post_link($post_id, 'raw'));
    exit;
}

/**
 * Show confirmation notice after pending change approval or rejection.
 */
add_action('admin_notices', 'owbn_admin_notice_pending_result');
function owbn_admin_notice_pending_result()
{
    $post_id = owbn_get_current_post_id();
    if (!$post_id) return;

    $post = get_post($post_id);
    if (!$post) return;

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return;

    $entity_key = $config['entity_key'];
    $singular = $config['singular'];

    if (get_transient("owbn_{$entity_key}_pending_approved_{$post_id}")) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>';
        printf(
            esc_html__('%s staff changes have been approved and applied.', 'owbn-chronicle-manager'),
            esc_html($singular)
        );
        echo '</strong></p></div>';
        delete_transient("owbn_{$entity_key}_pending_approved_{$post_id}");
    }

    if (get_transient("owbn_{$entity_key}_pending_rejected_{$post_id}")) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>';
        printf(
            esc_html__('%s staff changes have been rejected. The previous values remain.', 'owbn-chronicle-manager'),
            esc_html($singular)
        );
        echo '</strong></p></div>';
        delete_transient("owbn_{$entity_key}_pending_rejected_{$post_id}");
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════════════════════

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
