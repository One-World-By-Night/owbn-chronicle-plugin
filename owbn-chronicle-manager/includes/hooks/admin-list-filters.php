<?php
/**
 * File: includes/hooks/admin-list-filters.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.0.0
 *
 * Handles:
 * - Performance: Replace slow AccessSchema filter with cached version
 * - Permission filtering: Restrict list to user's accessible posts
 * - View links: Point to owbn-client frontend pages
 *
 * v2 changes:
 * - All hardcoded post type checks replaced with owbn_get_entity_post_types()
 * - Per-entity slug extraction replaced with generic owbn_extract_entity_slugs_from_roles()
 * - Hardcoded meta_key selection replaced with entity config lookup
 * - Per-entity map_meta_cap filters replaced with single owbn_entity_map_meta_cap
 * - Cap grants loop over all registered entity types
 */

if (!defined('ABSPATH')) exit;

/**
 * Check if user is an admin-level role that bypasses ASC checks.
 */
function owbn_user_is_admin_role(WP_User $user): bool {
    return (bool) array_intersect($user->roles, ['administrator', 'exec_team', 'web_team']);
}

/**
 * Check if user has any ASC roles for any entity type.
 * Uses cached roles to avoid API calls.
 */
function owbn_user_has_any_entity_roles(WP_User $user): bool {
    static $cache = [];
    if (isset($cache[$user->ID])) {
        return $cache[$user->ID];
    }

    $roles = owbn_get_cached_user_roles($user->ID, $user->user_email);
    $entity_prefixes = [];
    foreach (owbn_get_entity_types() as $post_type => $config) {
        $entity_key = $config['entity_key'] ?? '';
        if ($entity_key) {
            $entity_prefixes[] = $entity_key . '/';
        }
    }

    foreach ($roles as $role) {
        foreach ($entity_prefixes as $prefix) {
            if (strpos($role, $prefix) === 0) {
                $cache[$user->ID] = true;
                return true;
            }
        }
    }

    $cache[$user->ID] = false;
    return false;
}

// Capability grant — only for admin roles or users with ASC entity roles.
add_filter('user_has_cap', 'owbn_entity_cap_grant', 1, 4);
function owbn_entity_cap_grant($allcaps, $caps, $args, $user) {
    if (!$user instanceof WP_User || !$user->ID) {
        return $allcaps;
    }

    // Admin roles get full CPT access unconditionally.
    if (owbn_user_is_admin_role($user)) {
        foreach (owbn_get_entity_types() as $post_type => $config) {
            $base_caps = $config['capabilities'] ?? [];
            if (!empty($base_caps['read_post'])) {
                $allcaps[$base_caps['read_post']] = true;
            }
            if (!empty($base_caps['edit_post'])) {
                $allcaps[$base_caps['edit_post']] = true;
                $allcaps["edit_{$post_type}s"] = true;
            }
        }
        return $allcaps;
    }

    // Non-admin: only grant CPT caps if user has ASC roles for an entity type.
    if (!owbn_user_has_any_entity_roles($user)) {
        return $allcaps;
    }

    $entity_post_types = owbn_get_entity_post_types();

    foreach (owbn_get_entity_types() as $post_type => $config) {
        $base_caps = $config['capabilities'] ?? [];
        if (!empty($base_caps['read_post'])) {
            $allcaps[$base_caps['read_post']] = true;
        }
        if (!empty($base_caps['edit_post'])) {
            $allcaps["edit_{$post_type}s"] = true;
        }
    }

    // On our CPT pages, also grant generic edit_posts so WP doesn't block access.
    $is_our_cpt_page = (
        is_admin() &&
        isset($_GET['post_type']) &&
        in_array($_GET['post_type'], $entity_post_types, true)
    );
    if ($is_our_cpt_page) {
        $allcaps['edit_posts'] = true;
    }

    return $allcaps;
}

// map_meta_cap override — only for admin roles or ASC role holders.
add_filter('map_meta_cap', 'owbn_entity_override_map_meta_cap', 0, 4);
function owbn_entity_override_map_meta_cap($caps, $cap, $user_id, $args) {
    $our_caps = [];
    foreach (owbn_get_entity_types() as $post_type => $config) {
        $base_caps = $config['capabilities'] ?? [];
        if (!empty($base_caps['edit_post'])) {
            $our_caps[] = $base_caps['edit_post'];
        }
        if (!empty($base_caps['read_post'])) {
            $our_caps[] = $base_caps['read_post'];
        }
    }

    if (!in_array($cap, $our_caps, true)) {
        return $caps;
    }

    if (!in_array('do_not_allow', $caps, true)) {
        return $caps;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return $caps;
    }

    if (owbn_user_is_admin_role($user) || owbn_user_has_any_entity_roles($user)) {
        return ['read'];
    }

    return $caps;
}

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

    // Clear per-entity-type count caches
    foreach (owbn_get_entity_post_types() as $post_type) {
        delete_transient("owbn_counts_{$post_type}_{$user_id}");
    }
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

    // Grant basic page access to all authenticated users
    // This allows loading the CPT list screens - AccessSchema controls actual CRUD via capability map
    $allcaps['ocm_view_list'] = true;

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

    $config = owbn_get_entity_config($post_type);
    if (!$config) return [];

    $accessible = owbn_extract_entity_slugs_from_roles($roles, $user->ID, $post_type);

    $cache[$cache_key] = $accessible;
    return $accessible;
}

