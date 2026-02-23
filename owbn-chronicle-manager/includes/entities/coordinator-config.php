<?php
/**
 * Coordinator Entity Configuration
 *
 * Registers the coordinator entity type with the entity registry.
 * Field definitions are provided by owbn_get_coordinator_field_definitions()
 * in includes/fields.php (unchanged from v1).
 *
 * @package OWBN Chronicle Manager
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

owbn_register_entity_type([
    // Identity (matches existing data)
    'post_type'      => 'owbn_coordinator',
    'slug_meta_key'  => 'coordinator_slug',
    'url_slug'       => 'coordinators',
    'entity_key'     => 'coordinator',

    // Labels
    'singular'       => 'Coordinator',
    'plural'         => 'Coordinators',
    'menu_name'      => 'OWBN Coordinators',
    'menu_icon'      => 'dashicons-groups',
    'menu_position'  => 30,
    'show_in_menu'   => 'owbn-cc',
    'add_new_label'  => 'Add Coordinator',

    // Feature toggle
    'option_enabled' => 'owbn_enable_coordinators',

    // Fields
    'field_definitions' => 'owbn_get_coordinator_field_definitions',

    // Save behavior
    'immutable_fields'  => [],
    'restricted_fields' => ['coordinator_slug'],
    'staff_fields'      => ['coord_info'],

    // Slug pattern for validation
    'slug_pattern' => '/^[a-z0-9-]{2,32}$/',

    // Staff-specific rules
    'exclusive_fields'  => [],

    // Permissions (AccessSchema patterns)
    'access_patterns' => [
        'coordinator/{slug}/coordinator',
        'coordinator/{slug}/sub-coordinator',
    ],

    // API
    'api_key_option'    => 'owbn_coordinators_api_key',
    'list_fields'       => [
        'coordinator_slug', 'coordinator_title', 'coordinator_type',
        'coordinator_appointment', 'coord_info', 'hosting_chronicle',
    ],
    'detail_fields'     => 'all',
    'personnel_fields'  => ['coord_info', 'subcoord_list'],

    // Capabilities
    'capabilities' => [
        'edit_post'     => 'edit_owbn_coordinator',
        'read_post'     => 'read_owbn_coordinator',
        'delete_post'   => 'delete_owbn_coordinator',
        'edit_posts'    => 'ocm_view_list',
        'publish_posts' => 'ocm_create_coordinator',
    ],
]);
