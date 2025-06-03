<?php
if (!defined('ABSPATH')) exit;

function owbn_render_chronicle_card($post_id) {
    if (empty($post_id)) return '';

    $title = get_the_title($post_id);
    $slug = get_post_meta($post_id, 'chronicle_slug', true);
    $view_url = esc_url(home_url('/chronicle/' . $slug));

    // Genres
    $raw_genres = get_post_meta($post_id, 'genres', true);
    $genre_list = array_map('trim', is_array($raw_genres) ? $raw_genres : explode(',', (string)$raw_genres));
    $genre_display = !empty($genre_list) ? implode(', ', $genre_list) : '—';

    // Region
    $region = get_post_meta($post_id, 'chronicle_region', true);
    if (empty($region)) $region = '—';

    // OOC Location (Country → Region → City → Site Name)
    $locations = get_post_meta($post_id, 'ooc_locations', true);
    $location = '—';
    $online_only = false;

    if (is_array($locations) && !empty($locations)) {
        $first = $locations[0];
        $location_parts = [];

        if (!empty($first['country'])) $location_parts[] = strtoupper($first['country']);
        if (!empty($first['region']))  $location_parts[] = $first['region'];
        if (!empty($first['city']))    $location_parts[] = $first['city'];
        if (!empty($first['name']))    $location_parts[] = $first['name'];

        $location = implode(', ', $location_parts);
        $online_only = !empty($first['online_only']) && $first['online_only'] === '1';
    }

    $game_type = $online_only ? 'Virtual' : 'In-person';

    // IC Location (Correct key: ic_location_list)
    $ic_locations = get_post_meta($post_id, 'ic_location_list', true);
    $ic_location = '—';
    if (is_array($ic_locations) && !empty($ic_locations)) {
        $first_ic = $ic_locations[0];
        $ic_parts = [];

        if (!empty($first_ic['country'])) $ic_parts[] = strtoupper($first_ic['country']);
        if (!empty($first_ic['region']))  $ic_parts[] = $first_ic['region'];
        if (!empty($first_ic['city']))    $ic_parts[] = $first_ic['city'];
        if (!empty($first_ic['name']))    $ic_parts[] = $first_ic['name'];

        $ic_location = implode(', ', $ic_parts);
    }

    // Description from post content
    $post = get_post($post_id);
    $description = !empty($post) ? $post->post_content : '';
    if (empty($description)) $description = 'No description available.';

    // House Rules Link
    $house_rules_url = get_post_meta($post_id, 'chronicle_house_rules_url', true);

    // Social Media Links
    $social_links = get_post_meta($post_id, 'social_urls', true);
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
                $social_output .= '</a>';
            }
        }
    }

    // Document Links
    $document_links = get_post_meta($post_id, 'document_links', true);
    $document_output = '';

    // Optionally map file types or purposes to icons (placeholder, extend as needed)
    $document_icons = [
        'pdf'      => 'fa-solid fa-file-pdf',
        'doc'      => 'fa-solid fa-file-word',
        'docx'     => 'fa-solid fa-file-word',
        'xls'      => 'fa-solid fa-file-excel',
        'xlsx'     => 'fa-solid fa-file-excel',
        'txt'      => 'fa-solid fa-file-lines',
        'link'     => 'fas fa-link',
        'default'  => 'fa-solid fa-file',
    ];

    if (is_array($document_links) && !empty($document_links)) {
        foreach ($document_links as $doc) {
            $doc_title = $doc['title'] ?? '';
            $url   = '';
            $icon  = $document_icons['default'];

            // Prioritize upload over external link
            if (!empty($doc['upload'])) {
                $url = wp_get_attachment_url($doc['upload']);
                $ext = pathinfo($url, PATHINFO_EXTENSION);
                $icon = $document_icons[strtolower($ext)] ?? $document_icons['default'];
            } elseif (!empty($doc['link'])) {
                $url = $doc['link'];
                $icon = $document_icons['link'];
            }

            if (!empty($doc_title) && !empty($url)) {
                $document_output .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" style="margin-right: 0.5rem;">';
                $document_output .= '<i class="' . esc_attr($icon) . '"></i> ';
                $document_output .= esc_html($doc_title);
                $document_output .= '</a><br>';
            }
        }
    }


    ob_start();
    ?>
    <div class="owbn-chronicle-card">
        <div class="owbn-chronicle-title">
            <h2 class="chronicle-title"><a href="<?php echo esc_url($view_url); ?>"><?php echo esc_html($title); ?></a></h2>
        </div>
        <div class="owbn-chronicle-card-wrapper">
            <div class="owbn-chronicle-card--meta">
                <div class="owbn-chronicle-card-genre">
                    <strong>Genres:</strong> <?php echo esc_html($genre_display); ?>
                </div>
                <div class="owbn-chronicle-card-location">
                    IC: <?php echo esc_html($ic_location); ?> | 
                    <?php echo esc_html($region); ?><br />
                    OOC: <?php echo esc_html($location); ?> |
                    <?php echo esc_html($game_type); ?><br />
                </div>
            </div>

            <div class="chronicle-description">
                <?php echo wp_kses_post(wpautop($description)); ?>
            </div>

            <div class="chronicle-links">
                <?php if (!empty($document_output)): ?>
                    <div class="chronicle-documents" style="margin-bottom: 0.5rem;">
                        <?php echo wp_kses_post($document_output); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($social_output)): ?>
                    <div class="chronicle-social-links">
                        <?php echo wp_kses_post($social_output); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}