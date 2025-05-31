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

        case 'session_list':
            $session_list = get_post_meta($post->ID, 'session_list', true);
            
            if (!is_array($session_list) || empty($session_list)) {
                $output = 'No sessions available.';
                break;
            }

            ob_start();
            echo "<div class=\"owbn-chronicle-meta-session_list\">\n";
            foreach ($session_list as $index => $session) {
                echo "  <div class=\"chronicle-session-block chronicle-session-{$index}\">\n";

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

                // Notes (WYSIWYG)
                if (!empty($session['notes'])) {
                    echo "    <div class=\"chronicle-session-notes\"><strong>Notes:</strong> " . wp_kses_post(wpautop($session['notes'])) . "</div>\n";
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

            $output = ob_get_clean();
            break;            

        case 'ast_list':
            $ast_list = get_post_meta($post->ID, 'ast_list', true);

            if (!is_array($ast_list) || empty($ast_list)) {
                $output = 'No ASTs listed.';
                break;
            }

            ob_start();
            echo "<div class=\"owbn-chronicle-meta-ast_list\">\n";

            foreach ($ast_list as $index => $ast) {
                echo "  <div class=\"chronicle-ast-block chronicle-ast-{$index}\">\n";

                // User (user ID to name/email)
                if (!empty($ast['user']) && is_numeric($ast['user'])) {
                    $user_obj = get_userdata($ast['user']);
                    if ($user_obj) {
                        echo "    <div class=\"chronicle-ast-user\"><strong>User:</strong> " . esc_html($user_obj->display_name) . " (" . esc_html($user_obj->user_email) . ")</div>\n";
                    }
                }

                // Display Name
                if (!empty($ast['display_name'])) {
                    echo "    <div class=\"chronicle-ast-display-name\"><strong>Display Name:</strong> " . esc_html($ast['display_name']) . "</div>\n";
                }

                // Email
                if (!empty($ast['email'])) {
                    echo "    <div class=\"chronicle-ast-email\"><strong>Email:</strong> <a href=\"mailto:" . esc_attr($ast['email']) . "\">" . esc_html($ast['email']) . "</a></div>\n";
                }

                // Role
                if (!empty($ast['role'])) {
                    echo "    <div class=\"chronicle-ast-role\"><strong>Role:</strong> " . esc_html($ast['role']) . "</div>\n";
                }

                echo "  </div>\n";
            }

            echo "</div>\n";

            $output = ob_get_clean();
            break;

        case 'ooc_locations':
            $locations = get_post_meta($post->ID, 'ooc_locations', true);

            if (!is_array($locations) || empty($locations)) {
                $output = 'No locations listed.';
                break;
            }

            ob_start();
            echo "<div class=\"owbn-chronicle-meta-ooc_locations\">\n";

            foreach ($locations as $index => $loc) {
                echo "  <div class=\"chronicle-location-block chronicle-ooc-{$index}\">\n";

                if (!empty($loc['name'])) {
                    echo "    <div class=\"chron-location-name\"><strong>Location Name:</strong> " . esc_html($loc['name']) . "</div>\n";
                }
                if (!empty($loc['online_only'])) {
                    echo "    <div class=\"chron-location-online\"><strong>Online Only:</strong> Yes</div>\n";
                } else {
                    echo "    <div class=\"chron-location-online\"><strong>Online Only:</strong> No</div>\n";
                }
                if (!empty($loc['country'])) {
                    echo "    <div class=\"chron-location-country\"><strong>Country:</strong> " . esc_html(strtoupper($loc['country'])) . "</div>\n";
                }
                if (!empty($loc['region'])) {
                    echo "    <div class=\"chron-location-region\"><strong>Region:</strong> " . esc_html($loc['region']) . "</div>\n";
                }
                if (!empty($loc['city'])) {
                    echo "    <div class=\"chron-location-city\"><strong>City:</strong> " . esc_html($loc['city']) . "</div>\n";
                }
                if (!empty($loc['address'])) {
                    echo "    <div class=\"chron-location-address\"><strong>Address:</strong> " . esc_html($loc['address']) . "</div>\n";
                }
                if (!empty($loc['notes'])) {
                    echo "    <div class=\"chron-location-notes\"><strong>Notes:</strong><br>" . wp_kses_post(wpautop($loc['notes'])) . "</div>\n";
                }

                echo "  </div>\n";
            }

            echo "</div>\n";
            $output = ob_get_clean();
            break;

        case 'ic_location_list':
            $locations = get_post_meta($post->ID, 'ic_location_list', true);
            if (!is_array($locations) || empty($locations)) {
                $output = "No IC locations listed.";
                break;
            }

            $output = '';
            foreach ($locations as $location) {
                $output .= "<div class=\"owbn-chronicle-meta-ic_location_list-entry\">\n";

                if (!empty($location['name'])) {
                    $output .= "<div class=\"location-field location-name\"><strong>Site Name:</strong> " . esc_html($location['name']) . "</div>\n";
                }

                if (!empty($location['country'])) {
                    $output .= "<div class=\"location-field location-country\"><strong>Country:</strong> " . esc_html($location['country']) . "</div>\n";
                }

                if (!empty($location['region'])) {
                    $output .= "<div class=\"location-field location-region\"><strong>Region:</strong> " . esc_html($location['region']) . "</div>\n";
                }

                if (!empty($location['city'])) {
                    $output .= "<div class=\"location-field location-city\"><strong>City:</strong> " . esc_html($location['city']) . "</div>\n";
                }

                if (!empty($location['address'])) {
                    $output .= "<div class=\"location-field location-address\"><strong>Address:</strong> " . esc_html($location['address']) . "</div>\n";
                }

                if (!empty($location['notes'])) {
                    $output .= "<div class=\"location-field location-notes\"><strong>Game Site Notes:</strong><br>\n" . wp_kses_post(wpautop($location['notes'])) . "</div>\n";
                }

                $output .= "</div>\n";
            }
            break;

        case 'game_site_list':
            $sites = get_post_meta($post->ID, 'game_site_list', true);
            if (!is_array($sites) || empty($sites)) {
                $output = "No Game Sites listed.";
                break;
            }

            $output = '';
            foreach ($sites as $site) {
                $output .= "<div class=\"owbn-chronicle-meta-game_site_list-entry\">\n";

                if (!empty($site['name'])) {
                    $output .= "<div class=\"site-field site-name\"><strong>Site Name:</strong> " . esc_html($site['name']) . "</div>\n";
                }

                if (isset($site['online_only'])) {
                    $output .= "<div class=\"site-field site-online-only\"><strong>Online Only:</strong> " . ($site['online_only'] ? 'Yes' : 'No') . "</div>\n";
                }

                if (!empty($site['country'])) {
                    $output .= "<div class=\"site-field site-country\"><strong>Country:</strong> " . esc_html($site['country']) . "</div>\n";
                }

                if (!empty($site['region'])) {
                    $output .= "<div class=\"site-field site-region\"><strong>Region:</strong> " . esc_html($site['region']) . "</div>\n";
                }

                if (!empty($site['city'])) {
                    $output .= "<div class=\"site-field site-city\"><strong>City:</strong> " . esc_html($site['city']) . "</div>\n";
                }

                if (!empty($site['address'])) {
                    $output .= "<div class=\"site-field site-address\"><strong>Address:</strong> " . esc_html($site['address']) . "</div>\n";
                }

                if (!empty($site['notes'])) {
                    $output .= "<div class=\"site-field site-notes\"><strong>Game Site Notes:</strong><br>\n" . wp_kses_post(wpautop($site['notes'])) . "</div>\n";
                }

                $output .= "</div>\n";
            }
            break;

        case 'document_links':
            $document_links = get_post_meta($post->ID, 'document_links', true);
            $document_output = '';

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

            if (is_array($document_links) && !empty($document_links)) {
                foreach ($document_links as $doc) {
                    $doc_title = $doc['title'] ?? '';
                    $url       = '';
                    $icon      = $document_icons['default'];

                    if (!empty($doc['upload'])) {
                        $url  = wp_get_attachment_url($doc['upload']);
                        $ext  = pathinfo($url, PATHINFO_EXTENSION);
                        $icon = $document_icons[strtolower($ext)] ?? $document_icons['default'];
                    } elseif (!empty($doc['link'])) {
                        $url  = $doc['link'];
                        $icon = $document_icons['link'];
                    }

                    if (!empty($doc_title) && !empty($url)) {
                        $document_output .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" style="margin-right: 0.5rem;">';
                        $document_output .= '<i class="' . esc_attr($icon) . '"></i> ';
                        $document_output .= esc_html($doc_title);
                        $document_output .= '</a><br>' . "\n";
                    }
                }
            }

            if (!empty($document_output)) {
                $output .= "<div class=\"chronicle-documents\">\n" . $document_output . "</div>\n";
            }
            break;

        case 'social_urls':
            $social_links = get_post_meta($post->ID, 'social_urls', true);
            $social_output = '';

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

            if (is_array($social_links) && !empty($social_links)) {
                foreach ($social_links as $link) {
                    $platform = $link['platform'] ?? '';
                    $url = $link['url'] ?? '';

                    if (!empty($platform) && !empty($url)) {
                        $icon_class = $platform_icons[$platform] ?? 'fas fa-globe';
                        $social_output .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" style="margin-right: 0.5rem;">';
                        $social_output .= '<i class="' . esc_attr($icon_class) . '"></i>';
                        $social_output .= '</a>' . "\n";
                    }
                }
            }

            $output = $social_output;
            break;

        case 'email_lists':
            $email_lists = get_post_meta($post->ID, 'email_lists', true);
            $email_output = '';

            if (is_array($email_lists) && !empty($email_lists)) {
                foreach ($email_lists as $entry) {
                    $name = trim($entry['list_name'] ?? '');
                    $email = trim($entry['email_address'] ?? '');
                    $desc = trim($entry['description'] ?? '');

                    if (!empty($name) && !empty($email)) {
                        $email_output .= '<div class="chron-email-entry">';
                        $email_output .= '<a href="mailto:' . esc_attr($email) . '" style="margin-right: 0.5rem;">';
                        $email_output .= '<i class="fa-solid fa-envelope"></i> ';
                        $email_output .= esc_html($name);
                        $email_output .= '</a><br />';
                        if (!empty($desc)) {
                            $email_output .= '<div class="email-list-desc">' . wp_kses_post(wpautop($desc)) . '</div>';
                        }
                        $email_output .= '</div>' . "\n";
                    }
                }
            }

            $output = $email_output;
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
        "<div class=\"owbn-chronicle-meta-%s\">\n%s\n</div>\n",
        esc_attr($term),
        $output
    );
});