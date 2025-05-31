<?php
if (!defined('ABSPATH')) exit;

// Shortcode to display a single chronicle as a card
add_shortcode('owbn-chronicle', function($atts) {
    $atts = shortcode_atts([
        'plug' => '',
    ], $atts);

    if (empty($atts['plug'])) {
        return '<p>No chronicle specified.</p>';
    }

    // Normalize the slug (strip full URLs or paths down to the last slug segment)
    $slug = sanitize_title(basename(esc_url_raw($atts['plug'])));

    // Use get_page_by_path for more reliable slug-to-post resolution
    $chronicle_post = get_page_by_path($slug, OBJECT, 'owbn_chronicle');

    if (!$chronicle_post) {
        return '<p>Chronicle not found. Please check the slug.</p>';
    }

    $post_id = $chronicle_post->ID;

    // Output only the card version
    return '<div class="owbn-chronicle-wrapper">' . owbn_render_chronicle_card($post_id) . '</div>';
});

add_shortcode('owbn-chronicle-meta', function($atts) {
    $atts = shortcode_atts([
        'plug' => '',
        'term' => '',
    ], $atts);

    if (empty($atts['term'])) return '';

    // Resolve Chronicle slug (plug)
    $plug = $atts['plug'];

    if (empty($plug)) {
        // Try to get it from URL like /chronicles/<plug>
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/chronicles/([^/]+)/?#', $uri, $matches)) {
            $plug = sanitize_title($matches[1]);
        }
    }

    $post = null;

    if (!empty($plug)) {
        $post = get_page_by_path($plug, OBJECT, 'owbn_chronicle');
    }

    if (!$post && get_post_type() === 'owbn_chronicle') {
        global $post;
    }

    if (!$post || $post->post_type !== 'owbn_chronicle') {
        return '';
    }

    $term = strtolower($atts['term']);
    $output = '';

    switch ($term) {
        case 'title':
            $output = esc_html(get_the_title($post));
            break;

        case 'content':
            $output = apply_filters('the_content', $post->post_content);
            break;

        case 'author':
            $output = esc_html(get_the_author_meta('display_name', $post->post_author));
            break;

        case 'date':
            $output = esc_html(get_the_date('', $post));
            break;

        case 'slug':
        case 'post_name':
            $output = esc_html($post->post_name);
            break;

        case 'excerpt':
            $output = esc_html($post->post_excerpt ?: wp_trim_words($post->post_content, 30));
            break;

        case 'chronicle_slug':
        case 'active_player_count':
        case 'hst_selection':
        case 'cm_selection':
        case 'ast_selection':
        case 'web_url':
        case 'chronicle_start_date':
        case 'chronicle_region':
            $output = esc_html(get_post_meta($post->ID, $term, true));
            break;

        case 'chronicle_probationary':
        case 'chronicle_satellite':
            $output = get_post_meta($post->ID, $term, true) ? 'Yes' : 'No';
            break;

        default:
            $definitions = function_exists('owbn_get_chronicle_field_definitions')
                ? owbn_get_chronicle_field_definitions()
                : [];

            foreach ($definitions as $section => $fields) {
                if (isset($fields[$term])) {
                    $value = get_post_meta($post->ID, $term, true);
                    $field_def = $fields[$term];

                    if ($field_def['type'] === 'boolean') {
                        $output = $value ? 'Yes' : 'No';
                    } elseif (is_array($value)) {
                        $output = esc_html(implode(', ', array_filter(array_map('trim', $value))));
                    } elseif (is_scalar($value)) {
                        $output = esc_html($value);
                    }
                    break;
                }
            }

            if ($output === '') {
                $meta = get_post_meta($post->ID, $term, true);
                if (is_array($meta)) {
                    $output = esc_html(implode(', ', array_filter(array_map('trim', $meta))));
                } elseif (is_scalar($meta)) {
                    $output = esc_html($meta);
                }
            }
    }

    return sprintf(
        '<div class="owbn-chronicle-meta-%s">%s</div>',
        esc_attr($term),
        $output
    );
});