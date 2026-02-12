<?php
/**
 * Entity REST API Handlers
 *
 * Generic REST API list/detail handlers for all registered entity types.
 * Routes are driven by entity config — no per-type handler code needed.
 *
 * @package OWBN Chronicle Manager
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

// =============================================================================
// CORS SUPPORT
// =============================================================================

/**
 * Send CORS headers for owbn-cc REST API requests only.
 *
 * Hooked to rest_pre_serve_request so headers are only sent for
 * requests actually handled by the REST API, and scoped to our namespace.
 *
 * @param bool             $served  Whether the request has been served.
 * @param WP_REST_Response $result  Response object.
 * @param WP_REST_Request  $request Request object.
 * @return bool
 */
function owbn_api_cors_headers($served, $result, $request)
{
    $route = $request->get_route();
    if (strpos($route, '/owbn-cc/') === 0) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, x-api-key');
    }
    return $served;
}
add_filter('rest_pre_serve_request', 'owbn_api_cors_headers', 15, 3);

// =============================================================================
// PERMISSION CHECK
// =============================================================================

/**
 * Validate API key for an entity type endpoint.
 *
 * Reads the entity_type from the URL route parameter, looks up its config,
 * and checks the x-api-key header against the configured api_key_option.
 *
 * @param WP_REST_Request $request The REST request.
 * @return true|WP_Error True if authorized, WP_Error otherwise.
 */
function owbn_api_permission_check_entity(WP_REST_Request $request)
{
    $entity_type = $request->get_param('entity_type');
    $config = owbn_get_entity_config_by_key($entity_type);

    if (!$config) {
        return new WP_Error(
            'invalid_entity_type',
            sprintf('Unknown entity type: %s', $entity_type),
            ['status' => 404]
        );
    }

    $api_key_option = $config['api_key_option'] ?? '';
    if (!$api_key_option) {
        return new WP_Error('no_api_key_configured', 'No API key configured for this entity type', ['status' => 500]);
    }

    $expected_key = get_option($api_key_option, '');
    $provided_key = $request->get_header('x-api-key');

    if (!$expected_key || !$provided_key || $provided_key !== $expected_key) {
        return new WP_Error('unauthorized', 'Invalid or missing API key', ['status' => 403]);
    }

    return true;
}

// =============================================================================
// LIST HANDLER
// =============================================================================

/**
 * Handle entity list requests.
 *
 * Gets the entity type from the route, checks if enabled, builds a query
 * with optional filters from the JSON body, and returns formatted results.
 *
 * @param WP_REST_Request $request The REST request.
 * @return WP_REST_Response|WP_Error Response with entity list or error.
 */
function owbn_api_get_entity_list(WP_REST_Request $request)
{
    $entity_type = $request->get_param('entity_type');
    $config = owbn_get_entity_config_by_key($entity_type);

    if (!$config) {
        return new WP_Error('invalid_entity_type', sprintf('Unknown entity type: %s', $entity_type), ['status' => 404]);
    }

    if (!owbn_is_entity_enabled($config['post_type'])) {
        return new WP_Error(
            'disabled',
            sprintf('%s feature is disabled', $config['plural'] ?? $config['entity_key']),
            ['status' => 403]
        );
    }

    $atts = $request->get_json_params();
    if (!is_array($atts)) {
        $atts = [];
    }

    $query = owbn_get_entity_query($config, $atts);
    $results = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = owbn_format_entity_data(get_the_ID(), $config, false);
        }
    }

    wp_reset_postdata();
    return rest_ensure_response($results);
}

// =============================================================================
// DETAIL HANDLER
// =============================================================================

/**
 * Handle entity detail requests.
 *
 * Gets the entity type from the route, reads the slug from the JSON body,
 * queries by slug_meta_key, and returns the full formatted entity.
 *
 * @param WP_REST_Request $request The REST request.
 * @return WP_REST_Response|WP_Error Response with entity detail or error.
 */