/**
 * Generic slug extraction from AccessSchema roles for any entity type.
 *
 * Uses the entity config's access_patterns to extract slugs from role strings.
 * Falls back to meta lookup using staff_fields and slug_meta_key.
 *
 * @param array  $roles     AccessSchema roles for the user.
 * @param int    $user_id   WordPress user ID.
 * @param string $post_type WordPress post type name.
 * @return array List of accessible slugs.
 */
function owbn_extract_entity_slugs_from_roles($roles, $user_id, $post_type)
{
    $config = owbn_get_entity_config($post_type);
    if (!$config) return [];

    $slugs = [];
    $access_patterns = $config['access_patterns'] ?? [];

    // Build regex patterns from access_patterns config
    // e.g. 'chronicle/{slug}/hst' becomes '#^chronicle/([^/]+)/hst$#'
    foreach ($access_patterns as $pattern) {
        $regex_pattern = '#^' . str_replace('\\{slug\\}', '([^/]+)', preg_quote($pattern, '#')) . '$#';
        foreach ($roles as $role) {
            if (preg_match($regex_pattern, $role, $m)) {
                $slugs[] = $m[1];
            }
        }
    }

    if (!empty($slugs)) {
        return array_unique($slugs);
    }

    // Fallback: meta lookup (only if no AccessSchema roles found)
    $staff_fields  = $config['staff_fields'] ?? [];
    $slug_meta_key = $config['slug_meta_key'] ?? '';

    if (empty($staff_fields) || empty($slug_meta_key)) {
        return [];
    }

    global $wpdb;
    $user_id_str = (string) $user_id;

    // Build meta_key IN clause from staff_fields
    $meta_key_placeholders = implode(',', array_fill(0, count($staff_fields), '%s'));
    $query_args = array_merge([$post_type], $staff_fields);
    $query_args[] = '%"user":"' . $wpdb->esc_like($user_id_str) . '"%';

    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = %s
         AND p.post_status IN ('publish', 'draft')
         AND pm.meta_key IN ($meta_key_placeholders)
         AND pm.meta_value LIKE %s
         LIMIT 100",
        $query_args
    ));

    foreach ($post_ids as $pid) {
        $slug = get_post_meta($pid, $slug_meta_key, true);
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
    if (!in_array($post_type, owbn_get_entity_post_types(), true)) {
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

    $config = owbn_get_entity_config($post_type);
    $meta_key = $config['slug_meta_key'] ?? '';

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
    if ($screen->base === 'edit' && in_array($screen->post_type, owbn_get_entity_post_types(), true)) {
        $owbn_list_screen_active = true;

        // Remove slow per-post map_meta_cap filter (single generic filter in v2)
        remove_filter('map_meta_cap', 'owbn_entity_map_meta_cap', 10);
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
    if (!$post || !in_array($post->post_type, owbn_get_entity_post_types(), true)) {
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

    $config = owbn_get_entity_config($post->post_type);
    $meta_key = $config['slug_meta_key'] ?? '';
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
    if (!in_array($type, owbn_get_entity_post_types(), true)) {
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

        $config = owbn_get_entity_config($type);
        $meta_key = $config['slug_meta_key'] ?? '';

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
    if (!in_array($post->post_type, owbn_get_entity_post_types(), true)) {
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

/**
 * Get the frontend view URL for an entity post.
 *
 * Uses entity config to look up the slug meta key and entity_key to
 * determine which owbn-client option holds the detail page ID.
 *
 * @param WP_Post $post The post object.
 * @return string|null The frontend URL, or null if unavailable.
 */
function owbn_get_frontend_view_url($post)
{
    if (!function_exists('owc_option_name')) {
        return null;
    }

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return null;

    $slug_meta_key = $config['slug_meta_key'] ?? '';
    $entity_key    = $config['entity_key'] ?? '';
    $plural        = strtolower($config['plural'] ?? '');

    if (!$slug_meta_key || !$entity_key) return null;

    $slug = get_post_meta($post->ID, $slug_meta_key, true);

    // owbn-client uses option names like 'chronicles_detail_page', 'coordinators_detail_page'
    $page_id = get_option(owc_option_name("{$plural}_detail_page"), 0);

    if ($page_id && $slug) {
        return add_query_arg('slug', $slug, get_permalink($page_id));
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
    if (!$screen || !in_array($screen->post_type, owbn_get_entity_post_types(), true)) {
        return;
    }

    $user = wp_get_current_user();
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return;
    }

    $config = owbn_get_entity_config($screen->post_type);
    $type_label = $config ? strtolower($config['plural']) : $screen->post_type;
    ?>
    <div class="notice notice-info is-dismissible">
        <p><?php printf(
            esc_html__('Showing only %s you have access to.', 'owbn-chronicle-manager'),
            esc_html($type_label)
        ); ?></p>
    </div>
    <?php
}
