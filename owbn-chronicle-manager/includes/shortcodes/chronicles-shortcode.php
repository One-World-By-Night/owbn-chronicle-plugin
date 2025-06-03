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
    echo "  <div>\n";
    echo "    <select id=\"filter-satellite\" class=\"owbn-select2 single\" data-filter=\"satellite\">\n";
    echo "      <option value=\"\">Filter by Satellite</option>\n";
    echo "      <option value=\"yes\">Satellite</option>\n";
    echo "      <option value=\"no\">Not Satellite</option>\n";
    echo "    </select>\n";
    echo "  </div>\n";
    echo "  <div>\n";
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

        $game_type_raw = get_post_meta($post_id, 'game_type', true);
        $game_type = in_array($game_type_raw, ['In-Person', 'Virtual', 'Hybrid']) ? $game_type_raw : '—';

        $ooc_locations = get_post_meta($post_id, 'ooc_locations', true);
        $first_location = is_array($ooc_locations) && !empty($ooc_locations) ? $ooc_locations : [];

        $city    = !empty($first_location['city']) ? trim($first_location['city']) : '';
        $region  = !empty($first_location['region']) ? trim($first_location['region']) : '';
        $country = !empty($first_location['country']) ? strtoupper(trim($first_location['country'])) : '';

        $location_parts = array_filter([$city, $region, $country]);
        $location_display = implode(', ', $location_parts);
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

////////////////////////////////////
// owbn-chronicles-list shortcode //
////////////////////////////////////
function owbn_force_enqueue_assets() {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    owbn_enqueue_plugin_assets();
}

add_shortcode('owbn-chronicles-list', function($atts) {
    owbn_force_enqueue_assets(); // ← this ensures scripts/styles are loaded

    $atts = shortcode_atts([
        'columns' => 'title,ooc_locations,chronicle_region,genres,game_type,chronicle_probationary,chronicle_satellite',
        'filter' => 'ooc_locations-country,chronicle_region,genres,game_type,probationary,satellite',
        // Flat keys supported for filtering
        'plug' => '',
        'region' => '',
        'genre' => '',
        'genres' => '',
        'country' => '',
        'state' => '',
        'game_type' => '',
        'probationary' => '',
        'satellite' => '',
        'chronicle_region' => '',
    ], $atts);

    // Normalize comma-separated columns and filters
    $column_keys = array_map('trim', explode(',', $atts['columns']));
    $filter_keys = array_filter(array_map('trim', explode(',', $atts['filter'])));

    // Fetch matching chronicles
    $query = owbn_get_chronicle_query($atts);

    ob_start();
    echo '<div class="owbn-chronicle-list">';

    // Optional: dynamic filters dropdown
    if (!empty($filter_keys)) {
        echo owbn_render_chronicle_filters($filter_keys);
    }

    // Table output
    if ($query->have_posts()) {
        echo owbn_render_chronicle_table($query, $column_keys);
    } else {
        echo '<p>No chronicles found.</p>';
    }

    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
});

