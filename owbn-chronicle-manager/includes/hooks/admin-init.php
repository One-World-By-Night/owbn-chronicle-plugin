<?php
/**
 * File: includes/hooks/admin-init.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.0.0
 *
 * Handles:
 * - Flush rewrite rules
 * - Shared capabilities & roles
 * - Genre list init
 * - Region list init
 *
 * Removed in v2 (handled elsewhere):
 * - Feature toggle helpers (replaced by owbn_is_entity_enabled)
 * - require_once chain (handled by main plugin file)
 * - Enctype (handled by owbn_add_entity_enctype in entity-init.php)
 */

if (!defined('ABSPATH')) exit;

// ══════════════════════════════════════════════════════════════════════════════
// FLUSH REWRITE RULES
// ══════════════════════════════════════════════════════════════════════════════

add_action('init', function () {
    if (get_option('owbn_flush_rewrite_rules')) {
        flush_rewrite_rules();
        delete_option('owbn_flush_rewrite_rules');
    }
}, 99);

// ══════════════════════════════════════════════════════════════════════════════
// SHARED CAPABILITIES & ROLES
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Grant admin capabilities for all registered entity types.
 *
 * Derives capabilities from the entity registry so that new entity types
 * automatically get their caps granted to administrator.
 */
function owbn_grant_admin_chronicle_caps()
{
    $role = get_role('administrator');
    if (!$role) return;

    // Shared capability
    $caps = ['ocm_view_list'];

    // Build caps from registered entity types
    foreach (owbn_get_entity_types() as $config) {
        $post_type  = $config['post_type'];
        $base_caps  = $config['capabilities'] ?? [];

        // Add all base capabilities from config
        foreach ($base_caps as $cap) {
            $caps[] = $cap;
        }

        // Add WordPress-standard CPT capabilities
        $caps[] = "edit_{$post_type}";
        $caps[] = "read_{$post_type}";
        $caps[] = "delete_{$post_type}";
        $caps[] = "edit_{$post_type}s";
        $caps[] = "edit_others_{$post_type}s";
        $caps[] = "publish_{$post_type}s";
        $caps[] = "read_private_{$post_type}s";
        $caps[] = "create_{$post_type}s";
    }

    $caps = array_unique($caps);

    foreach ($caps as $cap) {
        if (!$role->has_cap($cap)) {
            $role->add_cap($cap);
        }
    }
}
add_action('admin_init', 'owbn_grant_admin_chronicle_caps');

function owbn_create_custom_roles()
{
    add_role('web_team', 'Web Team', [
        'read' => true,
        'manage_options' => true,
        'ocm_view_list' => true,

        // Chronicle
        'ocm_view_chronicle' => true,
        'ocm_edit_chronicle' => true,
        'ocm_delete_chronicle' => true,
        'ocm_create_chronicle' => true,
        'edit_owbn_chronicle' => true,
        'read_owbn_chronicle' => true,
        'delete_owbn_chronicle' => true,
        'edit_owbn_chronicles' => true,
        'edit_others_owbn_chronicles' => true,
        'publish_owbn_chronicles' => true,
        'read_private_owbn_chronicles' => true,
        'create_owbn_chronicles' => true,

        // Coordinator
        'ocm_create_coordinator' => true,
        'edit_owbn_coordinator' => true,
        'read_owbn_coordinator' => true,
        'delete_owbn_coordinator' => true,
        'edit_owbn_coordinators' => true,
        'edit_others_owbn_coordinators' => true,
        'publish_owbn_coordinators' => true,
        'read_private_owbn_coordinators' => true,
        'create_owbn_coordinators' => true,
    ]);

    add_role('exec_team', 'Exec Team', [
        'read' => true,
        'ocm_view_list' => true,

        // Chronicle
        'ocm_view_chronicle' => true,
        'ocm_edit_chronicle' => true,
        'ocm_delete_chronicle' => true,
        'ocm_create_chronicle' => true,
        'edit_owbn_chronicle' => true,
        'read_owbn_chronicle' => true,
        'delete_owbn_chronicle' => true,
        'edit_owbn_chronicles' => true,
        'edit_others_owbn_chronicles' => true,
        'publish_owbn_chronicles' => true,
        'read_private_owbn_chronicles' => true,
        'create_owbn_chronicles' => true,

        // Coordinator
        'ocm_create_coordinator' => true,
        'edit_owbn_coordinator' => true,
        'read_owbn_coordinator' => true,
        'delete_owbn_coordinator' => true,
        'edit_owbn_coordinators' => true,
        'edit_others_owbn_coordinators' => true,
        'publish_owbn_coordinators' => true,
        'read_private_owbn_coordinators' => true,
        'create_owbn_coordinators' => true,
    ]);

    add_role('chron_staff', 'Chronicle Staff', [
        'read' => true,
        'edit_posts' => true,
        'edit_others_posts' => true,
        'ocm_view_list' => true,
        'ocm_view_chronicle' => true,
        'ocm_edit_chronicle' => true,
        'edit_owbn_chronicle' => true,
        'read_owbn_chronicle' => true,
    ]);

    add_role('coord_staff', 'Coordinator Staff', [
        'read' => true,
        'edit_posts' => true,
        'ocm_view_list' => true,
        'edit_owbn_coordinator' => true,
        'read_owbn_coordinator' => true,
    ]);

    // Clone admin caps to web_team
    $admin_role = get_role('administrator');
    $web_team = get_role('web_team');
    if ($admin_role && $web_team) {
        foreach ($admin_role->capabilities as $cap => $grant) {
            $web_team->add_cap($cap);
        }
    }
}

