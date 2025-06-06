<?php
if (!defined('ABSPATH')) exit;

/**
 * Webhooks
 *
 * @package OWBN Chronicle Manager
 * @since 1.2.1
 * 
 * Update wp-config.php with your secret API key to secure the endpoint.
 * define('OWBN_API_KEY', 'your-secret-key-here');
 */

add_action('rest_api_init', function () {

    // OPTIONS handler for /chronicles (CORS preflight + discoverability)
    register_rest_route('owbn/v1', '/chronicles', [
        'methods' => ['OPTIONS'],
        'callback' => fn() => new WP_REST_Response(null, 204),
        'permission_callback' => '__return_true',
        'show_in_index' => true,
    ]);

    // OPTIONS handler for /chronicle-detail (CORS preflight + discoverability)
    register_rest_route('owbn/v1', '/chronicle-detail', [
        'methods' => ['OPTIONS'],
        'callback' => fn() => new WP_REST_Response(null, 204),
        'permission_callback' => '__return_true',
        'show_in_index' => true,
    ]);

    // POST endpoint for list of chronicles
    register_rest_route('owbn/v1', '/chronicles', [
        'methods' => 'POST',
        'callback' => 'owbn_api_get_chronicles',
        'permission_callback' => 'owbn_api_permission_check',
        'show_in_index' => true,
    ]);

    // POST endpoint for detail of one chronicle by slug
    register_rest_route('owbn/v1', '/chronicle-detail', [
        'methods' => 'POST',
        'callback' => 'owbn_api_get_chronicle_detail',
        'permission_callback' => 'owbn_api_permission_check',
        'show_in_index' => true,
    ]);
});

// CORS support
add_action('rest_api_init', function () {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}, 15);

function owbn_api_permission_check($request) {
    $api_key = $request->get_header('x-api-key');
    $expected_key = defined('OWBN_API_KEY') ? OWBN_API_KEY : 'your-secret-key';

    if (!$api_key || $api_key !== $expected_key) {
        return new WP_Error('unauthorized', 'Invalid or missing API key', ['status' => 403]);
    }

    return true;
}

function owbn_api_get_chronicles($request) {
    $atts = $request->get_json_params();

    if (!is_array($atts)) {
        return new WP_Error('invalid_request', 'Expected JSON body', ['status' => 400]);
    }

    $query = owbn_get_chronicle_query($atts);
    $results = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $results[] = owbn_format_chronicle_data($post_id);
        }
    }

    wp_reset_postdata();
    return rest_ensure_response($results);
}

function owbn_api_get_chronicle_detail($request) {
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
    $all_fields = owbn_get_chronicle_field_definitions();
    $output = [
        'id' => $post_id,
        'title' => wp_kses_post(get_the_title($post_id)),
        'slug' => $slug,
    ];

    foreach ($all_fields as $section => $fields) {
        foreach ($fields as $key => $definition) {
            $value = get_post_meta($post_id, $key, true);

            if (in_array($key, ['hst_info', 'cm_info', 'ast_list', 'admin_contact'], true)) {
                $value = owbn_filter_personnel_list($value);
            } elseif (is_string($value)) {
                $value = wp_kses_post($value);
            }

            $output[$key] = $value ?? '';
        }
    }

    wp_reset_postdata();
    return rest_ensure_response($output);
}

function owbn_format_chronicle_data($post_id) {
    $all_fields = owbn_get_chronicle_field_definitions();
    $output = [
        'id' => $post_id,
        'title' => wp_kses_post(get_the_title($post_id)),
        'slug' => get_post_meta($post_id, 'chronicle_slug', true) ?: null,
    ];

    foreach ($all_fields as $section => $fields) {
        foreach ($fields as $key => $definition) {
            if (!empty($definition['type']) && $definition['type'] === 'wysiwyg') {
                continue; // skip top-level wysiwygs
            }

            $raw_value = get_post_meta($post_id, $key, true);
            $value = null;

            if (in_array($key, ['hst_info', 'cm_info', 'ast_list', 'admin_contact'], true)) {
                $value = owbn_filter_personnel_list($raw_value);
            } elseif (is_array($raw_value)) {
                $value = owbn_strip_wysiwyg_subfields($raw_value, $definition);
            } elseif (is_string($raw_value) && strlen(trim($raw_value)) > 0) {
                $value = wp_kses_post($raw_value);
            }

            $output[$key] = $value ?? null;
        }
    }

    return $output;
}

function owbn_strip_wysiwyg_subfields($value, $definition) {
    if (!is_array($value) || empty($definition['fields'])) {
        return $value;
    }

    $subfields = $definition['fields'];

    // If it's a single group (associative)
    if (owbn_is_associative_array($value)) {
        foreach ($subfields as $subkey => $subdef) {
            if ($subdef['type'] === 'wysiwyg' && array_key_exists($subkey, $value)) {
                unset($value[$subkey]);
            }
        }
        return $value;
    }

    // If it's a list of groups
    foreach ($value as &$entry) {
        if (!is_array($entry)) continue;
        foreach ($subfields as $subkey => $subdef) {
            if ($subdef['type'] === 'wysiwyg' && array_key_exists($subkey, $entry)) {
                unset($entry[$subkey]);
            }
        }
    }

    return $value;
}

function owbn_is_associative_array(array $arr) {
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function owbn_filter_personnel_list($list) {
    if (!is_array($list)) return [];

    if (isset($list['display_name']) || isset($list['display_email'])) {
        $list = [$list];
    }

    $filtered = [];
    foreach ($list as $entry) {
        if (!is_array($entry)) continue;
        $filtered[] = [
            'display_name' => $entry['display_name'] ?? '',
            'display_email' => $entry['display_email'] ?? '',
            'role' => $entry['role'] ?? '',
        ];
    }
    return $filtered;
}