<?php
/**
 * File: includes/hooks/admin-list-filters.php
 * Text Domain: owbn-chronicle-manager
 * @version 1.3.0
 * 
 * Uses AccessSchema's existing user_meta cache - no additional API calls
 */

if (!defined('ABSPATH')) exit;

// ══════════════════════════════════════════════════════════════════════════════
// GET CACHED ROLES FROM ACCESSSCHEMA'S USER_META (NO API CALLS)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get user's roles from AccessSchema's existing user_meta cache
 * This NEVER makes API calls - just reads what's already cached
 */
function owbn_get_user_cached_roles($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) return [];
    
    $client_id = defined('ASC_PREFIX') ? strtolower(str_replace('_', '-', ASC_PREFIX)) : 'ccs';
    $cache_key = "{$client_id}_accessschema_cached_roles";
    
    $cached = get_user_meta($user_id, $cache_key, true);
    
    return is_array($cached) ? $cached : [];
}

// ══════════════════════════════════════════════════════════════════════════════
// REPLACE SLOW ACCESSSCHEMA FILTER WITH CACHE-ONLY VERSION
// ══════════════════════════════════════════════════════════════════════════════

add_action('plugins_loaded', function() {
    // Remove the original filter that makes API calls
    remove_filter('user_has_cap', 'asc_hook_user_has_cap_filter', 10);
    
    // Add cache-only replacement
    add_filter('user_has_cap', 'owbn_cached_user_has_cap_filter', 10, 4);
}, 99);

/**
 * Cache-only replacement for asc_hook_user_has_cap_filter
 * Reads from user_meta - NEVER makes API calls
 */
function owbn_cached_user_has_cap_filter($allcaps, $caps, $args, $user)
{
    $requested_cap = $caps[0] ?? null;
    if (!$requested_cap || !$user instanceof WP_User) {
        return $allcaps;
    }
    
    $client_id = defined('ASC_PREFIX') ? strtolower(str_replace('_', '-', ASC_PREFIX)) : 'ccs';
    $mode = get_option("{$client_id}_accessschema_mode", 'remote');
    
    if ($mode === 'none') {
        return $allcaps;
    }
    
    // Get roles from user_meta cache (NO API call)
    $roles = owbn_get_user_cached_roles($user->ID);
    
    // Handle group-level check
    if ($requested_cap === 'asc_has_access_to_group') {
        $group_path = $args[1] ?? null;
        if ($group_path && owbn_user_has_role_access($roles, $group_path)) {
            $allcaps[$requested_cap] = true;
        }
        return $allcaps;
    }
    
    // Capability mapping check
    $role_map = get_option("{$client_id}_capability_map", []);
    if (empty($role_map[$requested_cap])) {
        return $allcaps;
    }
    
    foreach ((array) $role_map[$requested_cap] as $raw_path) {
        $role_path = str_replace('$slug', sanitize_key(get_query_var('slug') ?: ''), $raw_path);
        
        if (owbn_user_has_role_access($roles, $role_path)) {
            $allcaps[$requested_cap] = true;
            break;
        }
    }
    
    return $allcaps;
}

/**
 * Check if user's roles grant access to a path (exact, hierarchical, or wildcard)
 */
function owbn_user_has_role_access($roles, $path)
{
    if (empty($roles) || empty($path)) {
        return false;
    }
    
    // Exact match
    if (in_array($path, $roles, true)) {
        return true;
    }
    
    // Wildcard match
    if (strpos($path, '*') !== false) {
        $regex = '#^' . str_replace('\*', '[^/]*', preg_quote($path, '#')) . '$#';
        foreach ($roles as $role) {
            if (preg_match($regex, $role)) {
                return true;
            }
        }
    }
    
    // Hierarchical match (role is parent of path)
    foreach ($roles as $role) {
        if (strpos($path, $role . '/') === 0) {
            return true;
        }
    }
    
    return false;
}

// ══════════════════════════════════════════════════════════════════════════════
// ACCESSIBLE SLUGS - Extract from cached roles
// ══════════════════════════════════════════════════════════════════════════════

