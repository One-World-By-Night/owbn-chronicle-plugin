<?php
/**
 * Generic Entity Validation
 *
 * Config-driven validation for all registered entity types.
 *
 * Two concerns are kept strictly separate:
 *
 *   1. INTEGRITY validation — per-field checks for data correctness
 *      (required fields, slug format/uniqueness, user_info completeness,
 *      select-placeholder). Failures here cause the save path to SKIP the
 *      offending fields individually while every other field still saves.
 *      Never causes an entire save to be silently dropped.
 *
 *   2. COMPLIANCE detection — entity-level policy checks like
 *      required_documents. Compliance gaps NEVER block a save. They are
 *      surfaced persistently via post meta (_owbn_*_compliance_gaps) and
 *      only block draft→publish transitions through the publication gate.
 *
 */

if (!defined('ABSPATH')) exit;

/**
 * Run per-field integrity validation against a submission.
 *
 * Returns a flat list of field keys that failed integrity validation.
 * A field in this list should be SKIPPED during save — its existing DB
 * value (or absence) is preserved and the user is told what went wrong.
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

    $field_definitions_callable = $config['field_definitions'] ?? null;
    if (!is_callable($field_definitions_callable)) {
        return [];
    }
    $definitions = call_user_func($field_definitions_callable);

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

                if (empty($user_info) && $post_id) {
                    $user_info = get_post_meta($post_id, $key, true);
                    $user_info = is_array($user_info) ? $user_info : [];
                }

                $user          = trim($user_info['user'] ?? '');
                $display_name  = trim($user_info['display_name'] ?? '');
                $display_email = trim($user_info['display_email'] ?? '');

                $is_required = !empty($meta['required']);

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

    return array_values(array_unique($errors));
}

/**
 * Detect compliance gaps for an entity (required_documents policy).
 *
 * Compliance gaps are NOT save blockers. They represent policy deficiencies
 * that should be surfaced persistently and fixed at leisure by staff. The
 * only place a compliance gap blocks user action is at draft→publish
 * transition (the publication gate, below).
 *
 * Returns a list of missing required doc titles. Empty array = fully compliant.
 *
 * @param int        $post_id    The post ID to evaluate.
 * @param array|null $doc_links  Optional pre-parsed document_links array. When
 *                               null, reads from post meta (post-save state).
 *                               When provided, evaluates the given array
 *                               (useful during wp_insert_post_data inspection).
 * @return array List of missing required doc titles.
 */
function owbn_detect_entity_compliance_gaps(int $post_id, ?array $doc_links = null): array
{
    if (!$post_id) return [];

    $post = get_post($post_id);
    if (!$post) return [];

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return [];

    $required_docs = $config['required_documents'] ?? [];
    if (empty($required_docs)) return [];

    if ($doc_links === null) {
        $doc_links = get_post_meta($post_id, 'document_links', true);
    }
    if (!is_array($doc_links)) $doc_links = [];

    $satisfied = [];
    foreach ($doc_links as $doc) {
        if (!is_array($doc)) continue;
        $title   = trim((string) ($doc['title'] ?? ''));
        $link    = trim((string) ($doc['link'] ?? ''));
        $file_id = $doc['file_id'] ?? '';
        if ($title !== '' && ($link !== '' || !empty($file_id))) {
            $satisfied[] = $title;
        }
    }

    $gaps = [];
    foreach ($required_docs as $req_title) {
        if (!in_array($req_title, $satisfied, true)) {
            $gaps[] = $req_title;
        }
    }

    return $gaps;
}

/**
 * Recompute compliance gaps for a post and persist them to post meta.
 *
 * Called from the save path after all fields have been written. The meta
 * key is `_owbn_{entity_key}_compliance_gaps`. An empty array means the
 * post is fully compliant — the meta is deleted in that case so queries
 * can use NOT EXISTS cleanly.
 *
 * @param int   $post_id The post ID.
 * @param array $config  Entity config.
 */
function owbn_update_entity_compliance_meta(int $post_id, array $config): void
{
    if (empty($config['required_documents'])) {
        return;
    }
    $entity_key = $config['entity_key'];
    $gaps = owbn_detect_entity_compliance_gaps($post_id);
    $meta_key = "_owbn_{$entity_key}_compliance_gaps";

    if (empty($gaps)) {
        delete_post_meta($post_id, $meta_key);
    } else {
        update_post_meta($post_id, $meta_key, $gaps);
    }
}

/**
 * Force new/draft entity posts to draft status when integrity validation fails.
 *
 * Runs on wp_insert_post_data. Behavior by original status:
 *   - publish → stay publish. Per-field errors stored in a transient so the
 *     save handler skips just those fields. The post row still updates; every
 *     VALID field is saved.
 *   - draft/new → force post_status = 'draft' as before, but still record the
 *     per-field error list so the save handler skips invalid fields only.
 *
 * CRITICAL: this function NEVER sets the legacy `validation_blocked` transient.
 * No code path in entity-save.php should early-return on an entire save.
 *
 * @param array $data    Slashed, sanitised post data.
 * @param array $postarr Raw post array including meta input.
 * @return array Possibly modified $data.
 */
