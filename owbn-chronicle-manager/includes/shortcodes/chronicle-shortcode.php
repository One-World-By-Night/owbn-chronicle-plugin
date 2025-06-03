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
    return '<div class="owbn-chronicle-wrapper">' . wp_kses_post(owbn_render_chronicle_card($post_id)) . '</div>';
});

add_shortcode('owbn-chronicle-meta', function($atts) {
    $atts = shortcode_atts([
        'plug'  => '',
        'term'  => '',
        'label' => 'true', // default to showing label
    ], $atts);

    if (empty($atts['term'])) return '';

    // Resolve Chronicle slug (plug)
    $plug = $atts['plug'];

    if (empty($plug)) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/chronicles/([^/]+)/?#', $uri, $matches)) {
            $plug = sanitize_title($matches[1]);
        }
    }

    $post = null;
    if (!empty($plug)) {
        $post = get_page_by_path($plug, OBJECT, 'owbn_chronicle');
    }

    if (!$post) {
        global $post;
        if ($post && $post->post_type !== 'owbn_chronicle') {
            return '';
        }
    }

    if (!$post || $post->post_type !== 'owbn_chronicle') {
        return '';
    }

    $term = strtolower($atts['term']);
    $show_label = filter_var($atts['label'], FILTER_VALIDATE_BOOLEAN);

    // Dispatch map for rendering handlers
    $term_handlers = [
        'title'             => 'owbn_render_title',
        'session_list'      => 'owbn_render_session_list',
        'chronicle_slug'    => 'owbn_render_chronicle_slug',
        'hst_info'          => 'owbn_render_hst_info',
        'cm_info'           => 'owbn_render_cm_info',
        'ast_list'          => 'owbn_render_ast_list',
        'ooc_locations'     => 'owbn_render_ooc_locations',
        'ic_location_list'  => 'owbn_render_ic_location_list',
        'game_site_list'    => 'owbn_render_game_site_list',
        'document_links'    => 'owbn_render_document_links',
        'social_urls'       => 'owbn_render_social_urls',
        'email_lists'       => 'owbn_render_email_lists',
        'chronicle_status'  => 'owbn_render_chronicle_status',
    ];

    if (isset($term_handlers[$term]) && function_exists($term_handlers[$term])) {
        $content = call_user_func($term_handlers[$term], $post);
    } else {
        $content = owbn_chronicle_output_simple_meta($post, $term);
    }

    return owbn_chronicle_output_wrapper($term, $content, $show_label);
});

function owbn_chronicle_output_simple_meta($post, $term) {
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
        case 'chronicle_start_date':
        case 'chronicle_region':
            $output = esc_html(get_post_meta($post->ID, $term, true));
            break;

        case 'chronicle_probationary':
            $output = get_post_meta($post->ID, $term, true) ? 'Yes' : 'No';
            break;

        case 'chronicle_satellite':
            $is_satellite = get_post_meta($post->ID, 'chronicle_satellite', true);
            if (!$is_satellite) {
                $output = 'No';
            } else {
                $parent_slug_meta = get_post_meta($post->ID, 'chronicle_parent', true);
                if (!empty($parent_slug_meta)) {
                    $parent_url = esc_url(home_url('/chronicles/' . $parent_slug_meta));
                    $output = sprintf(
                        '<a href="%s">%s</a>',
                        $parent_url,
                        esc_html(strtoupper($parent_slug_meta))
                    );
                } else {
                    $output = 'Yes';
                }
            }
            break;

        case 'web_url':
            $web_url = get_post_meta($post->ID, 'web_url', true);
            $slug = $post->post_name; // or use get_post_field('post_name', $post->ID);
            $link_label = strtoupper($slug) . ' Website';

            $output = !empty($web_url)
                ? sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url($web_url), esc_html($link_label))
                : '—';
            break;

        case 'genres':
            $raw_genres = get_post_meta($post->ID, 'genres', true);
            $genre_list = array_map('trim', is_array($raw_genres) ? $raw_genres : explode(',', (string)$raw_genres));
            $filtered = array_filter($genre_list);

            $output = !empty($filtered)
                ? esc_html(implode(', ', $filtered))
                : '—';
            break;

        case 'premise':
        case 'game_theme':
        case 'game_mood':
        case 'traveler_info':
            $value = get_post_meta($post->ID, $term, true);
            $output = !empty($value)
                ? wp_kses_post(wpautop($value))
                : '-';
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

    return $output;
}

