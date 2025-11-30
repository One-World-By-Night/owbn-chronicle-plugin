<?php
if (!defined('ABSPATH')) exit;

/**
 * Render the full view of a single Coordinator
 *
 * @param int $post_id
 * @return string
 */
function owbn_render_coordinator_full($post_id)
{
    if (get_post_type($post_id) !== 'owbn_coordinator') {
        return '<p>Invalid Coordinator.</p>';
    }

    ob_start();

    $title = get_the_title($post_id);
    $content = apply_filters('the_content', get_post_field('post_content', $post_id));

    // Get all meta
    $slug = get_post_meta($post_id, 'coordinator_slug', true);
    $office_title = get_post_meta($post_id, 'coordinator_title', true);
    $office_desc = get_post_meta($post_id, 'office_description', true);
    $term_start = get_post_meta($post_id, 'term_start_date', true);
    $web_url = get_post_meta($post_id, 'web_url', true);
    $coord_info = get_post_meta($post_id, 'coord_info', true);
    $subcoord_list = get_post_meta($post_id, 'subcoord_list', true);
    $document_links = get_post_meta($post_id, 'document_links', true);
    $email_lists = get_post_meta($post_id, 'email_lists', true);

?>
    <div class="owbn-coordinator-full">
        <h1 class="coordinator-title"><?php echo esc_html($office_title ?: $title); ?></h1>

        <!-- Basic Information -->
        <div class="owbn-coordinator-section">
            <h2><?php esc_html_e('Basic Information', 'owbn-chronicle-manager'); ?></h2>
            <div class="owbn-coordinator-group">

                <?php if (!empty($term_start)): ?>
                    <div class="owbn-coordinator-field">
                        <strong><?php esc_html_e('Term Started:', 'owbn-chronicle-manager'); ?></strong>
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($term_start))); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($web_url)): ?>
                    <div class="owbn-coordinator-field">
                        <strong><?php esc_html_e('Website:', 'owbn-chronicle-manager'); ?></strong>
                        <a href="<?php echo esc_url($web_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($web_url); ?></a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($office_desc)): ?>
                    <div class="owbn-coordinator-field owbn-coordinator-field-full">
                        <strong><?php esc_html_e('Office Description:', 'owbn-chronicle-manager'); ?></strong>
                        <div class="coordinator-office-description">
                            <?php echo wp_kses_post(wpautop($office_desc)); ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Coordinator -->
        <?php if (is_array($coord_info) && !empty($coord_info['display_name'])): ?>
            <div class="owbn-coordinator-section">
                <h2><?php esc_html_e('Coordinator', 'owbn-chronicle-manager'); ?></h2>
                <div class="owbn-coordinator-group">
                    <div class="owbn-coordinator-field">
                        <strong><?php esc_html_e('Name:', 'owbn-chronicle-manager'); ?></strong>
                        <?php
                        $coord_name = $coord_info['display_name'];
                        // Use display_email only - never expose actual_email
                        $coord_email = $coord_info['display_email'] ?? '';
                        if (!empty($coord_email)) {
                            echo '<a href="mailto:' . esc_attr($coord_email) . '">' . esc_html($coord_name) . '</a>';
                        } else {
                            echo esc_html($coord_name);
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Sub-Coordinators -->
        <?php if (is_array($subcoord_list) && !empty($subcoord_list)): ?>
            <div class="owbn-coordinator-section">
                <h2><?php esc_html_e('Sub-Coordinators', 'owbn-chronicle-manager'); ?></h2>
                <div class="owbn-coordinator-group">
                    <ul class="owbn-subcoord-list">
                        <?php foreach ($subcoord_list as $subcoord):
                            if (empty($subcoord['display_name'])) continue;
                            $name = $subcoord['display_name'];
                            $role = $subcoord['role'] ?? '';
                            // Use display_email only - never expose actual_email
                            $email = $subcoord['display_email'] ?? '';
                        ?>
                            <li>
                                <?php if (!empty($email)): ?>
                                    <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($name); ?></a>
                                <?php else: ?>
                                    <?php echo esc_html($name); ?>
                                <?php endif; ?>
                                <?php if (!empty($role)): ?>
                                    <span class="subcoord-role">(<?php echo esc_html($role); ?>)</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Document Links -->
        <?php if (is_array($document_links) && !empty($document_links)): ?>
            <div class="owbn-coordinator-section">
                <h2><?php esc_html_e('Documents', 'owbn-chronicle-manager'); ?></h2>
                <div class="owbn-coordinator-group">
                    <ul class="owbn-document-list">
                        <?php foreach ($document_links as $doc):
                            $doc_title = $doc['title'] ?? '';
                            if (empty($doc_title)) continue;

                            $url = '';
                            if (!empty($doc['file_id'])) {
                                $url = wp_get_attachment_url($doc['file_id']);
                            } elseif (!empty($doc['link'])) {
                                $url = $doc['link'];
                            }
                        ?>
                            <li>
                                <?php if (!empty($url)): ?>
                                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
                                        <span class="dashicons dashicons-media-document"></span>
                                        <?php echo esc_html($doc_title); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo esc_html($doc_title); ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Email Lists -->
        <?php if (is_array($email_lists) && !empty($email_lists)): ?>
            <div class="owbn-coordinator-section">
                <h2><?php esc_html_e('Email Lists', 'owbn-chronicle-manager'); ?></h2>
                <div class="owbn-coordinator-group">
                    <ul class="owbn-email-list">
                        <?php foreach ($email_lists as $list):
                            $list_name = $list['list_name'] ?? '';
                            $list_email = $list['email_address'] ?? '';
                            $list_desc = $list['description'] ?? '';
                            if (empty($list_name)) continue;
                        ?>
                            <li>
                                <strong>
                                    <?php if (!empty($list_email)): ?>
                                        <a href="mailto:<?php echo esc_attr($list_email); ?>"><?php echo esc_html($list_name); ?></a>
                                    <?php else: ?>
                                        <?php echo esc_html($list_name); ?>
                                    <?php endif; ?>
                                </strong>
                                <?php if (!empty($list_desc)): ?>
                                    <div class="email-list-description"><?php echo wp_kses_post(wpautop($list_desc)); ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Post Content -->
        <?php if (!empty($content)): ?>
            <div class="coordinator-content">
                <?php echo wp_kses_post($content); ?>
            </div>
        <?php endif; ?>

    </div>
<?php

    return ob_get_clean();
}

