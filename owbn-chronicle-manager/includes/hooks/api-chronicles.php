<?php
if (!defined('ABSPATH')) exit;

/**
 * REST API - Chronicle Handlers
 *
 * @package OWBN Chronicle Manager
 * @since 1.5.0
 */

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE API HANDLERS
// ══════════════════════════════════════════════════════════════════════════════

function owbn_api_get_chronicles($request)
{
    if (!owbn_chronicles_enabled()) {
        return new WP_Error('disabled', 'Chronicles feature is disabled', ['status' => 403]);
    }

    $atts = $request->get_json_params();
    if (!is_array($atts)) {
        $atts = [];
    }

    $query = owbn_get_chronicle_query($atts);
    $results = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = owbn_format_chronicle_list_data(get_the_ID());
        }
    }

    wp_reset_postdata();
    return rest_ensure_response($results);
}

function owbn_api_get_chronicle_detail($request)
{
    if (!owbn_chronicles_enabled()) {
        return new WP_Error('disabled', 'Chronicles feature is disabled', ['status' => 403]);
    }

    $body = $request->get_json_params();
    $slug = isset($body['slug']) ? sanitize_text_field($body['slug']) : '';

    if (!$slug) {
        return new WP_Error('missing_slug', 'Slug is required', ['status' => 400]);
    }

    $query = new WP_Query([
        'post_type' => 'owbn_chronicle',
        'post_status' => 'publish',
        'meta_key' => 'chronicle_slug',
        'meta_value' => $slug,
        'posts_per_page' => 1,
    ]);

    if (!$query->have_posts()) {
        return new WP_Error('not_found', 'No chronicle found for this slug', ['status' => 404]);
    }

    $query->the_post();
    $output = owbn_format_chronicle_detail_data(get_the_ID());

    wp_reset_postdata();
    return rest_ensure_response($output);
}

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE FORMATTERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Format chronicle data for LIST endpoint (slim response)
 */
function owbn_format_chronicle_list_data($post_id)
{
    $slug = get_post_meta($post_id, 'chronicle_slug', true) ?: null;

    // Get OOC location
    $ooc_locations_raw = get_post_meta($post_id, 'ooc_locations', true);
    $ooc_location = [
        'country' => '',
        'region'  => '',
        'city'    => '',
        'notes'   => '',
    ];

    if (is_array($ooc_locations_raw) && !empty($ooc_locations_raw)) {
        // Check if single location object (has 'country' key) or array of locations
        if (isset($ooc_locations_raw['country'])) {
            // Single location object
            $ooc_location = [
                'country' => $ooc_locations_raw['country'] ?? '',
                'region'  => $ooc_locations_raw['region'] ?? '',
                'city'    => $ooc_locations_raw['city'] ?? '',
                'notes'   => $ooc_locations_raw['notes'] ?? '',
            ];
        } else {
            // Array of locations - get first
            $first = reset($ooc_locations_raw);
            if (is_array($first)) {
                $ooc_location = [
                    'country' => $first['country'] ?? '',
                    'region'  => $first['region'] ?? '',
                    'city'    => $first['city'] ?? '',
                    'notes'   => $first['notes'] ?? '',
                ];
            }
        }
    }

    // Get parent info
    $parent_raw = get_post_meta($post_id, 'chronicle_parent', true);
    $parent_value = '';
    if (is_numeric($parent_raw) && intval($parent_raw) > 0) {
        $parent_slug = get_post_meta(intval($parent_raw), 'chronicle_slug', true);
        $parent_value = $parent_slug ?: '';
    }

    return [
        'id'                     => $post_id,
        'title'                  => wp_kses_post(get_the_title($post_id)),
        'slug'                   => $slug,
        'chronicle_slug'         => $slug,
        'genres'                 => get_post_meta($post_id, 'genres', true) ?: [],
        'game_type'              => get_post_meta($post_id, 'game_type', true) ?: '',
        'ooc_locations'          => $ooc_location,
        'chronicle_start_date'   => get_post_meta($post_id, 'chronicle_start_date', true) ?: '',
        'chronicle_region'       => get_post_meta($post_id, 'chronicle_region', true) ?: '',
        'chronicle_probationary' => get_post_meta($post_id, 'chronicle_probationary', true) ?: '0',
        'chronicle_satellite'    => get_post_meta($post_id, 'chronicle_satellite', true) ?: '0',
        'chronicle_parent'       => $parent_value,
    ];
}

/**
 * Format chronicle data for DETAIL endpoint (full response)
 */
function owbn_format_chronicle_detail_data($post_id)
{
    $slug = get_post_meta($post_id, 'chronicle_slug', true) ?: null;
    $all_fields = owbn_get_chronicle_field_definitions();

    $output = [
        'id'      => $post_id,
        'title'   => wp_kses_post(get_the_title($post_id)),
        'slug'    => $slug,
        'content' => wp_kses_post(get_post_field('post_content', $post_id)),
    ];

    foreach ($all_fields as $section => $fields) {
        foreach ($fields as $key => $definition) {
            $raw_value = get_post_meta($post_id, $key, true);
            $value = null;

            if ($key === 'chronicle_parent' && is_numeric($raw_value)) {
                $parent_id = intval($raw_value);
                $parent_slug = get_post_meta($parent_id, 'chronicle_slug', true);
                $parent_title = get_the_title($parent_id);

                if ($parent_slug && $parent_title) {
                    $value = "[{$parent_slug}] {$parent_title}";
                } elseif ($parent_title) {
                    $value = $parent_title;
                }

                $output['chronicle_parent_id'] = $parent_id;
            } elseif (in_array($key, ['hst_info', 'cm_info', 'ast_list', 'admin_contact'], true)) {
                $value = owbn_filter_personnel_list($raw_value);
            } elseif ($key === 'document_links') {
                $value = owbn_format_document_links($raw_value);
            } elseif (is_array($raw_value)) {
                $value = owbn_strip_wysiwyg_subfields($raw_value, $definition);
            } elseif (is_string($raw_value) && strlen(trim($raw_value)) > 0) {
                $value = wp_kses_post($raw_value);
            }

            $output[$key] = $value ?? '';
        }
    }

    return $output;
}

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Filter personnel list to exclude actual_email
 */
function owbn_filter_personnel_list($raw_value)
{
    if (!is_array($raw_value)) {
        return $raw_value;
    }

    // Single user_info structure
    if (isset($raw_value['display_name'])) {
        return [
            'display_name'  => $raw_value['display_name'] ?? '',
            'display_email' => $raw_value['display_email'] ?? '',
        ];
    }

    // Array of user_info structures
    $filtered = [];
    foreach ($raw_value as $person) {
        if (!is_array($person)) continue;

        $entry = [
            'display_name'  => $person['display_name'] ?? '',
            'display_email' => $person['display_email'] ?? '',
        ];

        if (isset($person['role'])) {
            $entry['role'] = $person['role'];
        }

        $filtered[] = $entry;
    }

    return $filtered;
}

/**
 * Strip WYSIWYG subfields from nested arrays
 */
function owbn_strip_wysiwyg_subfields($value, $definition)
{
    if (!is_array($value)) return $value;

    $subfields = $definition['fields'] ?? [];

    foreach ($value as &$item) {
        if (!is_array($item)) continue;

        foreach ($subfields as $sub_key => $sub_def) {
            if (($sub_def['type'] ?? '') === 'wysiwyg' && isset($item[$sub_key])) {
                unset($item[$sub_key]);
            }
        }
    }

    return $value;
}