function owbn_api_get_entity_detail(WP_REST_Request $request)
{
    $entity_type = $request->get_param('entity_type');
    $config = owbn_get_entity_config_by_key($entity_type);

    if (!$config) {
        return new WP_Error('invalid_entity_type', sprintf('Unknown entity type: %s', $entity_type), ['status' => 404]);
    }

    if (!owbn_is_entity_enabled($config['post_type'])) {
        return new WP_Error(
            'disabled',
            sprintf('%s feature is disabled', $config['plural'] ?? $config['entity_key']),
            ['status' => 403]
        );
    }

    $body = $request->get_json_params();
    $slug = isset($body['slug']) ? sanitize_text_field($body['slug']) : '';

    if (!$slug) {
        return new WP_Error('missing_slug', 'Slug is required', ['status' => 400]);
    }

    $query = new WP_Query([
        'post_type'      => $config['post_type'],
        'post_status'    => 'publish',
        'meta_key'       => $config['slug_meta_key'],
        'meta_value'     => $slug,
        'posts_per_page' => 1,
    ]);

    if (!$query->have_posts()) {
        return new WP_Error(
            'not_found',
            sprintf('No %s found for this slug', $config['singular'] ?? $config['entity_key']),
            ['status' => 404]
        );
    }

    $query->the_post();
    $output = owbn_format_entity_data(get_the_ID(), $config, true);

    wp_reset_postdata();
    return rest_ensure_response($output);
}

// =============================================================================
// DATA FORMATTER
// =============================================================================

/**
 * Format entity data for API response.
 *
 * Builds the output array from field definitions and config. For list mode,
 * only includes fields from config's list_fields. For detail mode, includes
 * all fields (or those specified in config's detail_fields).
 *
 * @param int   $post_id The post ID.
 * @param array $config  Entity type configuration.
 * @param bool  $full    True for detail response, false for list response.
 * @return array Formatted entity data.
 */
function owbn_format_entity_data(int $post_id, array $config, bool $full = false): array
{
    $slug_meta_key   = $config['slug_meta_key'];
    $slug            = get_post_meta($post_id, $slug_meta_key, true) ?: null;
    $personnel_fields = $config['personnel_fields'] ?? [];
    $list_fields     = $config['list_fields'] ?? [];
    $detail_fields   = $config['detail_fields'] ?? 'all';

    // Base output
    $output = [
        'id'    => $post_id,
        'title' => wp_kses_post(get_the_title($post_id)),
        'slug'  => $slug,
    ];

    // Include content for detail responses
    if ($full) {
        $output['content'] = wp_kses_post(get_post_field('post_content', $post_id));
    }

    // Get field definitions from config
    $field_definitions_source = $config['field_definitions'] ?? null;
    if (!$field_definitions_source) {
        return $output;
    }

    $all_fields = is_callable($field_definitions_source)
        ? call_user_func($field_definitions_source)
        : [];

    if (empty($all_fields)) {
        return $output;
    }

    // Determine which field keys to include
    if ($full) {
        // Detail: include all fields, or specific detail_fields if configured
        $include_keys = null; // null = include all
        if ($detail_fields !== 'all' && is_array($detail_fields)) {
            $include_keys = $detail_fields;
        }
    } else {
        // List: only include list_fields
        $include_keys = $list_fields;
    }

    // Iterate field definitions (sections -> fields)
    foreach ($all_fields as $section => $fields) {
        foreach ($fields as $key => $definition) {
            // Skip fields not in the include list (if list is set)
            if ($include_keys !== null && !in_array($key, $include_keys, true)) {
                continue;
            }

            $raw_value = get_post_meta($post_id, $key, true);
            $value = null;

            // --- Format based on field type / key ---

            if ($key === 'chronicle_parent' && is_numeric($raw_value) && intval($raw_value) > 0) {
                // Resolve parent chronicle to slug and title
                $parent_id    = intval($raw_value);
                $parent_slug  = get_post_meta($parent_id, 'chronicle_slug', true);
                $parent_title = get_the_title($parent_id);

                $value = $parent_slug ?: '';

                if ($full) {
                    $output['chronicle_parent_id']    = $parent_id;
                    $output['chronicle_parent_title'] = $parent_title ? wp_kses_post($parent_title) : '';
                }
            } elseif (in_array($key, $personnel_fields, true)) {
                // Personnel fields: strip actual_email
                $value = owbn_filter_personnel_list($raw_value);
            } elseif ($key === 'document_links') {
                // Document links: resolve file URLs
                $value = owbn_format_document_links($raw_value);
            } elseif (is_array($raw_value)) {
                // Array values: strip wysiwyg subfields
                $value = owbn_strip_wysiwyg_subfields($raw_value, $definition);
            } elseif (is_string($raw_value) && strlen(trim($raw_value)) > 0) {
                // String values: sanitize HTML
                $value = wp_kses_post($raw_value);
            }

            // Determine default value: array-type fields default to [],
            // everything else defaults to ''
            $type = $definition['type'] ?? '';
            $is_array_field = in_array($type, [
                'session_group',
                'ast_group',
                'user_info',
                'ooc_location',
                'location_group',
                'social_links_group',
                'email_lists_group',
                'player_lists_group',
                'document_links_group',
                'multi_select',
            ], true);

            $output[$key] = $value ?? ($is_array_field ? [] : '');
        }
    }

    return $output;
}

