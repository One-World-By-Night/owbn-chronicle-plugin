<?php
if (!defined('ABSPATH')) exit;

/**
 * REST API Core - Routes & Permission
 *
 * @package OWBN Chronicle Manager
 * @since 1.5.0
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

    // Check database option first, fall back to constant for backwards compatibility
    $expected_key = get_option('owbn_api_key_readonly');
    if (!$expected_key && defined('OWBNCC_API_KEY')) {
        $expected_key = OWBNCC_API_KEY;
    }

    if (!$expected_key || !$api_key || $api_key !== $expected_key) {
        return new WP_Error('unauthorized', 'Invalid or missing API key', ['status' => 403]);
    }

    return true;
}

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER ROUTES
// ══════════════════════════════════════════════════════════════════════════════

add_action('rest_api_init', function () {

    // ─── CHRONICLE ROUTES ────────────────────────────────────────────────────

    register_rest_route('owbn-cc/v1', '/chronicles', [
        'methods' => ['OPTIONS'],
        'callback' => fn() => new WP_REST_Response(null, 204),
        'permission_callback' => '__return_true',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn-cc/v1', '/chronicle-detail', [
        'methods' => ['OPTIONS'],
        'callback' => fn() => new WP_REST_Response(null, 204),
        'permission_callback' => '__return_true',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn-cc/v1', '/chronicles', [
        'methods' => 'POST',
        'callback' => 'owbn_api_get_chronicles',
        'permission_callback' => 'owbn_api_permission_check',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn-cc/v1', '/chronicle-detail', [
        'methods' => 'POST',
        'callback' => 'owbn_api_get_chronicle_detail',
        'permission_callback' => 'owbn_api_permission_check',
        'show_in_index' => true,
    ]);

    // ─── COORDINATOR ROUTES ──────────────────────────────────────────────────

    register_rest_route('owbn-cc/v1', '/coordinators', [
        'methods' => ['OPTIONS'],
        'callback' => fn() => new WP_REST_Response(null, 204),
        'permission_callback' => '__return_true',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn-cc/v1', '/coordinator-detail', [
        'methods' => ['OPTIONS'],
        'callback' => fn() => new WP_REST_Response(null, 204),
        'permission_callback' => '__return_true',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn-cc/v1', '/coordinators', [
        'methods' => 'POST',
        'callback' => 'owbn_api_get_coordinators',
        'permission_callback' => 'owbn_api_permission_check',
        'show_in_index' => true,
    ]);

    register_rest_route('owbn-cc/v1', '/coordinator-detail', [
        'methods' => 'POST',
        'callback' => 'owbn_api_get_coordinator_detail',
        'permission_callback' => 'owbn_api_permission_check',
        'show_in_index' => true,
    ]);
});
