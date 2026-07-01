<?php
/**
 * Central "Pending Staff Changes" admin queue.
 *
 * Lists every entity (chronicle / coordinator) that has a pending staff
 * changeset (_owbn_pending_changes) awaiting admin approval, with inline
 * Approve / Reject actions. This is the central counterpart to the per-post
 * notice in admin-notices.php — it reuses the same handler
 * (owbn_handle_pending_changeset_action) so approvals apply meta + sync roles.
 */

defined('ABSPATH') || exit;

/**
 * Summarize a single pending field value for display.
 *
 * @param mixed $pv
 * @return string
 */
function owbn_pending_value_summary($pv)
{
    if (is_array($pv) && isset($pv['display_name'])) {
        return $pv['display_name'] . (!empty($pv['display_email']) ? ' (' . $pv['display_email'] . ')' : '');
    }
    if (is_array($pv)) {
        // ast_group — list of user rows
        $names = array_filter(array_column($pv, 'display_name'));
        return $names ? implode(', ', $names) : '(none)';
    }
    return (string) $pv;
}

/**
 * Count outstanding pending changesets (for the menu badge).
 *
 * @return int
 */
function owbn_pending_changes_count()
{
    global $wpdb;
    return (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta}
         WHERE meta_key = '_owbn_pending_changes'
           AND meta_value NOT IN ('', 'a:0:{}', 'b:0;', 'N;')"
    );
}

/**
 * Render the central pending-changes queue page.
 */
function owbn_render_pending_changes_page()
{
    echo '<div class="wrap"><h1>' . esc_html__('Pending Staff Changes', 'owbn-chronicle-manager') . '</h1>';

    if (!function_exists('owbn_is_admin_user') || !owbn_is_admin_user()) {
        echo '<p>' . esc_html__('You do not have permission to view this page.', 'owbn-chronicle-manager') . '</p></div>';
        return;
    }

    // Result notice after an approve/reject that redirected back here.
    if (!empty($_GET['owbn_pending_done'])) {
        $done = sanitize_text_field(wp_unslash($_GET['owbn_pending_done']));
        $msg  = ($done === 'approve')
            ? __('Changes approved and applied.', 'owbn-chronicle-manager')
            : __('Changes rejected and discarded.', 'owbn-chronicle-manager');
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    }

    // Find every post carrying a pending changeset.
    global $wpdb;
    $post_ids = $wpdb->get_col(
        "SELECT post_id FROM {$wpdb->postmeta}
         WHERE meta_key = '_owbn_pending_changes'
           AND meta_value NOT IN ('', 'a:0:{}', 'b:0;', 'N;')"
    );

    $rows = array();
    foreach ($post_ids as $pid) {
        $pending = get_post_meta((int) $pid, '_owbn_pending_changes', true);
        if (empty($pending) || empty($pending['fields'])) {
            continue;
        }
        $rows[] = array((int) $pid, $pending);
    }

    if (empty($rows)) {
        echo '<p>' . esc_html__('No pending staff changes. Nothing to review.', 'owbn-chronicle-manager') . '</p></div>';
        return;
    }

    echo '<p>' . sprintf(
        esc_html__('%d change set(s) awaiting approval.', 'owbn-chronicle-manager'),
        count($rows)
    ) . '</p>';

    $self_url = admin_url('admin.php?page=owbn-cc-pending');

    foreach ($rows as $row) {
        list($pid, $pending) = $row;

        $post = get_post($pid);
        if (!$post) {
            continue;
        }
        $config = owbn_get_entity_config($post->post_type);
        if (!$config) {
            continue;
        }
        $entity_key = $config['entity_key'];
        $singular   = $config['singular'] ?? __('Entity', 'owbn-chronicle-manager');

        // Resolve field labels from the entity's field definitions.
        $callable    = $config['field_definitions'] ?? null;
        $definitions = is_callable($callable) ? call_user_func($callable) : array();
        $labels      = array();
        foreach ($pending['fields'] as $fk => $unused) {
            foreach ($definitions as $section => $flds) {
                if (isset($flds[$fk])) {
                    $labels[$fk] = $flds[$fk]['label'];
                }
            }
        }

        $by      = get_userdata($pending['submitted_by'] ?? 0);
        $by_name = $by ? ($by->display_name . ' (' . $by->user_email . ')') : __('Unknown user', 'owbn-chronicle-manager');
        $when    = $pending['submitted_at'] ?? '';
        $self    = !empty($pending['self_promoted']);

        echo '<div class="card" style="max-width:820px;margin:0 0 16px;padding:12px 16px;border-left:4px solid #d63638;">';
        echo '<h2 style="margin-top:0;"><a href="' . esc_url(get_edit_post_link($pid)) . '">' . esc_html($post->post_title) . '</a> '
            . '<span style="color:#888;font-weight:normal;font-size:13px;">(' . esc_html($singular) . ')</span></h2>';
        echo '<p style="margin:4px 0;"><small>' . sprintf(
            esc_html__('Submitted by %1$s%2$s', 'owbn-chronicle-manager'),
            '<strong>' . esc_html($by_name) . '</strong>',
            $when ? ' ' . sprintf(esc_html__('on %s', 'owbn-chronicle-manager'), esc_html($when)) : ''
        );
        if ($self) {
            echo ' &mdash; <span style="color:#d63638;font-weight:bold;">' . esc_html__('Self-promotion detected', 'owbn-chronicle-manager') . '</span>';
        }
        echo '</small></p>';

        echo '<table class="widefat striped" style="margin:8px 0;"><thead><tr><th>'
            . esc_html__('Field', 'owbn-chronicle-manager') . '</th><th>'
            . esc_html__('Pending Value', 'owbn-chronicle-manager') . '</th></tr></thead><tbody>';
        foreach ($pending['fields'] as $fk => $pv) {
            $label = $labels[$fk] ?? $fk;
            echo '<tr><td><strong>' . esc_html($label) . '</strong></td><td>'
                . esc_html(owbn_pending_value_summary($pv)) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<form method="post" style="display:inline-block;">';
        wp_nonce_field("owbn_{$entity_key}_pending_action", "owbn_{$entity_key}_pending_nonce");
        echo '<input type="hidden" name="owbn_pending_post_id" value="' . esc_attr($pid) . '">';
        echo '<input type="hidden" name="owbn_pending_redirect" value="' . esc_attr($self_url) . '">';
        echo '<button type="submit" name="owbn_pending_action" value="approve" class="button button-primary" style="margin-right:8px;">'
            . esc_html__('Approve', 'owbn-chronicle-manager') . '</button>';
        echo '<button type="submit" name="owbn_pending_action" value="reject" class="button" '
            . 'onclick="return confirm(\'' . esc_js(__('Reject and discard these changes?', 'owbn-chronicle-manager')) . '\');">'
            . esc_html__('Reject', 'owbn-chronicle-manager') . '</button>';
        echo '</form>';

        echo '</div>';
    }

    echo '</div>';
}
