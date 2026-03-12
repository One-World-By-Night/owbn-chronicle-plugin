<?php
/**
 * Entity History Tracking
 *
 * Auto-archives outgoing staff when user_info fields change.
 * Stores history as serialized arrays in readonly_history meta fields.
 * Newest entries first (reverse chronological).
 */

if (!defined('ABSPATH')) exit;

/**
 * Archive the outgoing coordinator when coord_info user changes.
 *
 * Fires on save_post for owbn_coordinator posts. Compares the previous
 * coord_info user ID with the new one; if different and the previous
 * holder had data, prepends a history entry to coordinator_history.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function owbn_archive_coordinator_on_change(int $post_id, WP_Post $post): void
{
    if ($post->post_type !== 'owbn_coordinator') return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $previous = get_post_meta($post_id, '_owbn_prev_coord_info', true);
    $current  = get_post_meta($post_id, 'coord_info', true);

    $prev_user = $previous['user'] ?? '';
    $curr_user = $current['user'] ?? '';

    // No change or no previous holder — nothing to archive.
    if (!$prev_user || $prev_user === $curr_user) {
        // Always update the snapshot for next comparison.
        update_post_meta($post_id, '_owbn_prev_coord_info', $current);
        return;
    }

    // Build history entry from the outgoing coordinator.
    $entry = [
        'display_name'    => $previous['display_name'] ?? '',
        'actual_email'    => $previous['actual_email'] ?? '',
        'term_start_date' => get_post_meta($post_id, '_owbn_prev_term_start', true) ?: get_post_meta($post_id, 'term_start_date', true),
        'term_end_date'   => current_time('Y-m-d'),
    ];

    // Prepend to history (newest first).
    $history = get_post_meta($post_id, 'coordinator_history', true);
    if (!is_array($history)) {
        $history = [];
    }
    array_unshift($history, $entry);
    update_post_meta($post_id, 'coordinator_history', $history);

    // Update snapshot for next comparison.
    update_post_meta($post_id, '_owbn_prev_coord_info', $current);
    update_post_meta($post_id, '_owbn_prev_term_start', get_post_meta($post_id, 'term_start_date', true));
}
add_action('save_post', 'owbn_archive_coordinator_on_change', 20, 2);

/**
 * Archive outgoing chronicle staff (HST, CM, ASTs) when they change.
 *
 * Fires on save_post for owbn_chronicle posts. Compares previous
 * snapshots of hst_info, cm_info, and ast_list with current values.
 * Changed staff members are prepended to staff_history.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function owbn_archive_chronicle_staff_on_change(int $post_id, WP_Post $post): void
{
    if ($post->post_type !== 'owbn_chronicle') return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $history = get_post_meta($post_id, 'staff_history', true);
    if (!is_array($history)) {
        $history = [];
    }

    $today   = current_time('Y-m-d');
    $changed = false;

    // Check HST and CM (user_info fields).
    foreach (['hst_info' => 'HST', 'cm_info' => 'CM'] as $field => $role_label) {
        $prev_key = "_owbn_prev_{$field}";
        $previous = get_post_meta($post_id, $prev_key, true);
        $current  = get_post_meta($post_id, $field, true);

        $prev_user = $previous['user'] ?? '';
        $curr_user = $current['user'] ?? '';

        if ($prev_user && $prev_user !== $curr_user) {
            array_unshift($history, [
                'role'         => $role_label,
                'display_name' => $previous['display_name'] ?? '',
                'actual_email' => $previous['actual_email'] ?? '',
                'start_date'   => get_post_meta($post_id, "_owbn_prev_{$field}_start", true) ?: '',
                'end_date'     => $today,
            ]);
            $changed = true;
        }

        update_post_meta($post_id, $prev_key, $current);
        update_post_meta($post_id, "_owbn_prev_{$field}_start", $today);
    }

    // Check AST list (ast_group field).
    $prev_asts = get_post_meta($post_id, '_owbn_prev_ast_list', true);
    $curr_asts = get_post_meta($post_id, 'ast_list', true);

    if (is_array($prev_asts)) {
        $prev_users = array_column($prev_asts, 'user');
        $curr_users = is_array($curr_asts) ? array_column($curr_asts, 'user') : [];

        // Find removed ASTs.
        foreach ($prev_asts as $prev_ast) {
            $prev_ast_user = $prev_ast['user'] ?? '';
            if ($prev_ast_user && !in_array($prev_ast_user, $curr_users, true)) {
                $role_label = !empty($prev_ast['role']) ? 'AST (' . $prev_ast['role'] . ')' : 'AST';
                array_unshift($history, [
                    'role'         => $role_label,
                    'display_name' => $prev_ast['display_name'] ?? '',
                    'actual_email' => $prev_ast['actual_email'] ?? '',
                    'start_date'   => '',
                    'end_date'     => $today,
                ]);
                $changed = true;
            }
        }
    }

    update_post_meta($post_id, '_owbn_prev_ast_list', $curr_asts);

    if ($changed) {
        update_post_meta($post_id, 'staff_history', $history);
    }
}
add_action('save_post', 'owbn_archive_chronicle_staff_on_change', 20, 2);

/**
 * Initialize snapshots on first save or upgrade.
 *
 * Sets _owbn_prev_* meta if not yet present, so the next save
 * can detect changes.
 */
function owbn_init_coordinator_snapshot(): void
{
    $coordinators = get_posts([
        'post_type'      => 'owbn_coordinator',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ]);

    foreach ($coordinators as $post_id) {
        if (!get_post_meta($post_id, '_owbn_prev_coord_info', true)) {
            $current = get_post_meta($post_id, 'coord_info', true);
            if (!empty($current)) {
                update_post_meta($post_id, '_owbn_prev_coord_info', $current);
                update_post_meta($post_id, '_owbn_prev_term_start', get_post_meta($post_id, 'term_start_date', true));
            }
        }
    }
}

function owbn_init_chronicle_staff_snapshot(): void
{
    $chronicles = get_posts([
        'post_type'      => 'owbn_chronicle',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ]);

    foreach ($chronicles as $post_id) {
        foreach (['hst_info', 'cm_info'] as $field) {
            $prev_key = "_owbn_prev_{$field}";
            if (!get_post_meta($post_id, $prev_key, true)) {
                $current = get_post_meta($post_id, $field, true);
                if (!empty($current)) {
                    update_post_meta($post_id, $prev_key, $current);
                }
            }
        }

        if (!get_post_meta($post_id, '_owbn_prev_ast_list', true)) {
            $ast_list = get_post_meta($post_id, 'ast_list', true);
            if (!empty($ast_list)) {
                update_post_meta($post_id, '_owbn_prev_ast_list', $ast_list);
            }
        }
    }
}
