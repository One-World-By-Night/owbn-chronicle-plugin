<?php
if (!defined('ABSPATH')) exit;

/**
 * Webhooks / REST API
 *
 * @package OWBN Chronicle Manager
 * @since 1.2.1
 * 
 * Update wp-config.php with your secret API key to secure the endpoint.
 * define('OWBNCC_API_KEY', 'your-secret-key-here');
 */

// ══════════════════════════════════════════════════════════════════════════════
// CORS SUPPORT
// ══════════════════════════════════════════════════════════════════════════════

add_action('rest_api_init', function () {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, x-api-key');
}, 15);

// ══════════════════════════════════════════════════════════════════════════════
// PERMISSION CHECK
// ══════════════════════════════════════════════════════════════════════════════

function owbn_api_permission_check($request)
{
    $api_key = $request->get_header('x-api-key');
    $expected_key = defined('OWBNCC_API_KEY') ? OWBNCC_API_KEY : 'your-secret-key';

    if (!$api_key || $api_key !== $expected_key) {
        return new WP_Error('unauthorized', 'Invalid or missing API key', ['status' => 403]);
    }

    return true;
}

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER ROUTES
// ══════════════════════════════════════════════════════════════════════════════

add_action('rest_api_init', function () {

    // ─── CHRONICLE ROUTES ────────────────────────────────────────────────────

    register_rest_route('owbn/v1', '/chronicles', [
        'methods' => ['OPTIONS'],
        'callback' => fn() => new WP_REST_Response(null, 204),
        'permission_callback' => '__return_true',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn/v1', '/chronicle-detail', [
        'methods' => ['OPTIONS'],
        'callback' => fn() => new WP_REST_Response(null, 204),
        'permission_callback' => '__return_true',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn/v1', '/chronicles', [
        'methods' => 'POST',
        'callback' => 'owbn_api_get_chronicles',
        'permission_callback' => 'owbn_api_permission_check',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn/v1', '/chronicle-detail', [
        'methods' => 'POST',
        'callback' => 'owbn_api_get_chronicle_detail',
        'permission_callback' => 'owbn_api_permission_check',
        'show_in_index' => true,
    ]);

    // ─── COORDINATOR ROUTES ──────────────────────────────────────────────────

    register_rest_route('owbn/v1', '/coordinators', [
        'methods' => ['OPTIONS'],
        'callback' => fn() => new WP_REST_Response(null, 204),
        'permission_callback' => '__return_true',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn/v1', '/coordinator-detail', [
        'methods' => ['OPTIONS'],
        'callback' => fn() => new WP_REST_Response(null, 204),
        'permission_callback' => '__return_true',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn/v1', '/coordinators', [
        'methods' => 'POST',
        'callback' => 'owbn_api_get_coordinators',
        'permission_callback' => 'owbn_api_permission_check',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn/v1', '/coordinator-detail', [
        'methods' => 'POST',
        'callback' => 'owbn_api_get_coordinator_detail',
        'permission_callback' => 'owbn_api_permission_check',
        'show_in_index' => true,
    ]);
});

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
            $post_id = get_the_ID();
            $results[] = owbn_format_chronicle_list_data($post_id);
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
    $post_id = get_the_ID();

    $output = owbn_format_chronicle_detail_data($post_id);

    wp_reset_postdata();
    return rest_ensure_response($output);
}

/**
 * Format chronicle data for LIST endpoint (slim response)
 */
function owbn_format_chronicle_list_data($post_id)
{
    $slug = get_post_meta($post_id, 'chronicle_slug', true) ?: null;

    // Get first OOC location
    $ooc_locations_raw = get_post_meta($post_id, 'ooc_locations', true);
    $ooc_location = null;
    if (is_array($ooc_locations_raw) && !empty($ooc_locations_raw)) {
        $first = reset($ooc_locations_raw);
        $ooc_location = [
            'country' => $first['country'] ?? '',
            'region'  => $first['region'] ?? '',
            'city'    => $first['city'] ?? '',
            'notes'   => $first['notes'] ?? '',
        ];
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
                } else {
                    $value = null;
                }

                $output['chronicle_parent_id'] = $parent_id;
            } elseif (in_array($key, ['hst_info', 'cm_info', 'ast_list', 'admin_contact'], true)) {
                $value = owbn_filter_personnel_list($raw_value);
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
// COORDINATOR API HANDLERS
// ══════════════════════════════════════════════════════════════════════════════

function owbn_api_get_coordinators($request)
{
    if (!owbn_coordinators_enabled()) {
        return new WP_Error('disabled', 'Coordinators feature is disabled', ['status' => 403]);
    }

    $atts = $request->get_json_params();

    if (!is_array($atts)) {
        $atts = [];
    }

    $query = owbn_get_coordinator_query($atts);
    $results = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $results[] = owbn_format_coordinator_data($post_id, false);
        }
    }

    wp_reset_postdata();
    return rest_ensure_response($results);
}