/**
 * Refresh capabilities on existing custom roles.
 *
 * add_role() is a no-op if the role already exists, so stale roles in the
 * database never receive new capabilities. This function ensures every cap
 * defined in owbn_create_custom_roles() is present on the stored role object.
 *
 * Called from owbn_run_upgrade() on version bumps.
 */
function owbn_refresh_custom_role_caps(): void
{
    $role_caps = [
        'chron_staff' => [
            'read' => true,
            'edit_posts' => true,
            'edit_others_posts' => true,
            'ocm_view_list' => true,
            'ocm_view_chronicle' => true,
            'ocm_edit_chronicle' => true,
            'edit_owbn_chronicle' => true,
            'read_owbn_chronicle' => true,
        ],
        'coord_staff' => [
            'read' => true,
            'edit_posts' => true,
            'ocm_view_list' => true,
            'edit_owbn_coordinator' => true,
            'read_owbn_coordinator' => true,
        ],
    ];

    foreach ($role_caps as $role_slug => $caps) {
        $role = get_role($role_slug);
        if (!$role) continue;

        foreach ($caps as $cap => $grant) {
            if (!$role->has_cap($cap)) {
                $role->add_cap($cap, $grant);
            }
        }
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// GENRE LIST INIT
// ══════════════════════════════════════════════════════════════════════════════

add_action('init', function () {
    if (owbn_is_entity_enabled('owbn_chronicle') && !get_option('owbn_genre_list')) {
        update_option('owbn_genre_list', [
            'Vampire - Anarch',
            'Vampire - Camarilla',
            'Vampire - Sabbat',
            'Vampire - Giovanni',
            'Vampire - Independent',
            'Vampire - Clan Specific',
            'Changeling - Seelie',
            'Changeling - Unseelie',
            'Changing Breeds - Garou',
            'Changing Breeds - Hengeyokai',
            'Changing Breeds - Other',
            'Demon',
            'Hunter',
            'Kuei-Jin',
            'Mage - Traditions',
            'Mage - Technocracy',
            'Wraith',
            'Other',
        ]);
    }
});

// ══════════════════════════════════════════════════════════════════════════════
// REGION LIST INIT
// ══════════════════════════════════════════════════════════════════════════════

add_action('init', function () {
    if (owbn_is_entity_enabled('owbn_chronicle') && !get_option('owbn_region_list')) {
        update_option('owbn_region_list', [
            'Central and West Brazil',
            'Great Lakes',
            'International',
            'Mid Atlantic',
            'Mississippi Valley',
            'New York and New England',
            'Northeast Brazil',
            'Northern California',
            'Southeast',
            'Southeast Brazil',
            'Southern Brazil',
            'Southern CA and Southwest',
        ]);
    }
});
