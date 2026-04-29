<?php
/**
 * Coordinator Description Migration & Mirror.
 *
 * Coordinators previously stored their description in `office_description`
 * post meta (rendered via a WYSIWYG field in the metabox). The metabox now
 * hosts wp_editor() for `post_content` in a Description tab — matching the
 * chronicle pattern. External clients (owbn-client widgets/shortcodes) still
 * read `office_description`, so on save we mirror post_content into that
 * meta key to keep the wire format unchanged.
 */

if (!defined('ABSPATH')) exit;

/**
 * One-time migration: copy office_description into post_content for any
 * owbn_coordinator whose post_content is empty. Runs once, then sets a flag.
 */
add_action('admin_init', function () {
    if (get_option('owbn_coordinator_desc_migrated_v1')) return;
    if (!current_user_can('manage_options')) return;

    $posts = get_posts([
        'post_type'      => 'owbn_coordinator',
        'post_status'    => 'any',
        'numberposts'    => -1,
        'fields'         => 'ids',
        'suppress_filters' => true,
    ]);

    foreach ($posts as $post_id) {
        $office  = (string) get_post_meta($post_id, 'office_description', true);
        $content = (string) get_post_field('post_content', $post_id);

        if (trim($office) === '') continue;

        if (trim($content) === '') {
            // Empty post_content → straight copy.
            $merged = $office;
        } elseif (trim($content) === trim($office)) {
            // Already in sync → nothing to do.
            continue;
        } else {
            // Both have distinct content. Concatenate so nothing is silently
            // dropped when the mirror hook later overwrites office_description.
            $merged = $content . "\n\n" . $office;
        }

        wp_update_post([
            'ID'           => $post_id,
            'post_content' => $merged,
        ]);
    }

    update_option('owbn_coordinator_desc_migrated_v1', time(), false);
}, 5);

/**
 * Mirror post_content -> office_description on every coordinator save so
 * external clients reading the legacy meta key keep working.
 */
add_action('save_post_owbn_coordinator', function ($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if ($post->post_status === 'auto-draft') return;

    update_post_meta($post_id, 'office_description', (string) $post->post_content);
}, 20, 2);