function owbn_api_get_coordinator_detail($request)
{
    if (!owbn_coordinators_enabled()) {
        return new WP_Error('disabled', 'Coordinators feature is disabled', ['status' => 403]);
    }

    $body = $request->get_json_params();
    $slug = isset($body['slug']) ? sanitize_text_field($body['slug']) : '';

    if (!$slug) {
        return new WP_Error('missing_slug', 'Slug is required', ['status' => 400]);
    }

    $query = new WP_Query([
        'post_type'      => 'owbn_coordinator',
        'post_status'    => 'publish',
        'meta_key'       => 'coordinator_slug',
        'meta_value'     => $slug,
        'posts_per_page' => 1,
    ]);

    if (!$query->have_posts()) {
        return new WP_Error('not_found', 'No coordinator found for this slug', ['status' => 404]);
    }

    $query->the_post();
    $post_id = get_the_ID();

    $output = owbn_format_coordinator_data($post_id, true);

    wp_reset_postdata();
    return rest_ensure_response($output);
}

/**
 * Build a WP_Query for coordinators
 */
function owbn_get_coordinator_query($atts = [])
{
    $args = [
        'post_type'      => 'owbn_coordinator',
        'post_status'    => 'publish',
        'posts_per_page' => isset($atts['limit']) ? intval($atts['limit']) : -1,
        'orderby'        => isset($atts['orderby']) ? sanitize_text_field($atts['orderby']) : 'title',
        'order'          => isset($atts['order']) ? sanitize_text_field($atts['order']) : 'ASC',
    ];

    if (!empty($atts['slug'])) {
        $args['meta_query'][] = [
            'key'   => 'coordinator_slug',
            'value' => sanitize_text_field($atts['slug']),
        ];
    }

    return new WP_Query($args);
}

/**
 * Format coordinator data for API response
 *
 * @param int  $post_id      The coordinator post ID
 * @param bool $include_full Include additional fields for detail endpoint
 */
function owbn_format_coordinator_data($post_id, $include_full = false)
{
    $output = [
        'id'    => $post_id,
        'title' => wp_kses_post(get_the_title($post_id)),
        'slug'  => get_post_meta($post_id, 'coordinator_slug', true) ?: null,
    ];

    // Basic fields (always included)
    $basic_fields = ['coordinator_title', 'term_start_date', 'web_url'];
    foreach ($basic_fields as $field) {
        $value = get_post_meta($post_id, $field, true);
        $output[$field] = is_string($value) && strlen(trim($value)) > 0 ? wp_kses_post($value) : '';
    }

    // Coordinator info - filter to only include display fields
    $coord_info = get_post_meta($post_id, 'coord_info', true);
    $output['coord_info'] = owbn_filter_coordinator_info($coord_info);

    // Content and office_description (always included)
    $output['content'] = wp_kses_post(get_post_field('post_content', $post_id));
    $output['office_description'] = wp_kses_post(get_post_meta($post_id, 'office_description', true));

    // Additional fields only for detail endpoint
    if ($include_full) {
        $subcoord_list = get_post_meta($post_id, 'subcoord_list', true);
        $output['subcoord_list'] = owbn_filter_subcoord_list($subcoord_list);

        $document_links = get_post_meta($post_id, 'document_links', true);
        $output['document_links'] = owbn_format_document_links($document_links);

        $email_lists = get_post_meta($post_id, 'email_lists', true);
        $output['email_lists'] = is_array($email_lists) ? $email_lists : [];
    }

    return $output;
}

/**
 * Filter coordinator info to exclude actual_email
 */
function owbn_filter_coordinator_info($coord_info)
{
    if (!is_array($coord_info)) {
        return [];
    }

    return [
        'display_name'  => $coord_info['display_name'] ?? '',
        'display_email' => $coord_info['display_email'] ?? '',
    ];
}

/**
 * Filter sub-coordinator list to exclude actual_email
 */
function owbn_filter_subcoord_list($subcoord_list)
{
    if (!is_array($subcoord_list)) {
        return [];
    }

    $filtered = [];
    foreach ($subcoord_list as $subcoord) {
        if (!is_array($subcoord)) continue;

        $filtered[] = [
            'display_name'  => $subcoord['display_name'] ?? '',
            'display_email' => $subcoord['display_email'] ?? '',
            'role'          => $subcoord['role'] ?? '',
        ];
    }

    return $filtered;
}

/**
 * Format document links for API response
 */
function owbn_format_document_links($document_links)
{
    if (!is_array($document_links)) {
        return [];
    }

    $formatted = [];
    foreach ($document_links as $doc) {
        if (!is_array($doc)) continue;

        $url = '';
        if (!empty($doc['file_id'])) {
            $url = wp_get_attachment_url($doc['file_id']);
        } elseif (!empty($doc['link'])) {
            $url = $doc['link'];
        }

        $formatted[] = [
            'title'       => $doc['title'] ?? '',
            'url'         => $url,
            'description' => $doc['description'] ?? '',
        ];
    }

    return $formatted;
}

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE HELPER FUNCTIONS
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
