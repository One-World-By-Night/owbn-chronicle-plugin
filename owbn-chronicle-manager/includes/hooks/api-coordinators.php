<?php
if (!defined('ABSPATH')) exit;

/**
 * REST API - Coordinator Handlers
 *
 * @package OWBN Chronicle Manager
 * @since 1.5.0
 */

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
            $results[] = owbn_format_coordinator_data(get_the_ID(), false);
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
    $output = owbn_format_coordinator_data(get_the_ID(), true);

    wp_reset_postdata();
    return rest_ensure_response($output);
}

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR QUERY
// ══════════════════════════════════════════════════════════════════════════════

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

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR FORMATTERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Format coordinator data for API response
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

        $player_lists = get_post_meta($post_id, 'player_lists', true);
        $output['player_lists'] = is_array($player_lists) ? $player_lists : [];
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