function owbn_render_title($post) {
    if (!$post instanceof WP_Post) {
        return '';
    }

    $title = esc_html(get_the_title($post));

    if (empty($title)) {
        return '';
    }

    return sprintf(
        "<div class=\"elementor-widget-container owbn-chronicle-title-container\">\n" .
        "  <h2 class=\"owbn-chronicle-title elementor-heading-title elementor-size-large\">%s</h2>\n" .
        "</div>\n",
        $title
    );
}

function owbn_render_session_list($post) {
    $session_list = get_post_meta($post->ID, 'session_list', true);

    if (!is_array($session_list) || empty($session_list)) {
        return "<div class=\"owbn-chronicle-session-list-empty elementor-widget-container\">\n" .
               "  <p>No sessions available.</p>\n" .
               "</div>\n";
    }

    ob_start();
    echo "<div class=\"owbn-chronicle-session-list\">\n";

    foreach ($session_list as $index => $session) {
        echo "  <div class=\"chronicle-session-block chronicle-session-{$index} elementor-widget-container\">\n";

        // Session Type
        if (!empty($session['session_type'])) {
            echo "    <div class=\"chronicle-session-type\"><strong>Session Type:</strong> " . esc_html($session['session_type']) . "</div>\n";
        }

        // Frequency
        if (!empty($session['frequency'])) {
            echo "    <div class=\"chronicle-session-frequency\"><strong>Frequency:</strong> " . esc_html($session['frequency']) . "</div>\n";
        }

        // Day
        if (!empty($session['day'])) {
            echo "    <div class=\"chronicle-session-day\"><strong>Day:</strong> " . esc_html($session['day']) . "</div>\n";
        }

        // Check-in Time
        if (!empty($session['checkin_time'])) {
            echo "    <div class=\"chronicle-session-checkin\"><strong>Check-in:</strong> " . esc_html($session['checkin_time']) . "</div>\n";
        }

        // Start Time
        if (!empty($session['start_time'])) {
            echo "    <div class=\"chronicle-session-start\"><strong>Start:</strong> " . esc_html($session['start_time']) . "</div>\n";
        }

        // Notes
        if (!empty($session['notes'])) {
            echo "    <div class=\"chronicle-session-notes\"><strong>Notes:</strong><br />\n" .
                 wp_kses_post(wpautop($session['notes'])) . "</div>\n";
        }

        // Genres
        if (!empty($session['genres'])) {
            $genres = is_array($session['genres']) ? $session['genres'] : explode(',', $session['genres']);
            $clean_genres = implode(', ', array_map('esc_html', array_filter(array_map('trim', $genres))));
            echo "    <div class=\"chronicle-session-genres\"><strong>Genres:</strong> {$clean_genres}</div>\n";
        }

        echo "  </div>\n";
    }

    echo "</div>\n";
    return ob_get_clean();
}

function owbn_render_hst_info($post) {
    if (!$post instanceof WP_Post) return '';

    $meta = get_post_meta($post->ID, 'hst_info', true);

    $id = '';
    $display_name = '';
    $email = '';

    // Handle string format (e.g., "3, TOP, devnull@host.local")
    if (is_string($meta)) {
        $parts = array_map('trim', explode(',', $meta));
        if (count($parts) >= 3) {
            [$id, $display_name, $email] = $parts;
        }
    }
    // Handle array format
    elseif (is_array($meta)) {
        $id = $meta['id'] ?? '';
        $display_name = $meta['display_name'] ?? '';
        $email = $meta['display_email'] ?? $meta['email'] ?? '';
    } else {
        return '';
    }

    if (empty($email) || empty($display_name)) return '';

    return sprintf(
        '<a href="mailto:%s">%s</a> %s',
        esc_attr($email),
        esc_html($display_name),
        esc_html($id)
    );
}