function owbn_get_user_accessible_slugs($post_type)
{
    static $cache = [];
    
    $user = wp_get_current_user();
    if (!$user->ID) return [];
    
    // Admins see everything
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return null;
    }
    
    $cache_key = "{$post_type}_{$user->ID}";
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }
    
    $roles = owbn_get_user_cached_roles($user->ID);
    $slugs = [];
    
    if ($post_type === 'owbn_chronicle') {
        // Pattern: chronicle/{slug}/hst, chronicle/{slug}/cm, chronicle/{slug}/ast
        foreach ($roles as $role) {
            if (preg_match('#^chronicle/([^/]+)/(hst|cm|ast)$#', $role, $m)) {
                $slugs[] = $m[1];
            }
        }
    } elseif ($post_type === 'owbn_coordinator') {
        // Pattern: coordinator/{slug}/coordinator, coordinator/{slug}/sub-coordinator
        foreach ($roles as $role) {
            if (preg_match('#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#', $role, $m)) {
                $slugs[] = $m[1];
            }
        }
    }
    
    $cache[$cache_key] = array_unique($slugs);
    return $cache[$cache_key];
}

// ══════════════════════════════════════════════════════════════════════════════
// PRE_GET_POSTS - Filter admin list to accessible posts only
// ══════════════════════════════════════════════════════════════════════════════

add_action('pre_get_posts', 'owbn_filter_admin_post_list');
function owbn_filter_admin_post_list($query)
{
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $post_type = $query->get('post_type');
    if (!in_array($post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        return;
    }
    
    $accessible_slugs = owbn_get_user_accessible_slugs($post_type);
    
    if ($accessible_slugs === null) {
        return; // Unrestricted
    }
    
    if (empty($accessible_slugs)) {
        $query->set('post__in', [0]);
        return;
    }
    
    $meta_key = ($post_type === 'owbn_chronicle') ? 'chronicle_slug' : 'coordinator_slug';
    $meta_query = $query->get('meta_query') ?: [];
    $meta_query[] = [
        'key'     => $meta_key,
        'value'   => $accessible_slugs,
        'compare' => 'IN',
    ];
    $query->set('meta_query', $meta_query);
}

// ══════════════════════════════════════════════════════════════════════════════
// BYPASS SLOW MAP_META_CAP ON LIST SCREENS
// ══════════════════════════════════════════════════════════════════════════════

global $owbn_list_screen_active;
$owbn_list_screen_active = false;

add_action('current_screen', function($screen) {
    global $owbn_list_screen_active;
    if ($screen->base === 'edit' && in_array($screen->post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        $owbn_list_screen_active = true;
        
        // Remove slow per-post map_meta_cap filters
        remove_filter('map_meta_cap', 'owbn_chronicle_map_meta_cap', 10);
        remove_filter('map_meta_cap', 'owbn_coordinator_map_meta_cap', 10);
    }
});

add_filter('map_meta_cap', 'owbn_list_view_map_meta_cap', 1, 4);
function owbn_list_view_map_meta_cap($caps, $cap, $user_id, $args)
{
    global $owbn_list_screen_active;
    
    if (!$owbn_list_screen_active) {
        return $caps;
    }
    
    if (!in_array($cap, ['edit_post', 'delete_post', 'read_post'], true)) {
        return $caps;
    }
    
    $post_id = !empty($args[0]) ? (int) $args[0] : 0;
    if (!$post_id) return $caps;
    
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        return $caps;
    }
    
    $user = get_userdata($user_id);
    if (!$user) return ['do_not_allow'];
    
    // Admin/exec always allowed
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return ['read'];
    }
    
    // Check cached accessible slugs
    $accessible = owbn_get_user_accessible_slugs($post->post_type);
    if ($accessible === null) {
        return ['read'];
    }
    
    $meta_key = ($post->post_type === 'owbn_chronicle') ? 'chronicle_slug' : 'coordinator_slug';
    $slug = get_post_meta($post_id, $meta_key, true);
    
    return in_array($slug, $accessible, true) ? ['read'] : ['do_not_allow'];
}

// ══════════════════════════════════════════════════════════════════════════════
// OPTIMIZED POST COUNTS
// ══════════════════════════════════════════════════════════════════════════════

