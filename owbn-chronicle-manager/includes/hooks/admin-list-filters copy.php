<?php
/**
 * File: includes/hooks/admin-list-filters.php
 * Text Domain: owbn-chronicle-manager
 * @version 1.2.0
 * 
 * Handles:
 * - Performance: Replace slow AccessSchema filter with cached version
 * - Permission filtering: Restrict list to user's accessible posts
 * - View links: Point to owbn-client frontend pages
 */

if (!defined('ABSPATH')) exit;

// ══════════════════════════════════════════════════════════════════════════════
// CACHED ACCESSSCHEMA ROLES - Fetch once, cache in transient
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get user's AccessSchema roles with aggressive transient caching
 * Only calls remote API if cache is empty
 */
function owbn_get_cached_user_roles($user_id = null, $email = null)
{
    if (!$user_id) {
        $user = wp_get_current_user();
        if (!$user->ID) return [];
        $user_id = $user->ID;
        $email = $user->user_email;
    }
    
    $cache_key = "owbn_asc_roles_{$user_id}";
    
    // Check transient first - this is the critical optimization
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    // Not in cache - fetch from AccessSchema (this is the slow part)
    $roles = [];
    $client_id = defined('ASC_PREFIX') ? strtolower(str_replace('_', '-', ASC_PREFIX)) : 'ccs';
    
    if (function_exists('accessSchema_client_remote_get_roles_by_email')) {
        $response = accessSchema_client_remote_get_roles_by_email($email, $client_id);
        if (!is_wp_error($response) && isset($response['roles'])) {
            $roles = $response['roles'];
        }
    }
    
    // Cache for 10 minutes
    set_transient($cache_key, $roles, 10 * MINUTE_IN_SECONDS);
    return $roles;
}

/**
 * Clear cached roles for a user (call when permissions change)
 */
function owbn_clear_cached_user_roles($user_id)
{
    delete_transient("owbn_asc_roles_{$user_id}");
    delete_transient("owbn_counts_owbn_chronicle_{$user_id}");
    delete_transient("owbn_counts_owbn_coordinator_{$user_id}");
}

// ══════════════════════════════════════════════════════════════════════════════
// REPLACE SLOW ACCESSSCHEMA FILTER WITH CACHED VERSION
// Must run VERY early and at same priority to properly replace
// ══════════════════════════════════════════════════════════════════════════════

// Remove the slow filter and add cached version
add_action('plugins_loaded', function() {
    // Remove the original slow filter
    remove_filter('user_has_cap', 'asc_hook_user_has_cap_filter', 10);
    
    // Add our cached replacement at same priority
    add_filter('user_has_cap', 'owbn_cached_user_has_cap_filter', 10, 4);
}, 99);

/**
 * Cached replacement for asc_hook_user_has_cap_filter
 * Uses transient-cached roles instead of making API calls
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
    
    // Grant basic menu access to all authenticated users
    // AccessSchema controls actual CRUD via capability map
    $allcaps['ocm_view_list'] = true;
    
    $email = $user->user_email;
    
    $email = $user->user_email;
    if (!is_email($email)) {
        return $allcaps;
    }
    
    // Get CACHED roles - this is the key difference
    $roles = owbn_get_cached_user_roles($user->ID, $email);
    
    // Handle group-level check
    if ($requested_cap === 'asc_has_access_to_group') {
        $group_path = $args[1] ?? null;
        if (!$group_path) {
            return $allcaps;
        }
        
        $has_access = in_array($group_path, $roles, true) ||
            !empty(preg_grep('#^' . preg_quote($group_path, '#') . '/#', $roles));
        
        if ($has_access) {
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
        $role_path = owbn_expand_role_path($raw_path);
        
        // Check against cached roles
        if (strpos($role_path, '*') !== false) {
            // Wildcard matching
            if (owbn_roles_match_pattern($roles, $role_path)) {
                $allcaps[$requested_cap] = true;
                break;
            }
        } else {
            // Exact or hierarchical match
            $has_access = in_array($role_path, $roles, true) ||
                !empty(preg_grep('#^' . preg_quote($role_path, '#') . '/#', $roles));
            
            if ($has_access) {
                $allcaps[$requested_cap] = true;
                break;
            }
        }
    }
    
    return $allcaps;
}

/**
 * Expand role path placeholders
 */
function owbn_expand_role_path($raw_path)
{
    $slug = get_query_var('slug') ?: '';
    return str_replace('$slug', sanitize_key($slug), $raw_path);
}

/**
 * Check if any roles match a wildcard pattern
 */
function owbn_roles_match_pattern($roles, $pattern)
{
    $regex = str_replace('\*', '[^/]*', preg_quote($pattern, '#'));
    foreach ($roles as $role) {
        if (preg_match('#^' . $regex . '$#', $role)) {
            return true;
        }
    }
    return false;
}

// ══════════════════════════════════════════════════════════════════════════════
// ACCESSIBLE SLUGS - Extract from cached roles
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get accessible slugs for current user (null = unrestricted for admins)
 */
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
    
    // Get cached roles
    $roles = owbn_get_cached_user_roles($user->ID, $user->user_email);
    
    if ($post_type === 'owbn_chronicle') {
        $accessible = owbn_extract_chronicle_slugs_from_roles($roles, $user->ID);
    } elseif ($post_type === 'owbn_coordinator') {
        $accessible = owbn_extract_coordinator_slugs_from_roles($roles, $user->ID);
    } else {
        $accessible = [];
    }
    
    $cache[$cache_key] = $accessible;
    return $accessible;
}