function owbn_render_cm_info($post) {
    if (!$post instanceof WP_Post) return '';

    $meta = get_post_meta($post->ID, 'cm_info', true);

    $id = '';
    $display_name = '';
    $email = '';

    // Handle string format: "3, TOP, devnull@host.local"
    if (is_string($meta)) {
        $parts = array_map('trim', explode(',', $meta));
        if (count($parts) >= 3) {
            [$id, $display_name, $email] = $parts;
        }
    }
    // Handle associative array format
    elseif (is_array($meta)) {
        $id = $meta['id'] ?? '';
        $display_name = $meta['display_name'] ?? '';
        $email = $meta['display_email'] ?? $meta['email'] ?? '';
    }

    if (empty($display_name) || empty($email)) return '';

    return sprintf(
        '<a href="mailto:%s">%s</a> %s',
        esc_attr($email),
        esc_html($display_name),
        esc_html($id)
    );
}

function owbn_render_ast_list($post) {
    if (!$post instanceof WP_Post) {
        return '';
    }

    $ast_list = get_post_meta($post->ID, 'ast_list', true);

    if (!is_array($ast_list) || empty($ast_list)) {
        return "<div class=\"owbn-chronicle-ast-list-empty elementor-widget-container\">\n" .
               "  <p>No ASTs listed.</p>\n" .
               "</div>\n";
    }

    ob_start();
    echo "<div class=\"owbn-chronicle-meta-ast_list\">\n";
    echo "  <div class=\"elementor-widget-container chronicle-ast-list-group\">\n";

    foreach ($ast_list as $index => $ast) {
        $name  = trim($ast['display_name'] ?? '');
        $email = trim($ast['display_email'] ?? ''); // only use display_email
        $role  = trim($ast['role'] ?? '');

        if (!empty($name)) {
            echo "    <div class=\"chron-ast-entry chron-ast-{$index}\">\n";
            echo "      ";

            if (!empty($role)) {
                echo esc_html($role) . ' ';
            }

            if (!empty($email)) {
                echo "<a href=\"mailto:" . esc_attr($email) . "\" class=\"chron-ast-email-link\">" . esc_html($name) . "</a>\n";
            } else {
                echo esc_html($name) . "\n";
            }

            echo "    </div>\n";
        }
    }

    echo "  </div>\n";
    echo "</div>\n";

    return ob_get_clean();
}

function owbn_render_ooc_locations($post) {
    if (!$post instanceof WP_Post) {
        return '';
    }

    $location = get_post_meta($post->ID, 'ooc_locations', true);

    if (!is_array($location) || empty(array_filter($location))) {
        return "<div class=\"owbn-chronicle-ooc-list-empty elementor-widget-container\">\n" .
               "  <p>No locations listed.</p>\n" .
               "</div>\n";
    }

    ob_start();
    echo "<div class=\"owbn-chronicle-ooc-locations\">\n";
    echo "  <div class=\"chronicle-location-block elementor-widget-container\">\n";

    // Collect non-empty location parts
    $parts = [];
    if (!empty($location['city'])) {
        $parts[] = esc_html($location['city']);
    }
    if (!empty($location['region'])) {
        $parts[] = esc_html($location['region']);
    }
    if (!empty($location['country'])) {
        $parts[] = esc_html(strtoupper($location['country']));
    }

    // Output location line if any parts exist
    if (!empty($parts)) {
        echo "    <div class=\"chron-location-line elementor-heading-title elementor-size-default\">\n";
        echo "      " . implode(', ', $parts) . "\n";
        echo "    </div>\n";
    }

    // Output notes if they exist
    if (!empty($location['notes'])) {
        echo "    <div class=\"chron-location-notes\">\n";
        echo "      " . wp_kses_post(wpautop($location['notes'])) . "\n";
        echo "    </div>\n";
    }

    echo "  </div>\n";
    echo "</div>\n";
    return ob_get_clean();
}

