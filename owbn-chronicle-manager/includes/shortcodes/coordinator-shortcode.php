<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode to display a single coordinator as a card
 * Usage: [owbn-coordinator plug="assamite"]
 */
add_shortcode('owbn-coordinator', function ($atts) {
    $atts = shortcode_atts([
        'plug' => '',
        'view' => 'box', // 'box' or 'full'
    ], $atts);

    if (empty($atts['plug'])) {
        return '<p>No coordinator specified.</p>';
    }

    // Normalize the slug
    $slug = sanitize_title(basename(esc_url_raw($atts['plug'])));

    // Find coordinator by slug meta
    $query = new WP_Query([
        'post_type'      => 'owbn_coordinator',
        'posts_per_page' => 1,
        'meta_key'       => 'coordinator_slug',
        'meta_value'     => $slug,
        'post_status'    => 'publish',
    ]);

    if (!$query->have_posts()) {
        // Fallback: try by post name
        $coordinator_post = get_page_by_path($slug, OBJECT, 'owbn_coordinator');
        if (!$coordinator_post) {
            return '<p>Coordinator not found. Please check the slug.</p>';
        }
        $post_id = $coordinator_post->ID;
    } else {
        $query->the_post();
        $post_id = get_the_ID();
        wp_reset_postdata();
    }

    // Render based on view type
    if ($atts['view'] === 'full') {
        return '<div class="owbn-coordinator-wrapper">' . wp_kses_post(owbn_render_coordinator_full($post_id)) . '</div>';
    }

    return '<div class="owbn-coordinator-wrapper">' . wp_kses_post(owbn_render_coordinator_card($post_id)) . '</div>';
});

/**
 * Shortcode to display coordinator meta fields
 * Usage: [owbn-coordinator-meta plug="assamite" term="coord_info"]
 */
add_shortcode('owbn-coordinator-meta', function ($atts) {
    $atts = shortcode_atts([
        'plug'  => '',
        'term'  => '',
        'label' => 'true',
    ], $atts);

    if (empty($atts['term'])) return '';

    // Resolve Coordinator slug
    $plug = $atts['plug'];

    if (empty($plug)) {
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if (preg_match('#/coords/([^/]+)/?#', $uri, $matches)) {
            $plug = sanitize_title($matches[1]);
        }
    }

    $post = null;
    if (!empty($plug)) {
        // Find by slug meta
        $query = new WP_Query([
            'post_type'      => 'owbn_coordinator',
            'posts_per_page' => 1,
            'meta_key'       => 'coordinator_slug',
            'meta_value'     => $plug,
            'post_status'    => 'publish',
        ]);

        // NEW - Single lookup
        $query = new WP_Query([
            'post_type'      => 'owbn_coordinator',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => [[
                'key'   => 'coordinator_slug',
                'value' => $plug,
            ]],
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
        ]);

        if (!$query->have_posts()) {
            return '';
        }

        $post = $query->posts[0];
    }

    if (!$post) {
        global $post;
        if (!$post || $post->post_type !== 'owbn_coordinator') {
            return '';
        }
    }

    if (!$post || $post->post_type !== 'owbn_coordinator') {
        return '';
    }

    $term = strtolower($atts['term']);
    $show_label = filter_var($atts['label'], FILTER_VALIDATE_BOOLEAN);

    // Dispatch map for rendering handlers
    $term_handlers = [
        'title'           => 'owbn_render_coordinator_title',
        'office_title'    => 'owbn_render_coordinator_office_title',
        'coord_info'      => 'owbn_render_coordinator_info',
        'subcoord_list'   => 'owbn_render_coordinator_subcoord_list',
        'document_links'  => 'owbn_render_coordinator_document_links',
        'email_lists'     => 'owbn_render_coordinator_email_lists',
    ];

    if (isset($term_handlers[$term]) && function_exists($term_handlers[$term])) {
        $content = call_user_func($term_handlers[$term], $post);
    } else {
        $content = owbn_coordinator_output_simple_meta($post, $term);
    }

    return owbn_coordinator_output_wrapper($term, $content, $show_label);
});

/**
 * Output simple meta fields
 */
function owbn_coordinator_output_simple_meta($post, $term)
{
    $output = '';

    switch ($term) {
        case 'title':
            $output = esc_html(get_the_title($post));
            break;

        case 'content':
            $output = apply_filters('the_content', $post->post_content);
            break;

        case 'slug':
        case 'coordinator_slug':
            $output = esc_html(get_post_meta($post->ID, 'coordinator_slug', true));
            break;

        case 'coordinator_title':
        case 'office_title':
            $output = esc_html(get_post_meta($post->ID, 'coordinator_title', true));
            break;

        case 'office_description':
            $desc = get_post_meta($post->ID, 'office_description', true);
            $output = wp_kses_post(wpautop($desc));
            break;

        case 'term_start_date':
            $date = get_post_meta($post->ID, 'term_start_date', true);
            if (!empty($date)) {
                $output = esc_html(date_i18n(get_option('date_format'), strtotime($date)));
            }
            break;

        case 'web_url':
            $url = get_post_meta($post->ID, 'web_url', true);
            if (!empty($url)) {
                $output = '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($url) . '</a>';
            }
            break;

        default:
            $meta = get_post_meta($post->ID, $term, true);
            if (is_array($meta)) {
                $output = esc_html(implode(', ', array_filter(array_map('trim', $meta))));
            } else {
                $output = esc_html(trim((string) $meta));
            }
            break;
    }

    return $output;
}
