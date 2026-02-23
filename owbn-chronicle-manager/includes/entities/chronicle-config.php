<?php
/**
 * Chronicle Entity Configuration
 *
 * Registers the chronicle entity type with the entity registry.
 * Field definitions are provided by owbn_get_chronicle_field_definitions()
 * in includes/fields.php (unchanged from v1).
 *
 * @package OWBN Chronicle Manager
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

owbn_register_entity_type([
    // Identity (matches existing data)
    'post_type'      => 'owbn_chronicle',
    'slug_meta_key'  => 'chronicle_slug',
    'url_slug'       => 'chronicles',
    'entity_key'     => 'chronicle',

    // Labels
    'singular'       => 'Chronicle',
    'plural'         => 'Chronicles',
    'menu_name'      => 'OWBN Chronicles',
    'menu_icon'      => 'dashicons-location-alt',
    'menu_position'  => 30,

    // Feature toggle
    'option_enabled' => 'owbn_enable_chronicles',

    // Fields
    'field_definitions' => 'owbn_get_chronicle_field_definitions',

    // Save behavior
    'immutable_fields'  => ['chronicle_slug'],
    'restricted_fields' => ['chronicle_slug', 'chronicle_start_date', 'chronicle_region', 'chronicle_probationary', 'chronicle_satellite', 'chronicle_parent'],
    'staff_fields'      => ['hst_info', 'cm_info'],

    // Slug pattern for validation
    'slug_pattern' => '/^[a-z0-9]{2,8}$/',

    // Staff-specific rules
    'exclusive_fields'  => [
        ['condition' => ['chronicle_satellite', '1'], 'clear' => ['cm_info']],
        ['condition' => ['chronicle_satellite', '0'], 'clear' => ['chronicle_parent']],
    ],

    // Permissions (AccessSchema patterns)
    'access_patterns' => [
        'chronicle/{slug}/cm',
        'chronicle/{slug}/hst',
    ],

    // API
    'api_key_option'    => 'owbn_chronicles_api_key',
    'list_fields'       => [
        'chronicle_slug', 'genres', 'game_type', 'active_player_count',
        'chronicle_region', 'chronicle_probationary', 'chronicle_satellite',
        'chronicle_parent', 'hst_info', 'cm_info', 'ooc_locations',
    ],
    'detail_fields'     => 'all',
    'personnel_fields'  => ['hst_info', 'cm_info', 'ast_list', 'admin_contact'],

    // Capabilities
    'capabilities' => [
        'edit_post'     => 'edit_owbn_chronicle',
        'read_post'     => 'read_owbn_chronicle',
        'delete_post'   => 'delete_owbn_chronicle',
        'edit_posts'    => 'ocm_view_list',
        'publish_posts' => 'ocm_create_chronicle',
    ],
]);