function owbn_render_ic_location_list($post) {
    if (!$post instanceof WP_Post) {
        return '';
    }

    $locations = get_post_meta($post->ID, 'ic_location_list', true);

    if (!is_array($locations) || empty($locations)) {
        return "<div class=\"owbn-chronicle-ic-location-empty elementor-widget-container\">\n" .
               "  <p>No IC locations listed.</p>\n" .
               "</div>\n";
    }

    ob_start();
    echo "<div class=\"owbn-chronicle-meta-ic_location_list\">\n";

    foreach ($locations as $index => $location) {
        echo "  <div class=\"owbn-chronicle-meta-ic_location_list-entry chronicle-ic-location-{$index} elementor-widget-container\">\n";

        // Name Line
        if (!empty($location['name'])) {
            echo "    <div class=\"location-field location-name elementor-heading-title elementor-size-default\">\n";
            echo "      <strong>Name:</strong> " . esc_html($location['name']) . "\n";
            echo "    </div>\n";
        }

        // City, Region (Country) Line
        $parts = [];

        if (!empty($location['city'])) {
            $parts[] = esc_html($location['city']);
        }
        if (!empty($location['region'])) {
            $parts[] = esc_html($location['region']);
        }

        $location_line = implode(', ', $parts);

        if (!empty($location['country'])) {
            $location_line .= $location_line ? " (" . esc_html($location['country']) . ")" : esc_html($location['country']);
        }

        if ($location_line) {
            echo "    <div class=\"location-field location-summary\">\n";
            echo "      $location_line\n";
            echo "    </div>\n";
        }

        echo "  </div>\n";
    }

    echo "</div>\n";
    return ob_get_clean();
}

function owbn_render_game_site_list($post) {
    if (!$post instanceof WP_Post) {
        return '';
    }

    $sites = get_post_meta($post->ID, 'game_site_list', true);

    if (!is_array($sites) || empty($sites)) {
        return "<div class=\"owbn-chronicle-game-site-empty elementor-widget-container\">\n" .
               "  <p>No Game Sites listed.</p>\n" .
               "</div>\n";
    }

    ob_start();
    echo "<div class=\"owbn-chronicle-meta-game_site_list\">\n";

    foreach ($sites as $index => $site) {
        $online = !empty($site['online']) || !empty($site['online_only']);
        $url = !empty($site['url']) ? esc_url($site['url']) : '';

        echo "  <div class=\"owbn-chronicle-meta-game_site_list-entry chronicle-game-site-{$index} elementor-widget-container\" style=\"margin-bottom: 1.5em;\">\n";

        // ---- Site Name (always shown) ----
        if (!empty($site['name'])) {
            echo "    <div class=\"site-field site-name elementor-heading-title elementor-size-default\">\n";
            echo "      <strong>Name:</strong> ";

            if ($online && $url) {
                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($site['name']) . '</a>';
            } else {
                echo esc_html($site['name']);
            }

            echo "\n    </div>\n";
        }

        // ---- Online site: just show URL ----
        if ($online && $url) {
            echo "    <div class=\"site-field site-url\">\n";
            echo "      <a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\">{$url}</a>\n";
            echo "    </div>\n";
        }
        // ---- Physical site: address block ----
        elseif (!$online) {
            $address = !empty($site['address']) ? esc_html($site['address']) : '';
            $city = !empty($site['city']) ? esc_html($site['city']) : '';
            $region = !empty($site['region']) ? esc_html($site['region']) : '';
            $country = !empty($site['country']) ? esc_html($site['country']) : '';

            $city_region = implode(', ', array_filter([$city, $region]));
            $final_line = $city_region;
            if (!empty($country)) {
                $final_line .= $city_region ? " ({$country})" : $country;
            }

            if (!empty($address) || !empty($final_line)) {
                echo "    <div class=\"site-field site-address\">\n";
                if ($address) {
                    echo "      {$address}<br />\n";
                }
                if ($final_line) {
                    echo "      {$final_line}\n";
                }
                echo "    </div>\n";
            }
        }

        // ---- Notes (optional) ----
        if (!empty($site['notes'])) {
            echo "    <div class=\"site-field site-notes\">\n";
            echo "      <strong>Game Site Notes:</strong><br />\n" . wp_kses_post(wpautop($site['notes'])) . "\n";
            echo "    </div>\n";
        }

        echo "  </div>\n";
    }

    echo "</div>\n";
    return ob_get_clean();
}

