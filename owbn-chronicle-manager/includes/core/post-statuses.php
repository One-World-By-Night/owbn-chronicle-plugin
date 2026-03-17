<?php
/**
 * Custom Post Statuses
 *
 * Registers the "Decommissioned" status for chronicle and coordinator post types.
 * Behaves identically to Draft (not public, excluded from search), but displays
 * the label "Decommissioned" in the admin instead.
 */

if (!defined('ABSPATH')) exit;

add_action('init', function () {
    register_post_status('decommissioned', [
        'label'                     => _x('Decommissioned', 'post status', 'owbn-chronicle-manager'),
        'public'                    => false,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Decommissioned <span class="count">(%s)</span>',
            'Decommissioned <span class="count">(%s)</span>',
            'owbn-chronicle-manager'
        ),
    ]);
});

// Show "Decommissioned" label in the post list Status column.
add_filter('display_post_states', function (array $states, WP_Post $post): array {
    if ($post->post_status === 'decommissioned') {
        $states['decommissioned'] = __('Decommissioned', 'owbn-chronicle-manager');
    }
    return $states;
}, 10, 2);

// Inject "Decommissioned" into the publish metabox status dropdown (classic editor).
add_action('admin_footer', function () {
    global $post;
    if (!$post) return;

    $post_types = ['owbn_chronicle', 'owbn_coordinator'];
    if (!in_array($post->post_type, $post_types, true)) return;
    ?>
    <script>
    jQuery(function ($) {
        var $select = $('select#post_status');
        if ($select.find('option[value="decommissioned"]').length === 0) {
            $select.append('<option value="decommissioned"><?php echo esc_js(__('Decommissioned', 'owbn-chronicle-manager')); ?></option>');
        }
        if ($('#hidden_post_status').val() === 'decommissioned') {
            $select.val('decommissioned');
            $('#post-status-display').text('<?php echo esc_js(__('Decommissioned', 'owbn-chronicle-manager')); ?>');
        }
    });
    </script>
    <?php
});
