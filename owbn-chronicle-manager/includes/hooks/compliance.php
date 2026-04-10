<?php
/**
 * Entity Compliance Surfacing
 *
 * Keeps compliance state visible at every level an admin might look:
 *
 *   1. Per-post metabox banner (handled in entity-init.php)
 *   2. CPT list table column showing ✓ / ⚠ at a glance
 *   3. Dashboard widget summarizing non-compliant entities
 *   4. One-time backfill sweep triggered from the upgrade hook so
 *      existing data is graded the moment this release goes live.
 *
 * Compliance state is stored in post meta `_owbn_{entity_key}_compliance_gaps`
 * by owbn_update_entity_compliance_meta() in entity-validate.php. Meta is
 * deleted when the post is fully compliant so queries can use NOT EXISTS.
 *
 */

if (!defined('ABSPATH')) exit;

/**
 * Register a compliance column on every entity CPT list table that has
 * required_documents configured.
 */
add_action('admin_init', 'owbn_register_compliance_list_columns');
function owbn_register_compliance_list_columns(): void
{
    foreach (owbn_get_entity_types() as $post_type => $config) {
        if (empty($config['required_documents'])) continue;
        if (!owbn_is_entity_enabled($post_type)) continue;

        add_filter("manage_{$post_type}_posts_columns", 'owbn_compliance_add_column');
        add_action("manage_{$post_type}_posts_custom_column", 'owbn_compliance_render_column', 10, 2);
        add_filter("manage_edit-{$post_type}_sortable_columns", 'owbn_compliance_make_sortable');
    }
}

function owbn_compliance_add_column(array $columns): array
{
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['owbn_compliance'] = __('Compliance', 'owbn-chronicle-manager');
        }
    }
    if (!isset($new['owbn_compliance'])) {
        $new['owbn_compliance'] = __('Compliance', 'owbn-chronicle-manager');
    }
    return $new;
}

function owbn_compliance_render_column(string $column, int $post_id): void
{
    if ($column !== 'owbn_compliance') return;

    $post = get_post($post_id);
    if (!$post) return;
    $config = owbn_get_entity_config($post->post_type);
    if (!$config || empty($config['required_documents'])) {
        echo '—';
        return;
    }
    $entity_key = $config['entity_key'];
    $gaps = get_post_meta($post_id, "_owbn_{$entity_key}_compliance_gaps", true);
    if (is_array($gaps) && !empty($gaps)) {
        $title = esc_attr(sprintf(
            __('Missing: %s', 'owbn-chronicle-manager'),
            implode(', ', $gaps)
        ));
        echo '<span class="owbn-compliance-badge owbn-compliance-badge--warn" title="' . $title . '" style="display:inline-block;padding:2px 8px;border-radius:10px;background:#fbecd0;color:#664400;font-weight:600;">';
        echo esc_html(sprintf(
            /* translators: %d: number of missing required documents */
            _n('%d missing', '%d missing', count($gaps), 'owbn-chronicle-manager'),
            count($gaps)
        ));
        echo '</span>';
    } else {
        echo '<span class="owbn-compliance-badge owbn-compliance-badge--ok" title="' . esc_attr__('All required documents present', 'owbn-chronicle-manager') . '" style="display:inline-block;padding:2px 8px;border-radius:10px;background:#e5f5e0;color:#14521e;font-weight:600;">';
        echo esc_html__('OK', 'owbn-chronicle-manager');
        echo '</span>';
    }
}

function owbn_compliance_make_sortable(array $columns): array
{
    $columns['owbn_compliance'] = 'owbn_compliance';
    return $columns;
}

/**
 * Dashboard widget — list of non-compliant entities for exec/web team.
 */
add_action('wp_dashboard_setup', 'owbn_compliance_register_dashboard_widget');
function owbn_compliance_register_dashboard_widget(): void
{
    if (!owbn_is_admin_user()) return;

    wp_add_dashboard_widget(
        'owbn_compliance_widget',
        __('OWBN — Entities Needing Required Documents', 'owbn-chronicle-manager'),
        'owbn_compliance_render_dashboard_widget'
    );
}

function owbn_compliance_render_dashboard_widget(): void
{
    $entities_with_compliance = [];
    foreach (owbn_get_entity_types() as $post_type => $config) {
        if (empty($config['required_documents'])) continue;
        if (!owbn_is_entity_enabled($post_type)) continue;
        $entities_with_compliance[$post_type] = $config;
    }
    if (empty($entities_with_compliance)) {
        echo '<p>' . esc_html__('No entity types with required documents are configured.', 'owbn-chronicle-manager') . '</p>';
        return;
    }

    $total_gaps = 0;
    foreach ($entities_with_compliance as $post_type => $config) {
        $entity_key = $config['entity_key'];
        $q = new WP_Query([
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 25,
            'meta_query'     => [[
                'key'     => "_owbn_{$entity_key}_compliance_gaps",
                'compare' => 'EXISTS',
            ]],
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        $count = (int) $q->found_posts;
        $total_gaps += $count;

        echo '<h3 style="margin-top:10px;">' . esc_html($config['plural']) . '</h3>';
        if ($count === 0) {
            echo '<p style="color:#14521e;">';
            echo esc_html__('All published entries are compliant.', 'owbn-chronicle-manager');
            echo '</p>';
            continue;
        }

        echo '<p>' . esc_html(sprintf(
            /* translators: %d: count of non-compliant entities */
            _n('%d entry missing required documents:', '%d entries missing required documents:', $count, 'owbn-chronicle-manager'),
            $count
        )) . '</p>';

        echo '<ul style="margin-left:16px;list-style:disc;">';
        foreach ($q->posts as $pid) {
            $gaps = get_post_meta($pid, "_owbn_{$entity_key}_compliance_gaps", true);
            $gaps = is_array($gaps) ? $gaps : [];
            $edit_link = get_edit_post_link($pid);
            echo '<li>';
            echo '<a href="' . esc_url($edit_link) . '">' . esc_html(get_the_title($pid)) . '</a>';
            if (!empty($gaps)) {
                echo ' <small style="color:#664400;">— ' . esc_html(implode(', ', $gaps)) . '</small>';
            }
            echo '</li>';
        }
        echo '</ul>';
        if ($q->found_posts > count($q->posts)) {
            echo '<p><em>' . esc_html(sprintf(
                /* translators: %d: number of additional non-compliant entries */
                __('(+%d more not shown)', 'owbn-chronicle-manager'),
                $q->found_posts - count($q->posts)
            )) . '</em></p>';
        }
    }

    if ($total_gaps === 0) {
        echo '<p><strong>' . esc_html__('Everything is compliant. ', 'owbn-chronicle-manager') . '</strong></p>';
    }
}

/**
 * One-time sweep: recompute compliance meta for every existing entity of
 * types with required_documents. Called from the upgrade hook so the
 * badges and widget are populated the moment this release activates,
 * with no user action needed.
 *
 * Idempotent and read-only for posts (only writes post meta). Safe to
 * re-run; callers may re-run it manually via a hidden admin-post action
 * if needed in the future.
 *
 * @return int Total number of entities evaluated.
 */
function owbn_compliance_backfill_all(): int
{
    $count = 0;
    foreach (owbn_get_entity_types() as $post_type => $config) {
        if (empty($config['required_documents'])) continue;

        $q = new WP_Query([
            'post_type'      => $post_type,
            'post_status'    => ['publish', 'draft', 'pending', 'future', 'private'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        foreach ($q->posts as $pid) {
            owbn_update_entity_compliance_meta((int) $pid, $config);
            $count++;
        }
    }
    return $count;
}
