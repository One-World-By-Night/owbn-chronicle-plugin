<?php
/**
 * Entity Type Registry
 *
 * Central registry for all entity types (chronicles, coordinators, etc.)
 * All downstream functions look up config by post type or entity key.
 *
 * @package OWBN Chronicle Manager
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Register an entity type configuration.
 *
 * @param array $config Entity type configuration array.
 */
function owbn_register_entity_type(array $config): void
{
    global $owbn_entity_types;

    if (!isset($owbn_entity_types)) {
        $owbn_entity_types = [];
    }

    $required_keys = ['post_type', 'entity_key', 'slug_meta_key', 'singular', 'plural'];
    foreach ($required_keys as $key) {
        if (empty($config[$key])) {
            _doing_it_wrong(__FUNCTION__, sprintf('Entity config missing required key: %s', $key), '2.0.0');
            return;
        }
    }

    $owbn_entity_types[$config['post_type']] = $config;
}

/**
 * Get all registered entity types.
 *
 * @return array Associative array of post_type => config.
 */
function owbn_get_entity_types(): array
{
    global $owbn_entity_types;
    return $owbn_entity_types ?? [];
}

/**
 * Get entity config by post type.
 *
 * @param string $post_type WordPress post type name.
 * @return array|null Config array or null if not found.
 */
function owbn_get_entity_config(string $post_type): ?array
{
    global $owbn_entity_types;
    return $owbn_entity_types[$post_type] ?? null;
}

/**
 * Get entity config by entity key.
 *
 * @param string $entity_key Entity key (e.g., 'chronicle', 'coordinator').
 * @return array|null Config array or null if not found.
 */
function owbn_get_entity_config_by_key(string $entity_key): ?array
{
    global $owbn_entity_types;

    if (!$owbn_entity_types) return null;

    foreach ($owbn_entity_types as $config) {
        if ($config['entity_key'] === $entity_key) {
            return $config;
        }
    }

    return null;
}

/**
 * Check if an entity type is enabled via its option toggle.
 *
 * @param string $post_type WordPress post type name.
 * @return bool
 */
function owbn_is_entity_enabled(string $post_type): bool
{
    $config = owbn_get_entity_config($post_type);
    if (!$config) return false;

    $option = $config['option_enabled'] ?? '';
    if (!$option) return true;

    return (bool) get_option($option, true);
}

/**
 * Get all registered entity post types as a flat array.
 *
 * @return array List of post type names.
 */
function owbn_get_entity_post_types(): array
{
    return array_keys(owbn_get_entity_types());
}

/**
 * Check if a post type is a registered entity.
 *
 * @param string $post_type WordPress post type name.
 * @return bool
 */
function owbn_is_entity_post_type(string $post_type): bool
{
    return owbn_get_entity_config($post_type) !== null;
}