function owbn_render_document_links($post) {
    if (!$post instanceof WP_Post) {
        return '';
    }

    $document_links = get_post_meta($post->ID, 'document_links', true);

    if (!is_array($document_links) || empty($document_links)) {
        return "<div class=\"owbn-chronicle-document-links-empty elementor-widget-container\">\n" .
               "  <p>No documents available.</p>\n" .
               "</div>\n";
    }

    $document_icons = [
        'pdf'     => 'fa-solid fa-file-pdf',
        'doc'     => 'fa-solid fa-file-word',
        'docx'    => 'fa-solid fa-file-word',
        'xls'     => 'fa-solid fa-file-excel',
        'xlsx'    => 'fa-solid fa-file-excel',
        'txt'     => 'fa-solid fa-file-lines',
        'link'    => 'fas fa-link',
        'default' => 'fa-solid fa-file',
    ];

    ob_start();
    echo "<div class=\"owbn-chronicle-meta-document_links\">\n";

    foreach ($document_links as $index => $doc) {
        $doc_title = $doc['title'] ?? '';
        $url       = '';
        $icon      = $document_icons['default'];

        if (!empty($doc['upload'])) {
            $url = wp_get_attachment_url($doc['upload']);
            $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
            $icon = $document_icons[$ext] ?? $document_icons['default'];
        } elseif (!empty($doc['link'])) {
            $url = $doc['link'];
            $icon = $document_icons['link'];
        }

        if (!empty($doc_title) && !empty($url)) {
            echo "  <div class=\"chronicle-document-entry chronicle-document-{$index} elementor-widget-container\">\n";
            echo "    <a href=\"" . esc_url($url) . "\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"chronicle-document-link\">\n";
            echo "      <i class=\"" . esc_attr($icon) . "\" style=\"margin-right: 0.5rem;\"></i> " . esc_html($doc_title) . "\n";
            echo "    </a>\n";
            echo "  </div>\n";
        }
    }

    echo "</div>\n";
    return ob_get_clean();
}

function owbn_render_social_urls($post) {
    if (!$post instanceof WP_Post) {
        return '';
    }

    $social_links = get_post_meta($post->ID, 'social_urls', true);

    if (!is_array($social_links) || empty($social_links)) {
        return "<div class=\"owbn-chronicle-social-urls-empty elementor-widget-container\">\n" .
               "  <p>No social media links available.</p>\n" .
               "</div>\n";
    }

    $platform_icons = [
        'facebook'  => 'fa-brands fa-facebook',
        'twitter'   => 'fa-brands fa-x-twitter',
        'instagram' => 'fa-brands fa-instagram',
        'linkedin'  => 'fa-brands fa-linkedin',
        'youtube'   => 'fa-brands fa-youtube',
        'tiktok'    => 'fa-brands fa-tiktok',
        'discord'   => 'fa-brands fa-discord',
        'twitch'    => 'fa-brands fa-twitch',
        'reddit'    => 'fa-brands fa-reddit',
        'threads'   => 'fa-brands fa-threads',
        'mastodon'  => 'fa-brands fa-mastodon',
        'bluesky'   => 'fa-brands fa-bluesky',
        'custom'    => 'fas fa-link',
    ];

    ob_start();
    echo "<div class=\"owbn-chronicle-meta-social_urls\">\n";
    echo "  <div class=\"elementor-widget-container chronicle-social-link-group\">\n";

    foreach ($social_links as $index => $link) {
        $platform = $link['platform'] ?? '';
        $url = $link['url'] ?? '';

        if (!empty($platform) && !empty($url)) {
            $icon_class = $platform_icons[$platform] ?? 'fas fa-globe';

            echo "    <a href=\"" . esc_url($url) . "\" class=\"chronicle-social-link chronicle-social-{$platform}\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"margin-right: 0.75rem; display: inline-block;\">\n";
            echo "      <i class=\"" . esc_attr($icon_class) . "\" aria-hidden=\"true\"></i><span class=\"screen-reader-text\">" . esc_html(ucfirst($platform)) . "</span>\n";
            echo "    </a>\n";
        }
    }

    echo "  </div>\n";
    echo "</div>\n";
    return ob_get_clean();
}