add_filter('wp_count_posts', 'owbn_optimize_post_counts', 10, 3);
function owbn_optimize_post_counts($counts, $type, $perm)
{
    if (!in_array($type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        return $counts;
    }
    
    $user = wp_get_current_user();
    if (!$user->ID) return $counts;
    
    // Admins get normal counts
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return $counts;
    }
    
    $accessible = owbn_get_user_accessible_slugs($type);
    
    $counts = new stdClass();
    foreach (get_post_stati() as $status) {
        $counts->$status = 0;
    }
    
    if (empty($accessible)) {
        return $counts;
    }
    
    global $wpdb;
    $meta_key = ($type === 'owbn_chronicle') ? 'chronicle_slug' : 'coordinator_slug';
    $placeholders = implode(',', array_fill(0, count($accessible), '%s'));
    
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT p.post_status, COUNT(*) AS num_posts 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = %s 
             AND pm.meta_key = %s 
             AND pm.meta_value IN ($placeholders)
             GROUP BY p.post_status",
            array_merge([$type, $meta_key], $accessible)
        ),
        ARRAY_A
    );
    
    foreach ($results as $row) {
        $counts->{$row['post_status']} = (int) $row['num_posts'];
    }
    
    return $counts;
}

// ══════════════════════════════════════════════════════════════════════════════
// ROW ACTIONS - View link to owbn-client frontend
// ══════════════════════════════════════════════════════════════════════════════

add_filter('post_row_actions', 'owbn_modify_row_actions', 10, 2);
function owbn_modify_row_actions($actions, $post)
{
    if (!in_array($post->post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        return $actions;
    }
    
    if (!function_exists('owc_option_name')) {
        return $actions;
    }
    
    $slug = get_post_meta($post->ID, ($post->post_type === 'owbn_chronicle') ? 'chronicle_slug' : 'coordinator_slug', true);
    $page_option = ($post->post_type === 'owbn_chronicle') ? 'chronicles_detail_page' : 'coordinators_detail_page';
    $page_id = get_option(owc_option_name($page_option), 0);
    
    if ($page_id && $slug && isset($actions['view'])) {
        $view_url = add_query_arg('slug', $slug, get_permalink($page_id));
        $actions['view'] = sprintf(
            '<a href="%s" rel="bookmark">%s</a>',
            esc_url($view_url),
            __('View', 'owbn-chronicle-manager')
        );
    }
    
    return $actions;
}

// ══════════════════════════════════════════════════════════════════════════════
// ADMIN NOTICE
// ══════════════════════════════════════════════════════════════════════════════

add_action('admin_notices', 'owbn_filtered_list_notice');
function owbn_filtered_list_notice()
{
    global $pagenow;
    if ($pagenow !== 'edit.php') return;
    
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        return;
    }
    
    $user = wp_get_current_user();
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return;
    }
    
    $type_label = ($screen->post_type === 'owbn_chronicle') ? 'chronicles' : 'coordinators';
    echo '<div class="notice notice-info is-dismissible"><p>';
    printf(esc_html__('Showing only %s you have access to.', 'owbn-chronicle-manager'), esc_html($type_label));
    echo '</p></div>';
}

// ══════════════════════════════════════════════════════════════════════════════
// ADMIN BAR - Remove "New Chronicle/Coordinator" for non-admins
// ══════════════════════════════════════════════════════════════════════════════

add_action('admin_bar_menu', 'owbn_remove_admin_bar_new_items', 999);
function owbn_remove_admin_bar_new_items($wp_admin_bar)
{
    $user = wp_get_current_user();
    if (!$user->ID) return;
    
    // Admins keep everything
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return;
    }
    
    // Remove "New Chronicle" and "New Coordinator" from "+ New" menu
    $wp_admin_bar->remove_node('new-owbn_chronicle');
    $wp_admin_bar->remove_node('new-owbn_coordinator');
}

// ══════════════════════════════════════════════════════════════════════════════
// ADMIN MENU - Remove "Add New" submenu for non-admins
// ══════════════════════════════════════════════════════════════════════════════

add_action('admin_menu', 'owbn_remove_add_new_submenu', 999);
function owbn_remove_add_new_submenu()
{
    $user = wp_get_current_user();
    if (!$user->ID) return;
    
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return;
    }
    
    // Remove "Add New" submenu items
    remove_submenu_page('edit.php?post_type=owbn_chronicle', 'post-new.php?post_type=owbn_chronicle');
    remove_submenu_page('edit.php?post_type=owbn_coordinator', 'post-new.php?post_type=owbn_coordinator');
}

// ══════════════════════════════════════════════════════════════════════════════
// HIDE "ADD NEW" BUTTON ON LIST PAGE VIA CSS
// ══════════════════════════════════════════════════════════════════════════════

add_action('admin_head', 'owbn_hide_add_new_button');
function owbn_hide_add_new_button()
{
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        return;
    }
    
    $user = wp_get_current_user();
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return;
    }
    
    echo '<style>.page-title-action { display: none !important; }</style>';
}