/**
 * Render coordinator output wrapper with optional label
 */
function owbn_coordinator_output_wrapper($term, $content, $show_label = true)
{
    if (empty($content)) return '';

    $label = ucwords(str_replace('_', ' ', $term));

    ob_start();
    echo '<div class="owbn-coordinator-meta owbn-coordinator-meta--' . esc_attr($term) . '">';

    if ($show_label) {
        echo '<span class="owbn-coordinator-meta-label">' . esc_html($label) . ':</span> ';
    }

    echo '<span class="owbn-coordinator-meta-value">' . $content . '</span>';
    echo '</div>';

    return ob_get_clean();
}

/**
 * Individual field render functions for shortcode use
 */
function owbn_render_coordinator_title($post)
{
    return esc_html(get_the_title($post));
}

function owbn_render_coordinator_office_title($post)
{
    $title = get_post_meta($post->ID, 'coordinator_title', true);
    return !empty($title) ? esc_html($title) : esc_html(get_the_title($post));
}

function owbn_render_coordinator_info($post)
{
    $coord_info = get_post_meta($post->ID, 'coord_info', true);
    if (!is_array($coord_info) || empty($coord_info['display_name'])) return '';

    $name = $coord_info['display_name'];
    // Use display_email only - never expose actual_email
    $email = $coord_info['display_email'] ?? '';

    if (!empty($email)) {
        return '<a href="mailto:' . esc_attr($email) . '">' . esc_html($name) . '</a>';
    }
    return esc_html($name);
}

function owbn_render_coordinator_subcoord_list($post)
{
    $subcoord_list = get_post_meta($post->ID, 'subcoord_list', true);
    if (!is_array($subcoord_list) || empty($subcoord_list)) return '';

    $output = '<ul class="owbn-subcoord-list">';
    foreach ($subcoord_list as $subcoord) {
        if (empty($subcoord['display_name'])) continue;

        $name = $subcoord['display_name'];
        $role = $subcoord['role'] ?? '';
        // Use display_email only - never expose actual_email
        $email = $subcoord['display_email'] ?? '';

        $output .= '<li>';
        if (!empty($email)) {
            $output .= '<a href="mailto:' . esc_attr($email) . '">' . esc_html($name) . '</a>';
        } else {
            $output .= esc_html($name);
        }
        if (!empty($role)) {
            $output .= ' <span class="subcoord-role">(' . esc_html($role) . ')</span>';
        }
        $output .= '</li>';
    }
    $output .= '</ul>';

    return $output;
}

function owbn_render_coordinator_document_links($post)
{
    $document_links = get_post_meta($post->ID, 'document_links', true);
    if (!is_array($document_links) || empty($document_links)) return '';

    $output = '<ul class="owbn-document-list">';
    foreach ($document_links as $doc) {
        $doc_title = $doc['title'] ?? '';
        if (empty($doc_title)) continue;

        $url = '';
        if (!empty($doc['file_id'])) {
            $url = wp_get_attachment_url($doc['file_id']);
        } elseif (!empty($doc['link'])) {
            $url = $doc['link'];
        }

        $output .= '<li>';
        if (!empty($url)) {
            $output .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($doc_title) . '</a>';
        } else {
            $output .= esc_html($doc_title);
        }
        $output .= '</li>';
    }
    $output .= '</ul>';

    return $output;
}

function owbn_render_coordinator_email_lists($post)
{
    $email_lists = get_post_meta($post->ID, 'email_lists', true);
    if (!is_array($email_lists) || empty($email_lists)) return '';

    $output = '<ul class="owbn-email-list">';
    foreach ($email_lists as $list) {
        $list_name = $list['list_name'] ?? '';
        $list_email = $list['email_address'] ?? '';
        if (empty($list_name)) continue;

        $output .= '<li>';
        if (!empty($list_email)) {
            $output .= '<a href="mailto:' . esc_attr($list_email) . '">' . esc_html($list_name) . '</a>';
        } else {
            $output .= esc_html($list_name);
        }
        $output .= '</li>';
    }
    $output .= '</ul>';

    return $output;
}
