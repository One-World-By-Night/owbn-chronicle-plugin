<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode to list all coordinators
 * Usage: [owbn-coordinators]
 * Usage: [owbn-coordinators view="cards"]
 * Usage: [owbn-coordinators view="table" columns="office,coordinator,term_start,website"]
 */
add_shortcode('owbn-coordinators', function ($atts) {
    $atts = shortcode_atts([
        'view'    => 'table',  // 'table', 'cards', or 'list'
        'columns' => 'office,coordinator,term_start',
        'orderby' => 'title',
        'order'   => 'ASC',
        'filter'  => '',       // future: filter by category/type
    ], $atts);

    $query = new WP_Query([
        'post_type'      => 'owbn_coordinator',
        'posts_per_page' => -1,
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
        'post_status'    => 'publish',
    ]);

    if (!$query->have_posts()) {
        return '<p>' . esc_html__('No coordinators found.', 'owbn-chronicle-manager') . '</p>';
    }

    ob_start();

    switch ($atts['view']) {
        case 'cards':
            echo owbn_render_coordinators_cards($query);
            break;

        case 'list':
            echo owbn_render_coordinators_simple_list($query);
            break;

        case 'table':
        default:
            $columns = array_map('trim', explode(',', $atts['columns']));
            echo owbn_render_coordinators_table($query, $columns);
            break;
    }

    wp_reset_postdata();

    return ob_get_clean();
});

/**
 * Render coordinators as cards grid
 */
function owbn_render_coordinators_cards($query)
{
    ob_start();

    echo '<div class="owbn-coordinators-grid">';
    while ($query->have_posts()) {
        $query->the_post();
        echo wp_kses_post(owbn_render_coordinator_card(get_the_ID()));
    }
    echo '</div>';

    return ob_get_clean();
}

/**
 * Render coordinators as simple list
 */
function owbn_render_coordinators_simple_list($query)
{
    ob_start();

    echo '<ul class="owbn-coordinators-list">';
    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();

        $slug = get_post_meta($post_id, 'coordinator_slug', true);
        $office_title = get_post_meta($post_id, 'coordinator_title', true);
        $coord_info = get_post_meta($post_id, 'coord_info', true);
        $view_url = home_url('/coords/' . $slug);

        $coord_name = is_array($coord_info) ? ($coord_info['display_name'] ?? '') : '';

        echo '<li>';
        echo '<a href="' . esc_url($view_url) . '">' . esc_html($office_title ?: get_the_title()) . '</a>';
        if (!empty($coord_name)) {
            echo ' — ' . esc_html($coord_name);
        }
        echo '</li>';
    }
    echo '</ul>';

    return ob_get_clean();
}

/**
 * Render coordinators as table with configurable columns
 */
function owbn_render_coordinators_table($query, $columns)
{
    $column_labels = [
        'office'       => __('Office', 'owbn-chronicle-manager'),
        'coordinator'  => __('Coordinator', 'owbn-chronicle-manager'),
        'term_start'   => __('Term Started', 'owbn-chronicle-manager'),
        'website'      => __('Website', 'owbn-chronicle-manager'),
        'subcoords'    => __('Sub-Coordinators', 'owbn-chronicle-manager'),
        'email'        => __('Email', 'owbn-chronicle-manager'),
    ];

    ob_start();

    echo '<div class="owbn-coordinators-table-wrapper">';
    echo '<table class="owbn-coordinators-table">';

    // Table header
    echo '<thead><tr>';
    foreach ($columns as $col) {
        $label = $column_labels[$col] ?? ucwords(str_replace('_', ' ', $col));
        echo '<th>' . esc_html($label) . '</th>';
    }
    echo '</tr></thead>';

    // Table body
    echo '<tbody>';
    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();

        echo '<tr>';
        foreach ($columns as $col) {
            echo '<td>' . owbn_render_coordinator_column($post_id, $col) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody>';

    echo '</table>';
    echo '</div>';

    return ob_get_clean();
}

/**
 * Render individual column value for coordinator table
 */
function owbn_render_coordinator_column($post_id, $column)
{
    $slug = get_post_meta($post_id, 'coordinator_slug', true);
    $view_url = home_url('/coords/' . $slug);

    switch ($column) {
        case 'office':
            $office_title = get_post_meta($post_id, 'coordinator_title', true);
            return '<a href="' . esc_url($view_url) . '">' . esc_html($office_title ?: get_the_title($post_id)) . '</a>';

        case 'coordinator':
            $coord_info = get_post_meta($post_id, 'coord_info', true);
            if (!is_array($coord_info) || empty($coord_info['display_name'])) {
                return '—';
            }
            $name = $coord_info['display_name'];
            // Use display_email only - never expose actual_email
            $email = $coord_info['display_email'] ?? '';
            if (!empty($email)) {
                return '<a href="mailto:' . esc_attr($email) . '">' . esc_html($name) . '</a>';
            }
            return esc_html($name);

        case 'term_start':
            $term_start = get_post_meta($post_id, 'term_start_date', true);
            if (empty($term_start)) return '—';
            return esc_html(date_i18n(get_option('date_format'), strtotime($term_start)));

        case 'website':
            $web_url = get_post_meta($post_id, 'web_url', true);
            if (empty($web_url)) return '—';
            return '<a href="' . esc_url($web_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('View', 'owbn-chronicle-manager') . '</a>';

        case 'subcoords':
            $subcoord_list = get_post_meta($post_id, 'subcoord_list', true);
            $count = is_array($subcoord_list) ? count($subcoord_list) : 0;
            return esc_html($count);

        case 'email':
            $coord_info = get_post_meta($post_id, 'coord_info', true);
            // Use display_email only - never expose actual_email
            $email = is_array($coord_info) ? ($coord_info['display_email'] ?? '') : '';
            if (empty($email)) return '—';
            return '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';

        default:
            $meta = get_post_meta($post_id, $column, true);
            if (is_array($meta)) {
                return esc_html(implode(', ', array_filter($meta)));
            }
            return esc_html((string) $meta);
    }
}

/**
 * Add filters section (optional, for future use)
 */
function owbn_render_coordinators_filters($filter_keys)
{
    // Placeholder for future filter implementation
    // Similar to chronicles-shortcode.php filters
    return '';
}
