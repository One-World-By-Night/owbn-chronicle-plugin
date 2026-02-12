<?php
/**
 * Generic Entity Validation
 *
 * Config-driven validation for all registered entity types.
 * Replaces per-entity validation files (chronicle-validate.php, coordinator-validate.php)
 * with a single generic implementation driven by entity config.
 *
 * @package OWBN Chronicle Manager
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Validate an entity submission against its field definitions.
 *
 * Looks up the entity config for the given post type and validates every field
 * defined in the config's field_definitions callable. Returns an array of field
 * keys that failed validation (empty array = no errors).
 *
 * @param string $post_type The WordPress post type being validated.
 * @param array  $postarr   The full post data array (typically from wp_insert_post_data filter).
 * @return array List of field keys that failed validation.
 */
function owbn_validate_entity_submission(string $post_type, array $postarr): array
{
    $config = owbn_get_entity_config($post_type);
    if (!$config) {
        return [];
    }

    // Get field definitions from the config callable
    $field_definitions_callable = $config['field_definitions'] ?? null;
    if (!is_callable($field_definitions_callable)) {
        return [];
    }
    $definitions = call_user_func($field_definitions_callable);

    // Get immutable fields from config
    $immutable_fields = $config['immutable_fields'] ?? [];

    $post_id = $postarr['ID'] ?? 0;

    // Normalize all boolean checkbox fields: if type=boolean and not in $postarr, set to '0'
    foreach ($definitions as $fields) {
        foreach ($fields as $key => $meta) {
            if (($meta['type'] ?? '') === 'boolean' && !isset($postarr[$key])) {
                $postarr[$key] = '0';
            }
        }
    }

    $errors = [];

    foreach ($definitions as $fields) {
        foreach ($fields as $key => $meta) {
            $field_type = $meta['type'] ?? '';

            // For immutable fields with an existing DB value, skip validation entirely
            if (in_array($key, $immutable_fields, true) && $post_id) {
                $existing = get_post_meta($post_id, $key, true);
                if (!empty($existing)) {
                    continue;
                }
            }

            $raw = owbn_safe_post_value($key, $postarr);
            $raw_string = is_array($raw) ? '' : $raw;

            // ── Required field check ─────────────────────────────────────
            if (!empty($meta['required']) && (is_array($raw) ? empty($raw) : trim($raw_string) === '')) {
                // Check if value exists in DB (for disabled / immutable fields)
                if ($post_id) {
                    $existing = get_post_meta($post_id, $key, true);
                    if (!empty($existing)) {
                        continue; // Has value in DB, not an error
                    }
                }
                $errors[] = $key;
                continue;
            }

            // ── Slug format + uniqueness validation ──────────────────────
            if ($field_type === 'slug' && !empty($raw_string)) {
                $slug_pattern = $config['slug_pattern'] ?? '/^[a-z0-9-]{2,32}$/';
                if (!preg_match($slug_pattern, strtolower($raw_string))) {
                    $errors[] = $key;
                } else {
                    // Check uniqueness within this post type
                    $existing_query = new WP_Query([
                        'post_type'      => $post_type,
                        'post_status'    => ['publish', 'draft', 'pending'],
                        'meta_key'       => $config['slug_meta_key'],
                        'meta_value'     => strtolower($raw_string),
                        'posts_per_page' => 1,
                        'post__not_in'   => $post_id ? [$post_id] : [],
                        'fields'         => 'ids',
                    ]);
                    if ($existing_query->have_posts()) {
                        $errors[] = $key;
                    }
                }
            }

            // ── user_info validation ─────────────────────────────────────
            if ($field_type === 'user_info') {
                $user_info = is_array($raw) ? $raw : [];

                // If no data submitted, check DB
                if (empty($user_info) && $post_id) {
                    $user_info = get_post_meta($post_id, $key, true);
                    $user_info = is_array($user_info) ? $user_info : [];
                }

                $user          = trim($user_info['user'] ?? '');
                $display_name  = trim($user_info['display_name'] ?? '');
                $display_email = trim($user_info['display_email'] ?? '');

                $is_required = !empty($meta['required']);

                // Check conditional required
                if (!$is_required && isset($meta['conditional_required'])) {
                    [$dep_key, $dep_value] = explode('=', $meta['conditional_required']);
                    $actual    = owbn_safe_post_value($dep_key, $postarr);
                    $actual    = is_array($actual) ? '' : trim($actual);
                    $dep_value = trim($dep_value);

                    if ((string) $actual === $dep_value) {
                        $is_required = true;
                    }
                }

                if ($is_required && ($user === '' || $display_name === '' || $display_email === '')) {
                    $errors[] = $key;
                }
            }

            // ── Select validation ────────────────────────────────────────
            if ($field_type === 'select' && $raw === '--Select Option--') {
                $errors[] = $key;
            }
        }
    }

    return $errors;
}

/**
 * Force entity posts to draft status when validation fails.
 *
 * Hooked into wp_insert_post_data so it runs before the post is persisted.
 * If validation errors are found the post_status is downgraded to 'draft'
 * and the error list is stored in a short-lived transient for the admin
 * notice handler to pick up.
 *
 * @param array $data    Slashed, sanitised post data.
 * @param array $postarr Raw post array including meta input.
 * @return array Possibly modified $data with post_status set to 'draft'.
 */
function owbn_force_draft_on_entity_error(array $data, array $postarr): array
{
    // Only act on registered entity post types
    $config = owbn_get_entity_config($data['post_type'] ?? '');
    if (!$config) {
        return $data;
    }

    $entity_key = $config['entity_key'];

    // Allow trashing or auto-draft
    if (isset($data['post_status']) && in_array($data['post_status'], ['trash', 'auto-draft'], true)) {
        return $data;
    }

    // Skip validation if being deleted
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if (
        (isset($_POST['action']) && $_POST['action'] === 'delete') ||
        (isset($_POST['action2']) && $_POST['action2'] === 'delete')
    ) {
        return $data;
    }

    // Skip if dirty changes already flagged
    if (!empty($postarr['ID']) && get_transient("owbn_{$entity_key}_dirty_notice_{$postarr['ID']}")) {
        return $data;
    }

    $errors = owbn_validate_entity_submission($data['post_type'], $postarr);

    if (!empty($errors)) {
        // Determine original post status from the database
        $original_status = '';
        if (!empty($postarr['ID'])) {
            $original_post = get_post($postarr['ID']);
            $original_status = $original_post ? $original_post->post_status : '';
        }

        // Store error transient for admin notice display
        if (!empty($postarr['ID'])) {
            set_transient("owbn_{$entity_key}_errors_{$postarr['ID']}", $errors, 60);
        }

        if ($original_status === 'publish') {
            // Published post: block save entirely instead of downgrading to draft.
            // The validation_blocked transient signals entity-save.php to skip all meta saves.
            if (!empty($postarr['ID'])) {
                set_transient("owbn_{$entity_key}_validation_blocked_{$postarr['ID']}", true, 60);
            }
            $data['post_status'] = 'publish';
        } else {
            // Draft/new: keep current behavior
            $data['post_status'] = 'draft';
        }
    }

    return $data;
}

// ══════════════════════════════════════════════════════════════════════════════
// HOOK REGISTRATION
// ══════════════════════════════════════════════════════════════════════════════

add_filter('wp_insert_post_data', 'owbn_force_draft_on_entity_error', 10, 2);
