<?php
if (!defined('ABSPATH')) exit;

// Shortcode to display a list of chronicles with filters
add_shortcode('owbn-chronicles', function($atts) {
    $atts = shortcode_atts([
        'plug' => '',
        'region' => '',
        'genre' => '',
        'country' => '',
        'state' => '',
        'game_type' => '',
        'probationary' => '',
        'satellite' => '',
        'chronicle_region' => '',
    ], $atts);

    // Prepare meta_query
    $meta_query = [];

    foreach ([
        'plug' => 'chronicle_slug',
        'region' => 'region',
        'genre' => 'genres',
        'country' => 'country',
        'state' => 'state',
        'game_type' => 'game_type',
        'probationary' => 'chronicle_probationary',
        'satellite' => 'chronicle_satellite',
        'chronicle_region' => 'chronicle_region',
    ] as $att_key => $meta_key) {
        if (!empty($atts[$att_key])) {
            $value = $atts[$att_key];
            if ($att_key === 'genre') {
                $value = '"' . $value . '"'; // Helps match serialized string exactly
            }

            $meta_query[] = [
                'key' => $meta_key,
                'value' => $value,
                'compare' => 'LIKE',
            ];
        }
    }

    $args = [
        'post_type' => 'owbn_chronicle',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_key' => 'chronicle_start_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
    ];

    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    $query = new WP_Query($args);

    ob_start();

    echo '<div class="owbn-chronicle-list">';
    
    // Placeholder for filters – will be replaced with Select2 UI
    echo '<div class="owbn-chronicle-filters">';
    echo '<!-- Filters coming soon -->';
    echo '</div>';

    if ($query->have_posts()) {
        echo owbn_render_chronicle_list($query); // Render as table
    } else {
        echo '<p>No chronicles found.</p>';
    }

    echo '</div>';

    wp_reset_postdata();
    return ob_get_clean();
});