function owbn_extract_chronicle_slugs_from_roles($roles, $user_id)
{
    $slugs = [];
    
    // Pattern: chronicle/{slug}/hst, chronicle/{slug}/cm, chronicle/{slug}/ast
    foreach ($roles as $role) {
        if (preg_match('#^chronicle/([^/]+)/(hst|cm|ast)$#', $role, $m)) {
            $slugs[] = $m[1];
        }
    }
    
    if (!empty($slugs)) {
        return array_unique($slugs);
    }
    
    // Fallback: meta lookup (only if no AccessSchema roles found)
    global $wpdb;
    $user_id_str = (string) $user_id;
    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'owbn_chronicle'
         AND p.post_status IN ('publish', 'draft')
         AND pm.meta_key IN ('hst_info', 'cm_info')
         AND pm.meta_value LIKE %s
         LIMIT 100",
        '%"user":"' . $wpdb->esc_like($user_id_str) . '"%'
    ));
    
    foreach ($post_ids as $pid) {
        $slug = get_post_meta($pid, 'chronicle_slug', true);
        if ($slug) $slugs[] = $slug;
    }
    
    return array_unique($slugs);
}

function owbn_extract_coordinator_slugs_from_roles($roles, $user_id)
{
    $slugs = [];
    
    // Pattern: coordinator/{slug}/coordinator, coordinator/{slug}/sub-coordinator
    foreach ($roles as $role) {
        if (preg_match('#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#', $role, $m)) {
            $slugs[] = $m[1];
        }
    }
    
    if (!empty($slugs)) {
        return array_unique($slugs);
    }
    
    // Fallback: meta lookup
    global $wpdb;
    $user_id_str = (string) $user_id;
    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'owbn_coordinator'
         AND p.post_status IN ('publish', 'draft')
         AND pm.meta_key = 'coord_info'
         AND pm.meta_value LIKE %s
         LIMIT 100",
        '%"user":"' . $wpdb->esc_like($user_id_str) . '"%'
    ));
    
    foreach ($post_ids as $pid) {
        $slug = get_post_meta($pid, 'coordinator_slug', true);
        if ($slug) $slugs[] = $slug;
    }
    
    return array_unique($slugs);
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
    if (!$post_id) {
        return $caps;
    }
    
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, ['owbn_chronicle', 'owbn_coordinator'], true)) {
        return $caps;
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return ['do_not_allow'];
    }
    
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
    
    if (in_array($slug, $accessible, true)) {
        return ['read'];
    }
    
    return ['do_not_allow'];
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
    if (!$user->ID) {
        return $counts;
    }
    
    // Admins get normal counts
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return $counts;
    }
    
    $cache_key = "owbn_counts_{$type}_{$user->ID}";
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    $accessible = owbn_get_user_accessible_slugs($type);
    
    $counts = new stdClass();
    foreach (get_post_stati() as $status) {
        $counts->$status = 0;
    }
    
    if ($accessible === null || !empty($accessible)) {
        global $wpdb;
        $meta_key = ($type === 'owbn_chronicle') ? 'chronicle_slug' : 'coordinator_slug';
        
        $where = '';
        if ($accessible !== null && !empty($accessible)) {
            $placeholders = implode(',', array_fill(0, count($accessible), '%s'));
            $where = $wpdb->prepare(
                " AND p.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value IN ($placeholders))",
                array_merge([$meta_key], $accessible)
            );
        }
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_status, COUNT(*) AS num_posts FROM {$wpdb->posts} p WHERE post_type = %s {$where} GROUP BY post_status",
                $type
            ),
            ARRAY_A
        );
        
        foreach ($results as $row) {
            $counts->{$row['post_status']} = (int) $row['num_posts'];
        }
    }
    
    set_transient($cache_key, $counts, 10 * MINUTE_IN_SECONDS);
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
    
    $view_url = owbn_get_frontend_view_url($post);
    
    if ($view_url && isset($actions['view'])) {
        $actions['view'] = sprintf(
            '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
            esc_url($view_url),
            esc_attr(sprintf(__('View &#8220;%s&#8221;', 'owbn-chronicle-manager'), get_the_title($post))),
            __('View', 'owbn-chronicle-manager')
        );
    }
    
    return $actions;
}

function owbn_get_frontend_view_url($post)
{
    if (!function_exists('owc_option_name')) {
        return null;
    }
    
    if ($post->post_type === 'owbn_chronicle') {
        $slug = get_post_meta($post->ID, 'chronicle_slug', true);
        $page_id = get_option(owc_option_name('chronicles_detail_page'), 0);
        if ($page_id && $slug) {
            return add_query_arg('slug', $slug, get_permalink($page_id));
        }
    } elseif ($post->post_type === 'owbn_coordinator') {
        $slug = get_post_meta($post->ID, 'coordinator_slug', true);
        $page_id = get_option(owc_option_name('coordinators_detail_page'), 0);
        if ($page_id && $slug) {
            return add_query_arg('slug', $slug, get_permalink($page_id));
        }
    }
    
    return null;
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
    ?>
    <div class="notice notice-info is-dismissible">
        <p><?php printf(
            esc_html__('Showing only %s you have access to.', 'owbn-chronicle-manager'),
            esc_html($type_label)
        ); ?></p>
    </div>
    <?php
}