// =============================================================================
// QUERY BUILDER
// =============================================================================

/**
 * Build a WP_Query for an entity type.
 *
 * Constructs base query args from config and applies optional filters
 * from $atts that match known meta keys for the entity.
 *
 * @param array $config Entity type configuration.
 * @param array $atts   Optional query attributes (filters, limit, orderby).
 * @return WP_Query The resulting query.
 */
function owbn_get_entity_query(array $config, array $atts = []): WP_Query
{
    $args = [
        'post_type'      => $config['post_type'],
        'post_status'    => 'publish',
        'posts_per_page' => isset($atts['limit']) ? intval($atts['limit']) : -1,
        'orderby'        => isset($atts['orderby']) ? sanitize_text_field($atts['orderby']) : 'title',
        'order'          => isset($atts['order']) ? sanitize_text_field($atts['order']) : 'ASC',
    ];

    // Get field definitions to know valid meta keys
    $field_definitions_source = $config['field_definitions'] ?? null;
    $valid_meta_keys = [];

    if ($field_definitions_source && is_callable($field_definitions_source)) {
        $all_fields = call_user_func($field_definitions_source);
        foreach ($all_fields as $section => $fields) {
            foreach ($fields as $key => $definition) {
                $valid_meta_keys[] = $key;
            }
        }
    }

    // Also include the slug meta key as a valid filter
    $valid_meta_keys[] = $config['slug_meta_key'];

    // Build meta_query from recognized filter keys in $atts
    $meta_query = [];

    // Known filter mappings: att key -> meta key + compare
    $filter_map = [
        'region' => ['key' => 'chronicle_region', 'compare' => '='],
        'genre'  => ['key' => 'genres', 'compare' => 'LIKE'],
        'slug'   => ['key' => $config['slug_meta_key'], 'compare' => '='],
    ];

    foreach ($filter_map as $att_key => $mapping) {
        if (!empty($atts[$att_key]) && in_array($mapping['key'], $valid_meta_keys, true)) {
            $meta_query[] = [
                'key'     => $mapping['key'],
                'value'   => sanitize_text_field($atts[$att_key]),
                'compare' => $mapping['compare'],
            ];
        }
    }

    // Allow arbitrary meta filters for keys that exist in field definitions
    if (!empty($atts['meta_filters']) && is_array($atts['meta_filters'])) {
        foreach ($atts['meta_filters'] as $meta_key => $meta_value) {
            if (in_array($meta_key, $valid_meta_keys, true)) {
                $meta_query[] = [
                    'key'     => sanitize_text_field($meta_key),
                    'value'   => sanitize_text_field($meta_value),
                    'compare' => '=',
                ];
            }
        }
    }

    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    return new WP_Query($args);
}

