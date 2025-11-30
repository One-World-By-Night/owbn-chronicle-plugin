<?php
if (!defined('ABSPATH')) exit;

/**
 * Render a coordinator card (summary view)
 *
 * @param int $post_id
 * @return string
 */
function owbn_render_coordinator_card($post_id)
{
    if (empty($post_id)) return '';

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'owbn_coordinator') return '';

    $title = get_the_title($post_id);
    $slug = get_post_meta($post_id, 'coordinator_slug', true);
    $view_url = esc_url(home_url('/coords/' . $slug));

    // Office title
    $office_title = get_post_meta($post_id, 'coordinator_title', true);
    if (empty($office_title)) $office_title = $title;

    // Coordinator info
    $coord_info = get_post_meta($post_id, 'coord_info', true);
    $coord_name = '';
    $coord_email = '';
    if (is_array($coord_info)) {
        $coord_name = $coord_info['display_name'] ?? '';
        // Use display_email only - never expose actual_email
        $coord_email = $coord_info['display_email'] ?? '';
    }

    // Term start date
    $term_start = get_post_meta($post_id, 'term_start_date', true);
    $term_display = '';
    if (!empty($term_start)) {
        $term_display = date_i18n(get_option('date_format'), strtotime($term_start));
    }

    // Website
    $web_url = get_post_meta($post_id, 'web_url', true);

    // Description from post content
    $description = !empty($post->post_content) ? $post->post_content : '';
    if (empty($description)) {
        $office_desc = get_post_meta($post_id, 'office_description', true);
        $description = !empty($office_desc) ? $office_desc : 'No description available.';
    }

    // Subcoordinators count
    $subcoord_list = get_post_meta($post_id, 'subcoord_list', true);
    $subcoord_count = is_array($subcoord_list) ? count($subcoord_list) : 0;

    // Document links
    $document_links = get_post_meta($post_id, 'document_links', true);
    $document_output = '';
    if (is_array($document_links) && !empty($document_links)) {
        foreach ($document_links as $doc) {
            $doc_title = $doc['title'] ?? '';
            $url = '';

            if (!empty($doc['file_id'])) {
                $url = wp_get_attachment_url($doc['file_id']);
            } elseif (!empty($doc['link'])) {
                $url = $doc['link'];
            }

            if (!empty($doc_title) && !empty($url)) {
                $document_output .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" style="margin-right: 0.5rem;">';
                $document_output .= '<i class="dashicons dashicons-media-document"></i> ';
                $document_output .= esc_html($doc_title);
                $document_output .= '</a><br>';
            }
        }
    }

    // Email lists
    $email_lists = get_post_meta($post_id, 'email_lists', true);
    $email_output = '';
    if (is_array($email_lists) && !empty($email_lists)) {
        foreach ($email_lists as $list) {
            $list_name = $list['list_name'] ?? '';
            $list_email = $list['email_address'] ?? '';

            if (!empty($list_name) && !empty($list_email)) {
                $email_output .= '<a href="mailto:' . esc_attr($list_email) . '">';
                $email_output .= '<i class="dashicons dashicons-email"></i> ';
                $email_output .= esc_html($list_name);
                $email_output .= '</a><br>';
            }
        }
    }

    ob_start();
?>
    <div class="owbn-coordinator-card">
        <div class="owbn-coordinator-title">
            <h2 class="coordinator-title"><a href="<?php echo esc_url($view_url); ?>"><?php echo esc_html($office_title); ?></a></h2>
        </div>
        <div class="owbn-coordinator-card-wrapper">
            <div class="owbn-coordinator-card--meta">
                <?php if (!empty($coord_name)): ?>
                    <div class="owbn-coordinator-card-name">
                        <strong>Coordinator:</strong>
                        <?php if (!empty($coord_email)): ?>
                            <a href="mailto:<?php echo esc_attr($coord_email); ?>"><?php echo esc_html($coord_name); ?></a>
                        <?php else: ?>
                            <?php echo esc_html($coord_name); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($term_display)): ?>
                    <div class="owbn-coordinator-card-term">
                        <strong>Term Started:</strong> <?php echo esc_html($term_display); ?>
                    </div>
                <?php endif; ?>

                <?php if ($subcoord_count > 0): ?>
                    <div class="owbn-coordinator-card-staff">
                        <strong>Sub-Coordinators:</strong> <?php echo esc_html($subcoord_count); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($web_url)): ?>
                    <div class="owbn-coordinator-card-website">
                        <strong>Website:</strong> <a href="<?php echo esc_url($web_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($web_url); ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="coordinator-description">
                <?php echo wp_kses_post(wpautop(wp_trim_words($description, 50, '...'))); ?>
            </div>

            <?php if (!empty($document_output) || !empty($email_output)): ?>
                <div class="coordinator-links">
                    <?php if (!empty($document_output)): ?>
                        <div class="coordinator-documents" style="margin-bottom: 0.5rem;">
                            <?php echo wp_kses_post($document_output); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($email_output)): ?>
                        <div class="coordinator-email-lists">
                            <?php echo wp_kses_post($email_output); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}
