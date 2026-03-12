<?php
/**
 * Entity Meta Revision Tracking
 *
 * Extends WordPress revisions to include post meta for registered entity
 * types. When a revision is created, all meta fields are copied to the
 * revision post. When a revision is restored, meta is copied back.
 *
 * This provides a full chronological edit history for all entity fields
 * through WordPress's built-in revision comparison UI.
 */

if (!defined('ABSPATH')) exit;

/**
 * Copy entity meta to a revision when it is created.
 *
 * @param int $revision_id The revision post ID.
 */
function owbn_save_entity_revision_meta(int $revision_id): void
{
    $revision = get_post($revision_id);
    if (!$revision || $revision->post_type !== 'revision') return;

    $parent_id = $revision->post_parent;
    $parent    = get_post($parent_id);
    if (!$parent) return;

    $config = owbn_get_entity_config($parent->post_type);
    if (!$config) return;

    $callable = $config['field_definitions'] ?? null;
    if (!is_callable($callable)) return;

    $field_groups = call_user_func($callable);
    foreach ($field_groups as $fields) {
        foreach ($fields as $key => $meta) {
            $type = $meta['type'] ?? 'text';
            if ($type === 'readonly_history') continue;

            $value = get_post_meta($parent_id, $key, true);
            if ($value !== '' && $value !== false) {
                update_metadata('post', $revision_id, $key, $value);
            }
        }
    }
}
add_action('_wp_put_post_revision', 'owbn_save_entity_revision_meta');

/**
 * Restore entity meta from a revision.
 *
 * @param int $post_id     The post being restored to.
 * @param int $revision_id The revision being restored from.
 */
function owbn_restore_entity_revision_meta(int $post_id, int $revision_id): void
{
    $post = get_post($post_id);
    if (!$post) return;

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return;

    $callable = $config['field_definitions'] ?? null;
    if (!is_callable($callable)) return;

    $field_groups = call_user_func($callable);
    foreach ($field_groups as $fields) {
        foreach ($fields as $key => $meta) {
            $type = $meta['type'] ?? 'text';
            if ($type === 'readonly_history') continue;

            $value = get_metadata('post', $revision_id, $key, true);
            if ($value !== '' && $value !== false) {
                update_post_meta($post_id, $key, $value);
            } else {
                delete_post_meta($post_id, $key);
            }
        }
    }
}
add_action('wp_restore_post_revision', 'owbn_restore_entity_revision_meta', 10, 2);

/**
 * Register entity meta fields with the revision diff UI.
 *
 * Adds entity field labels to the revision comparison screen so users
 * can see what changed between revisions.
 *
 * @param array $fields Existing revision fields.
 * @return array Filtered fields.
 */
function owbn_register_revision_diff_fields(array $fields): array
{
    // Determine post type from the revision being viewed.
    $revision_id = isset($_GET['revision']) ? (int) $_GET['revision'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
    if (!$revision_id) {
        $revision_id = isset($_GET['from']) ? (int) $_GET['from'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
    }
    if (!$revision_id) return $fields;

    $revision = get_post($revision_id);
    if (!$revision) return $fields;

    $parent = get_post($revision->post_parent);
    if (!$parent) return $fields;

    $config = owbn_get_entity_config($parent->post_type);
    if (!$config) return $fields;

    $callable = $config['field_definitions'] ?? null;
    if (!is_callable($callable)) return $fields;

    $field_groups = call_user_func($callable);
    foreach ($field_groups as $section_label => $section_fields) {
        foreach ($section_fields as $key => $meta) {
            $type = $meta['type'] ?? 'text';
            if ($type === 'readonly_history') continue;

            $fields[$key] = $meta['label'] ?? $key;
        }
    }

    return $fields;
}
add_filter('_wp_post_revision_fields', 'owbn_register_revision_diff_fields');

/**
 * Provide meta field values for the revision diff display.
 *
 * Serializes complex values to readable strings for comparison.
 *
 * @param string $value      The field value.
 * @param string $field_key  The field key.
 * @param object $revision   The revision post object.
 * @param string $context    The context (from/to).
 * @return string The display value.
 */
function owbn_get_revision_field_value($value, $field_key, $revision, $context)
{
    $meta = get_metadata('post', $revision->ID, $field_key, true);

    if (is_array($meta)) {
        return wp_json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    return is_scalar($meta) ? (string) $meta : '';
}

// Register the value callback for each entity meta key dynamically.
add_action('admin_init', function () {
    foreach (owbn_get_entity_types() as $config) {
        $callable = $config['field_definitions'] ?? null;
        if (!is_callable($callable)) continue;

        $field_groups = call_user_func($callable);
        foreach ($field_groups as $fields) {
            foreach ($fields as $key => $meta) {
                $type = $meta['type'] ?? 'text';
                if ($type === 'readonly_history') continue;

                add_filter("_wp_post_revision_field_{$key}", 'owbn_get_revision_field_value', 10, 4);
            }
        }
    }
});