// =============================================================================
// CORS PREFLIGHT
// =============================================================================

/**
 * Handle CORS preflight (OPTIONS) requests.
 *
 * Returns a 204 response with explicit CORS headers so that browsers
 * allow the subsequent POST request.
 *
 * @return WP_REST_Response
 */
function owbn_api_cors_preflight()
{
    $response = new WP_REST_Response(null, 204);
    $response->header('Access-Control-Allow-Origin', '*');
    $response->header('Access-Control-Allow-Methods', 'POST, OPTIONS');
    $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, x-api-key');
    return $response;
}

// =============================================================================
// ROUTE REGISTRATION
// =============================================================================

/**
 * Register REST API routes for all entity types.
 *
 * Registers generic routes using entity_type as a URL parameter, plus
 * legacy routes for backward compatibility during the migration period.
 */
add_action('rest_api_init', function () {

    $namespace = 'owbn-cc/v1';

    // -------------------------------------------------------------------------
    // Generic entity routes (registered once, entity_type is a URL param)
    // -------------------------------------------------------------------------

    // OPTIONS - List
    register_rest_route($namespace, '/entities/(?P<entity_type>[a-z0-9_-]+)/list', [
        'methods'             => 'OPTIONS',
        'callback'            => 'owbn_api_cors_preflight',
        'permission_callback' => '__return_true',
    ]);

    // POST - List
    register_rest_route($namespace, '/entities/(?P<entity_type>[a-z0-9_-]+)/list', [
        'methods'             => 'POST',
        'callback'            => 'owbn_api_get_entity_list',
        'permission_callback' => 'owbn_api_permission_check_entity',
    ]);

    // OPTIONS - Detail
    register_rest_route($namespace, '/entities/(?P<entity_type>[a-z0-9_-]+)/detail', [
        'methods'             => 'OPTIONS',
        'callback'            => 'owbn_api_cors_preflight',
        'permission_callback' => '__return_true',
    ]);

    // POST - Detail
    register_rest_route($namespace, '/entities/(?P<entity_type>[a-z0-9_-]+)/detail', [
        'methods'             => 'POST',
        'callback'            => 'owbn_api_get_entity_detail',
        'permission_callback' => 'owbn_api_permission_check_entity',
    ]);

    // -------------------------------------------------------------------------
    // Legacy routes (backward compatibility during transition)
    // -------------------------------------------------------------------------

    $legacy_routes = [
        // Chronicles
        [
            'route'       => '/chronicles',
            'callback'    => 'owbn_api_get_entity_list',
            'entity_type' => 'chronicle',
        ],
        [
            'route'       => '/chronicle-detail',
            'callback'    => 'owbn_api_get_entity_detail',
            'entity_type' => 'chronicle',
        ],
        // Coordinators
        [
            'route'       => '/coordinators',
            'callback'    => 'owbn_api_get_entity_list',
            'entity_type' => 'coordinator',
        ],
        [
            'route'       => '/coordinator-detail',
            'callback'    => 'owbn_api_get_entity_detail',
            'entity_type' => 'coordinator',
        ],
    ];

    foreach ($legacy_routes as $legacy) {
        $entity_type = $legacy['entity_type'];
        $callback    = $legacy['callback'];

        // OPTIONS
        register_rest_route($namespace, $legacy['route'], [
            'methods'             => 'OPTIONS',
            'callback'            => 'owbn_api_cors_preflight',
            'permission_callback' => '__return_true',
        ]);

        // POST - wraps the generic handler with a hardcoded entity_type
        register_rest_route($namespace, $legacy['route'], [
            'methods'             => 'POST',
            'callback'            => function (WP_REST_Request $request) use ($callback, $entity_type) {
                $request->set_param('entity_type', $entity_type);
                return call_user_func($callback, $request);
            },
            'permission_callback' => function (WP_REST_Request $request) use ($entity_type) {
                $request->set_param('entity_type', $entity_type);
                return owbn_api_permission_check_entity($request);
            },
        ]);
    }
});