function owbn_force_draft_on_entity_error(array $data, array $postarr): array
{
    $config = owbn_get_entity_config($data['post_type'] ?? '');
    if (!$config) {
        return $data;
    }

    $entity_key = $config['entity_key'];

    // Allow trashing, auto-draft, or decommissioning without field validation
    if (isset($data['post_status']) && in_array($data['post_status'], ['trash', 'auto-draft', 'decommissioned'], true)) {
        return $data;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if (
        (isset($_POST['action']) && $_POST['action'] === 'delete') ||
        (isset($_POST['action2']) && $_POST['action2'] === 'delete')
    ) {
        return $data;
    }

    if (!empty($postarr['ID']) && get_transient("owbn_{$entity_key}_dirty_notice_{$postarr['ID']}")) {
        return $data;
    }

    $errors = owbn_validate_entity_submission($data['post_type'], $postarr);

    // Publication gate — compliance check on draft→publish transitions.
    // If a user is trying to move a non-compliant post to 'publish', bounce it
    // back to 'draft' and record an error so they see why.
    //
    // Exemptions (gate does NOT fire):
    //   - current user has manage_options (site admin override)
    //   - for chronicles, target game_status is Probationary or Satellite
    //     (docs only required once promoted to Full)
    // Compliance gaps are still recorded in post meta either way.
    $publication_gate_errors = [];
    $is_publish_transition = false;
    $original_status = '';
    if (!empty($postarr['ID'])) {
        $original_post = get_post($postarr['ID']);
        $original_status = $original_post ? $original_post->post_status : '';
    }
    $gate_applies =
        ($data['post_status'] ?? '') === 'publish'
        && $original_status !== 'publish'
        && !empty($config['required_documents'])
        && !current_user_can('manage_options');

    if ($gate_applies && ($config['entity_key'] ?? '') === 'chronicle') {
        $target_is_full = empty($postarr['chronicle_probationary']) && empty($postarr['chronicle_satellite']);
        if (!$target_is_full) {
            $gate_applies = false;
        }
    }

    if ($gate_applies) {
        $is_publish_transition = true;
        $doc_links_submitted = owbn_safe_post_value('document_links', $postarr);
        $doc_links_submitted = is_array($doc_links_submitted) ? $doc_links_submitted : null;
        $fake_id = !empty($postarr['ID']) ? (int) $postarr['ID'] : 0;
        // Use a detached evaluation that doesn't require the post_id to load from DB
        $gaps = owbn_evaluate_compliance_from_doc_links(
            $config['required_documents'] ?? [],
            $doc_links_submitted ?? (
                $fake_id ? (get_post_meta($fake_id, 'document_links', true) ?: []) : []
            )
        );
        if (!empty($gaps)) {
            foreach ($gaps as $title) {
                $publication_gate_errors[] = 'publication_gate:' . $title;
            }
        }
    }

    $all_errors_for_notice = array_merge($errors, $publication_gate_errors);

    if (!empty($all_errors_for_notice) && !empty($postarr['ID'])) {
        // Persist per-field error list so save_post can skip those fields AND
        // so the metabox can render them with inline error markers on the next load.
        set_transient("owbn_{$entity_key}_errors_{$postarr['ID']}", $all_errors_for_notice, 120);

        // Persist the user's submitted values for error recovery on re-render.
        $submitted_values = owbn_capture_submitted_values($errors, $postarr);
        if (!empty($submitted_values)) {
            set_transient(
                "owbn_{$entity_key}_submitted_values_{$postarr['ID']}",
                $submitted_values,
                120
            );
        }
    }

    // Downgrade status only in two cases:
    //   (a) non-published post with integrity errors — preserve prior behavior
    //   (b) publication gate failed on a draft→publish attempt — bounce to draft
    if ($is_publish_transition && !empty($publication_gate_errors)) {
        $data['post_status'] = 'draft';
    } elseif (!empty($errors) && $original_status !== 'publish') {
        $data['post_status'] = 'draft';
    }

    return $data;
}

/**
 * Evaluate compliance from a given document_links array against required titles.
 *
 * Pure function — no DB access. Used by the publication gate which needs to
 * evaluate the SUBMITTED doc links, not the DB state.
 *
 * @param array $required_docs
 * @param array $doc_links
 * @return array Missing titles.
 */
function owbn_evaluate_compliance_from_doc_links(array $required_docs, array $doc_links): array
{
    $satisfied = [];
    foreach ($doc_links as $doc) {
        if (!is_array($doc)) continue;
        $title   = trim((string) ($doc['title'] ?? ''));
        $link    = trim((string) ($doc['link'] ?? ''));
        $file_id = $doc['file_id'] ?? '';
        if ($title !== '' && ($link !== '' || !empty($file_id))) {
            $satisfied[] = $title;
        }
    }
    $gaps = [];
    foreach ($required_docs as $req_title) {
        if (!in_array($req_title, $satisfied, true)) {
            $gaps[] = $req_title;
        }
    }
    return $gaps;
}

/**
 * Extract submitted values for errored fields so the metabox can re-render
 * them with the user's typed values instead of stale DB values.
 *
 * @param array $error_field_keys
 * @param array $postarr
 * @return array Map of field_key => submitted value.
 */
function owbn_capture_submitted_values(array $error_field_keys, array $postarr): array
{
    $out = [];
    foreach ($error_field_keys as $key) {
        if (!isset($postarr[$key])) continue;
        $val = $postarr[$key];
        if (is_string($val)) {
            $out[$key] = wp_unslash($val);
        } elseif (is_array($val)) {
            $out[$key] = wp_unslash($val);
        }
    }
    return $out;
}

add_filter('wp_insert_post_data', 'owbn_force_draft_on_entity_error', 10, 2);