function owbn_render_email_lists($post) {
    if (!$post instanceof WP_Post) {
        return '';
    }

    $email_lists = get_post_meta($post->ID, 'email_lists', true);

    if (!is_array($email_lists) || empty($email_lists)) {
        return "<div class=\"owbn-chronicle-email-lists-empty elementor-widget-container\">\n" .
               "  <p>No email lists available.</p>\n" .
               "</div>\n";
    }

    ob_start();
    echo "<div class=\"owbn-chronicle-meta-email_lists\">\n";
    echo "  <div class=\"elementor-widget-container chronicle-email-list-group\">\n";

    foreach ($email_lists as $index => $entry) {
        $name = trim($entry['list_name'] ?? '');
        $email = trim($entry['email_address'] ?? '');
        $desc = trim($entry['description'] ?? '');

        if (!empty($name) && !empty($email)) {
            echo "    <div class=\"chron-email-entry chron-email-{$index}\">\n";
            echo "      <a href=\"mailto:" . esc_attr($email) . "\" class=\"email-list-link\" style=\"margin-right: 0.5rem; display: inline-block;\">\n";
            echo "        <i class=\"fa-solid fa-envelope\" aria-hidden=\"true\"></i> <span class=\"email-list-name\">" . esc_html($name) . "</span>\n";
            echo "      </a>\n";

            if (!empty($desc)) {
                echo "      <div class=\"email-list-desc\">" . wp_kses_post(wpautop($desc)) . "</div>\n";
            }

            echo "    </div>\n";
        }
    }

    echo "  </div>\n";
    echo "</div>\n";
    return ob_get_clean();
}

function owbn_render_chronicle_status($post) {
    if (!$post instanceof WP_Post) return '';

    $lines = [];

    // Probationary status
    $is_probationary = get_post_meta($post->ID, 'chronicle_probationary', true);
    if ($is_probationary) {
        $lines[] = 'Probationary Game';
    } else {
        $lines[] = 'Full Game';
    }

    // Satellite status
    $is_satellite = get_post_meta($post->ID, 'chronicle_satellite', true);
    if ($is_satellite) {
        $parent_slug = get_post_meta($post->ID, 'chronicle_parent', true);

        if (!empty($parent_slug)) {
            $parent_url = esc_url(home_url('/chronicles/' . sanitize_title($parent_slug)));
            $parent_link = sprintf(
                '<a href="%s">%s</a>',
                $parent_url,
                esc_html(strtoupper($parent_slug))
            );
            $lines[] = 'Satellite of ' . $parent_link;
        } else {
            $lines[] = 'Satellite Game';
        }
    }

    // Wrap each line in a div for spacing
    return implode("<br />\n", array_map('wp_kses_post', $lines));
}


function owbn_chronicle_output_wrapper($term, $content, $show_label = true) {
    if (empty(trim($content))) return ''; // Skip rendering if content is empty

    // Load field definitions
    $definitions = function_exists('owbn_get_chronicle_field_definitions')
        ? owbn_get_chronicle_field_definitions()
        : [];

    $label = ucwords(str_replace('_', ' ', $term)); // Default label
    $field_type = ''; // Used to determine layout style

    foreach ($definitions as $section => $fields) {
        if (isset($fields[$term])) {
            if (!empty($fields[$term]['label'])) {
                $label = $fields[$term]['label'];
            }
            $field_type = $fields[$term]['type'] ?? '';
            break;
        }
    }

    $is_block = in_array($field_type, ['textarea', 'wysiwyg']);

    // Compose label markup
    $label_markup = '';
    if ($show_label) {
        if ($is_block) {
            // Label followed by line break
            $label_markup = sprintf(
                "<div class=\"owbn-chronicle-meta-label owbn-chronicle-meta-label-%s elementor-heading-title elementor-size-small\">%s:</div>\n",
                esc_attr($term),
                esc_html($label)
            );
        } else {
            // Label and content inline
            $label_markup = sprintf(
                "<div class=\"owbn-chronicle-meta-label owbn-chronicle-meta-label-%s elementor-heading-title elementor-size-small\">%s: %s</div>\n",
                esc_attr($term),
                esc_html($label),
                $content
            );

            // If inline, we've already output the content, so skip the main wrapper
            return sprintf(
                "<div class=\"owbn-chronicle-meta owbn-chronicle-meta-%s elementor-widget-container\">\n%s</div>\n",
                esc_attr($term),
                $label_markup
            );
        }
    }

    // Block layout (label above content)
    return sprintf(
        "<div class=\"owbn-chronicle-meta owbn-chronicle-meta-%s elementor-widget-container\">\n%s%s\n</div>\n",
        esc_attr($term),
        $label_markup,
        $content
    );
}