function owbn_get_chronicle_query($atts) {
    $meta_map = [
        'plug' => 'chronicle_slug',
        'region' => 'region',
        'genre' => 'genres',
        'genres' => 'genres',
        'country' => 'country',
        'state' => 'state',
        'game_type' => 'game_type',
        'probationary' => 'chronicle_probationary',
        'satellite' => 'chronicle_satellite',
        'chronicle_region' => 'chronicle_region',
    ];

    $meta_query = [];

    foreach ($atts as $att => $value) {
        if (empty($value)) continue;

        if (strpos($att, '.') !== false) {
            list($group, $subkey) = explode('.', $att, 2);
            $search = '"' . $subkey . '";s:' . strlen($value) . ':"' . $value . '"';
            $meta_query[] = [
                'key' => $group,
                'value' => $search,
                'compare' => 'LIKE',
            ];
        } elseif (isset($meta_map[$att])) {
            $meta_key = $meta_map[$att];
            if ($att === 'genre') {
                $value = '"' . $value . '"';
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

    return new WP_Query($args);
}

function owbn_render_chronicle_filters($filter_keys) {
    ob_start();
    echo "<div class=\"owbn-chronicles-list-filters\">\n";

    foreach ($filter_keys as $filter) {
        $label = ucwords(str_replace(['_', ':'], [' ', ' '], $filter));
        $filter_id = 'filter-' . strtolower(str_replace(['_', ':'], '-', $filter));

        switch ($filter) {
            case 'probationary':
            case 'chronicle_probationary':
                echo "  <select id=\"$filter_id\" class=\"owbn-select2 single\" data-filter=\"chronicle-probationary\">\n";
                echo "    <option value=\"\">Filter by Probationary</option>\n";
                echo "  </select>\n";
                break;

            case 'satellite':
            case 'chronicle_satellite':
                echo "  <select id=\"$filter_id\" class=\"owbn-select2 single\" data-filter=\"chronicle-satellite\">\n";
                echo "    <option value=\"\">Filter by Satellite</option>\n";
                echo "  </select>\n";
                break;

            default:
                $data_filter = strtolower(str_replace(['_', ':'], '-', $filter));
                echo "  <select id=\"$filter_id\" class=\"owbn-select2\" data-filter=\"$data_filter\">\n";
                echo "    <option value=\"\">Filter by $label</option>\n";
                echo "  </select>\n";
        }
    }

    echo "  <button id=\"clear-filters\">Clear Filters</button>\n";
    echo "</div>\n";
    return ob_get_clean();
}

function owbn_render_chronicle_table($query, $column_keys) {
    ob_start();

    echo "<div class=\"owbn-chronicle-legend\">\n";
    foreach ($column_keys as $col) {
        $label = ucwords(str_replace(['_', ':'], [' ', ' '], $col));
        echo "  <div class=\"chron-header\">$label</div>\n";
    }
    echo "</div>\n";

    echo "<div class=\"chronicle-rows\">\n";
    $index = 0;

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $row_class = ($index++ % 2 === 0) ? 'even' : 'odd';

        echo "  <div class=\"chron-list-wrapper {$row_class}\" " . owbn_extract_data_attributes($post_id, $column_keys) . ">";
        foreach ($column_keys as $col) {
            echo "    " . owbn_render_chronicle_column($post_id, $col);
        }
        echo "  </div>\n";
    }

    echo "</div>\n";
    return ob_get_clean();
}

function owbn_extract_data_attributes($post_id, $column_keys) {
    $attrs = [];

    foreach ($column_keys as $key) {
        $attr_key = strtolower(str_replace(['.', '_'], '-', $key));
        $values = [];

        // Handle grouped location fields dynamically
        if (in_array($key, ['game_site_list', 'ic_location_list', 'ooc_locations'], true)) {
            // Allow the location function to push data-* attributes directly
            $location_attrs = [];
            owbn_get_chronicle_location($post_id, $key, $location_attrs);

            // Append any location-based data attributes
            foreach ($location_attrs as $attr) {
                $attrs[] = $attr;
            }

            continue;
        }

        // Dot notation support for nested meta fields
        if (strpos($key, '.') !== false) {
            list($group_key, $sub_keys) = parse_nested_meta_key($key);
            $meta = get_post_meta($post_id, $group_key, true);

            if (is_array($meta)) {
                foreach ($meta as $entry) {
                    if (!is_array($entry)) continue;
                    foreach ((array)$sub_keys as $sub_key) {
                        if (!empty($entry[$sub_key])) {
                            $values[] = trim((string)$entry[$sub_key]);
                        }
                    }
                }
            }
        } else {
            $meta = get_post_meta($post_id, $key, true);

            if (in_array($meta, ['0', '1'], true)) {
                $values[] = $meta === '1' ? 'yes' : 'no';
            } elseif (is_array($meta)) {
                $values = array_map('trim', array_filter($meta));
            } elseif (!empty($meta)) {
                $values[] = trim((string)$meta);
            }
        }

        // Space-separated format to support multi-value filters like genres
        $joined = '';
        if ($key === 'genres') {
            // Comma-separated for genre filter compatibility
            $joined = implode(', ', array_unique(array_filter($values)));
        } else {
            // Default: space-separated
            $joined = implode(' ', array_unique(array_filter($values)));
        }

        if ($joined !== '') {
            $attrs[] = "data-{$attr_key}=\"" . esc_attr($joined) . "\"";
        }
    }

    return implode(' ', $attrs);
}

function owbn_render_chronicle_column($post_id, $col_key) {
    $value = owbn_get_chronicle_field_value($post_id, $col_key);
    return "<div class=\"chron-field chron-{$col_key}\">{$value}</div>\n";
}

function owbn_get_chronicle_field_value($post_id, $field_key) {
    // 1. Dot Notation Support (e.g. ast_list.role,display_name)
    if (strpos($field_key, '.') !== false) {
        list($group_key, $field_keys) = parse_nested_meta_key($field_key);
        return owbn_render_nested_meta($post_id, $group_key, $field_keys);
    }

    // 2. Special Cases
    switch ($field_key) {
        case 'title':
            $title = get_the_title($post_id);
            $slug = get_post_meta($post_id, 'chronicle_slug', true);
            if (!$slug) {
                $slug = basename(get_permalink($post_id)); // fallback
            }
            $url = esc_url(home_url('/chronicles/' . $slug));
            return '<a href="' . $url . '">' . esc_html($title) . '</a>';

        case 'chronicle_probationary':
            return get_post_meta($post_id, $field_key, true) === '1' ? 'Yes' : 'No';

        case 'chronicle_satellite':
            $is_satellite = get_post_meta($post_id, 'chronicle_satellite', true) === '1';
            if (!$is_satellite) {
                return "No";
            }
            $parent_slug_meta = get_post_meta($post_id, 'chronicle_parent', true);
            if (!empty($parent_slug_meta)) {
                $parent_url = esc_url(home_url('/chronicle/' . $parent_slug_meta));
                return "<a href=\"{$parent_url}\">" . esc_html(strtoupper($parent_slug_meta)) . "</a>";
            }
            return "<em>No parent set</em>";

        case 'genres':
        case 'genre':
            $raw = get_post_meta($post_id, 'genres', true);
            if (is_array($raw)) {
                return implode(', ', array_map('esc_html', array_filter($raw)));
            }
            return esc_html(trim((string) $raw)) . "";

        case 'game_type':
            $game_type = get_post_meta($post_id, 'game_type', true);
            $valid = ['In-Person', 'Virtual', 'Hybrid'];
            return in_array($game_type, $valid, true) ? esc_html($game_type) : '';

        case 'chronicle_region':
        case 'region':
            return esc_html(trim((string) get_post_meta($post_id, $field_key, true)));

        // Grouped Fields (no dot notation)
        case 'ast_list':
            return owbn_render_nested_meta($post_id, 'ast_list');

        case 'game_site_list':
            return owbn_get_chronicle_location($post_id, 'game_site_list');

        case 'ic_location_list':
            return owbn_get_chronicle_location($post_id, 'ic_location_list');

        case 'ooc_locations':
            return owbn_get_chronicle_location($post_id, 'ooc_locations');

        case 'session_list':
            return owbn_render_nested_meta($post_id, 'session_list', 'session_type');

        case 'social_urls':
            return owbn_render_nested_meta($post_id, 'social_urls', 'platform');

        case 'email_lists':
            return owbn_render_nested_meta($post_id, 'email_lists', 'list_name');
    }

    // 3. Default Fallback
    $meta = get_post_meta($post_id, $field_key, true);
    if (is_array($meta)) {
        $filtered = array_filter(array_map('trim', $meta));
        return esc_html(implode(', ', $filtered));
    }

    return esc_html(trim((string) $meta));
}

function owbn_render_nested_meta($post_id, $group_key, $sub_keys = null) {
    $data = get_post_meta($post_id, $group_key, true);
    if (!is_array($data)) return '';

    $parts = [];

    // Normalize to array
    if (!is_array($sub_keys) && $sub_keys !== null) {
        $sub_keys = [$sub_keys];
    }

    foreach ($data as $entry) {
        if (!is_array($entry)) continue;

        // AST list: custom format
        if ($group_key === 'ast_list') {
            $display = esc_html($entry['display_name'] ?? '');
            $role = esc_html($entry['role'] ?? '');
            $email = sanitize_email($entry['display_email'] ?? '');

            if (!$display) continue;

            $linked = $email ? "<a href='mailto:$email'>$display</a>" : $display;
            $parts[] = $role ? "$role: $linked" : $linked;
            continue;
        }

        // General case
        if ($sub_keys) {
            $row_parts = [];

            foreach ($sub_keys as $sub_key) {
                if (!empty($entry[$sub_key])) {
                    $row_parts[] = esc_html(trim($entry[$sub_key]));
                }
            }

            if (!empty($row_parts)) {
                $parts[] = implode(' – ', $row_parts);
            }
        }
    }

    return implode("<br>\n", array_filter($parts));
}

function owbn_get_chronicle_location($post_id, $meta_key, &$data_attrs = null) {
    $meta = get_post_meta($post_id, $meta_key, true);

    // Normalize: convert flat structure to array of one
    if (isset($meta['city']) || isset($meta['region']) || isset($meta['country'])) {
        $meta = [$meta];
    }

    if (!is_array($meta) || empty($meta)) {
        if (is_array($data_attrs)) {
            $data_attrs[] = 'data-country=""'; // ensure filter still works
        }
        return '&ndash;';
    }

    $locations = [];
    $city_vals = [];
    $region_vals = [];
    $country_vals = [];

    foreach ($meta as $entry) {
        if (!is_array($entry)) continue;

        $city = isset($entry['city']) ? trim($entry['city']) : '';
        $region = isset($entry['region']) ? trim($entry['region']) : '';
        $country = isset($entry['country']) ? trim($entry['country']) : '';

        if ($city) $city_vals[] = $city;
        if ($region) $region_vals[] = $region;
        if ($country) $country_vals[] = $country;

        $label_parts = array_filter([$city, $region]);
        $text = implode(', ', $label_parts);
        if ($country) {
            $text .= " ({$country})";
        }

        if ($text !== '') {
            $locations[] = esc_html($text);
        }
    }

    // Append data attributes for filtering
    if (is_array($data_attrs)) {
        $prefix = strtolower(str_replace(['.', '_'], '-', $meta_key));

        if (!empty($city_vals)) {
            $data_attrs[] = 'data-' . $prefix . '-city="' . esc_attr(implode(' ', array_unique($city_vals))) . '"';
        }
        if (!empty($region_vals)) {
            $data_attrs[] = 'data-' . $prefix . '-region="' . esc_attr(implode(' ', array_unique($region_vals))) . '"';
        }
        if (!empty($country_vals)) {
            $joined = esc_attr(implode(' ', array_unique($country_vals)));
            $data_attrs[] = 'data-' . $prefix . '-country="' . $joined . '"';
            $data_attrs[] = 'data-country="' . $joined . '"'; // global country filter
        }
    }

    return !empty($locations) ? implode("<br>\n", $locations) : '&ndash;';
}