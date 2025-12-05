<?php
if (!defined('ABSPATH')) exit;

// ══════════════════════════════════════════════════════════════════════════════
// FEATURE TOGGLE HELPERS
// ══════════════════════════════════════════════════════════════════════════════

if (!function_exists('owbn_chronicles_enabled')) {
    function owbn_chronicles_enabled()
    {
        return (bool) get_option('owbn_enable_chronicles', true);
    }
}

if (!function_exists('owbn_coordinators_enabled')) {
    function owbn_coordinators_enabled()
    {
        return (bool) get_option('owbn_enable_coordinators', true);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// LOAD FEATURE FILES
// ══════════════════════════════════════════════════════════════════════════════

require_once plugin_dir_path(__FILE__) . 'chronicle-init.php';
require_once plugin_dir_path(__FILE__) . 'coordinator-init.php';

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

function owbn_grant_admin_chronicle_caps()
{
    $role = get_role('administrator');
    if (!$role) return;

    $caps = [
        // Shared
        'ocm_view_list',

        // Chronicle
        'ocm_view_chronicle',
        'ocm_edit_chronicle',
        'ocm_delete_chronicle',
        'ocm_create_chronicle',
        'edit_owbn_chronicle',
        'read_owbn_chronicle',
        'delete_owbn_chronicle',
        'edit_owbn_chronicles',
        'edit_others_owbn_chronicles',
        'publish_owbn_chronicles',
        'read_private_owbn_chronicles',
        'create_owbn_chronicles',

        // Coordinator
        'ocm_create_coordinator',
        'edit_owbn_coordinator',
        'read_owbn_coordinator',
        'delete_owbn_coordinator',
        'edit_owbn_coordinators',
        'edit_others_owbn_coordinators',
        'publish_owbn_coordinators',
        'read_private_owbn_coordinators',
        'create_owbn_coordinators',
    ];

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

// ══════════════════════════════════════════════════════════════════════════════
// GENRE LIST INIT
// ══════════════════════════════════════════════════════════════════════════════

add_action('init', function () {
    if (owbn_chronicles_enabled() && !get_option('owbn_genre_list')) {
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
    if (owbn_chronicles_enabled() && !get_option('owbn_region_list')) {
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

// Enable file uploads in post editor
add_action('post_edit_form_tag', 'owbn_add_enctype_to_edit_form');
function owbn_add_enctype_to_edit_form()
{
    global $post;
    if (in_array($post->post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        echo ' enctype="multipart/form-data"';
    }
}