// Render a list of chronicles as a table
function owbn_render_chronicle_list($query) {
    ob_start();

    $row_index = 0;

    echo "\n<div class=\"owbn-chronicle-filters\">\n";

    // Country filter
    echo "  <select id=\"filter-country\" class=\"owbn-select2 single\" data-filter=\"country\">\n";
    echo "    <option value=\"\">Filter by Country</option>\n";
    echo "  </select>\n";

    // OWBN Region filter
    echo '<select id="filter-chronicle-region" class="owbn-select2 single" data-filter="chronicle-region">\n';
    echo "    <option value=\"\">Filter by Region</option>\n";
    echo "  </select>\n";

    // Genre Filter
    echo "  <select id=\"filter-genre\" class=\"owbn-select2 multi\" data-filter=\"genre\">\n";
    echo "    <option value=\"\">Filter by Genre</option>\n";
    echo "  </select>\n";

    // Game Type Filter
    echo "  <select id=\"filter-type\" class=\"owbn-select2 single\" data-filter=\"game-type\">\n";
    echo "    <option value=\"\">Filter by Game Type</option>\n";
    echo "    <option value=\"in-person\">In-Person</option>\n";
    echo "    <option value=\"virtual\">Virtual</option>\n";
    echo "    <option value=\"hybrid\">Hybrid</option>\n";
    echo "  </select>\n";

    // Probationary Filter
    echo "  <select id=\"filter-probationary\" class=\"owbn-select2 single\" data-filter=\"probationary\">\n";
    echo "    <option value=\"\">Filter by Probationary</option>\n";
    echo "    <option value=\"yes\">Probationary</option>\n";
    echo "    <option value=\"no\">Not Probationary</option>\n";
    echo "  </select>\n";

    // Satellite Filter
    echo "  <div style=\"display: flex; flex-direction: column; gap: 0.5rem;\">\n";
    echo "    <select id=\"filter-satellite\" class=\"owbn-select2 single\" data-filter=\"satellite\">\n";
    echo "      <option value=\"\">Filter by Satellite</option>\n";
    echo "      <option value=\"yes\">Satellite</option>\n";
    echo "      <option value=\"no\">Not Satellite</option>\n";
    echo "    </select>\n";
    echo "  </div>\n";
    echo "  <div style=\"display: flex; flex-direction: column; gap: 0.5rem;\">\n";
    echo "    <button id=\"clear-filters\">Clear Filters</button>\n";
    echo "  </div>\n";

    echo "</div>\n";

    echo "\n<div class=\"owbn-chronicle-legend\">\n";
    echo "  <div class=\"chron-header\">Chronicle Name</div>\n";
    echo "  <div class=\"chron-header\">Location</div>\n";
    echo "  <div class=\"chron-header\"> OWBN Region</div>\n";
    echo "  <div class=\"chron-header\">Genre</div>\n";
    echo "  <div class=\"chron-header\">Game Type</div>\n";
    echo "  <div class=\"chron-header\">Probationary</div>\n";
    echo "  <div class=\"chron-header\">Satellite</div>\n";
    echo "</div>\n";

    echo "\n<div class=\"chronicle-rows\">\n";
    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();

        $slug = get_post_meta($post_id, 'chronicle_slug', true);

        $ooc_locations = get_post_meta($post_id, 'ooc_locations', true);
        $first_location = is_array($ooc_locations) && !empty($ooc_locations) ? $ooc_locations[0] : [];

        $country = isset($first_location['country']) ? $first_location['country'] : '—';
        $region  = isset($first_location['region']) ? $first_location['region'] : '—';
        $city    = isset($first_location['city']) ? $first_location['city'] : '—';

        $game_type_raw = get_post_meta($post_id, 'game_type', true);
        $game_type = in_array($game_type_raw, ['In-Person', 'Virtual', 'Hybrid']) ? $game_type_raw : '—';

        $location_parts = [];
        if (!empty($country) && $country !== '—') {
            $location_parts[] = strtoupper($country);
        }
        if (!empty($region) && $region !== '—') {
            $location_parts[] = $region;
        }
        if (!empty($city) && $city !== '—') {
            $location_parts[] = $city;
        }
        $location_display = !empty($location_parts) ? implode(', ', $location_parts) : '—';
        $location_sort = strtoupper($country) . ',' . $region . ',' . $city;

        // Get and normalize genre (raw for debugging)
        $raw_genre = get_post_meta($post_id, 'genres', true);
        $genre_list = array_map('trim', is_array($raw_genre) ? $raw_genre : explode(',', (string) $raw_genre));
        $genre_display = implode(', ', $genre_list);
        $genre_classes = implode(' ', array_map('sanitize_html_class', $genre_list));

        $probationary = get_post_meta($post_id, 'chronicle_probationary', true) === '1' ? 'Yes' : 'No';

        $is_satellite = get_post_meta($post_id, 'chronicle_satellite', true) === '1';
        $parent_slug = '';
        if ($is_satellite) {
            $parent_slug_meta = get_post_meta($post_id, 'chronicle_parent', true); // <-- CORRECT KEY

            echo "<!-- DEBUG: using chronicle_parent = {$parent_slug_meta} -->";

            if (!empty($parent_slug_meta)) {
                $parent_post = get_page_by_path($parent_slug_meta, OBJECT, 'owbn_chronicle'); // or your CPT slug
                if ($parent_post) {
                    $parent_slug = esc_html(get_the_title($parent_post));
                } else {
                    $parent_slug = '[Unknown: ' . esc_html($parent_slug_meta) . ']';
                }
            } else {
                $parent_slug = '—';
            }
        }

        $chronicle_region = get_post_meta($post_id, 'chronicle_region', true);
        $chronicle_region = !empty($chronicle_region) ? $chronicle_region : '—';

        $view_url = esc_url(home_url('/chronicle/' . $slug));
        $row_class = ($row_index % 2 === 0) ? 'even' : 'odd';
        $row_class .= " country-" . sanitize_html_class($country);
        $row_class .= " region-" . sanitize_html_class($region);
        $row_class .= " city-" . sanitize_html_class($city);
        $row_class .= " genre-" . $genre_classes;
        $row_class .= $is_satellite ? ' is-satellite' : ' is-primary';

        echo "<div id=\"chron-{$slug}\" class=\"chron-wrapper {$row_class}\" 
            data-country=\"" . esc_attr($country) . "\" 
            data-region=\"" . esc_attr($region) . "\" 
            data-city=\"" . esc_attr($city) . "\" 
            data-genre=\"" . esc_attr($genre_classes) . "\" 
            data-game-type=\"" . esc_attr(strtolower($game_type)) . "\" 
            data-satellite=\"" . ($is_satellite ? 'yes' : 'no') . "\" 
            data-probationary=\"" . ($probationary === 'Yes' ? 'yes' : 'no') . "\" 
            data-chronicle-region=\"" . esc_attr($chronicle_region) . "\">\n";

        // Render each field as top-level grid cell
        echo "  <div class=\"chron-title\"><a href=\"{$view_url}\">" . esc_html(get_the_title($post_id)) . "</a></div>\n";
        echo "  <div class=\"chron-field chron-location\">" . esc_html($location_display) . "</div>\n";
        echo "  <div class=\"chron-field chron-region\">" . esc_html($chronicle_region) . "</div>\n";
        echo "  <div class=\"chron-field chron-genre\">" . esc_html($genre_display) . "</div>\n";
        echo "  <div class=\"chron-field chron-type\">" . esc_html($game_type) . "</div>\n";
        echo "  <div class=\"chron-field chron-probationary\">" . esc_html($probationary) . "</div>\n";

        if ($is_satellite && !empty($parent_slug_meta)) {
            $parent_url = esc_url(home_url('/chronicle/' . $parent_slug_meta));
            echo "  <div class=\"chron-field chron-satellite\"><a href=\"{$parent_url}\">" . esc_html($parent_slug) . "</a></div>\n";
        } else {
            echo "  <div class=\"chron-field chron-satellite\">No</div>\n";
        }

        echo "</div>\n";
        $row_index++;
    }

    echo "</div>\n";


    wp_reset_postdata();
    return ob_get_clean();
}
