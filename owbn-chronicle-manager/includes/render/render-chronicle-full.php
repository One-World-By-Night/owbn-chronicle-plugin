<?php
if (!defined('ABSPATH')) exit;

/**
 * Load field definitions if not already loaded
 */
if (!function_exists('owbn_get_chronicle_field_definitions')) {
    require_once plugin_dir_path(__FILE__) . '/../fields.php';
}

/**
 * Render the full view of a single Chronicle
 *
 * @param int $post_id
 * @return string
 */
function owbn_render_chronicle_full($post_id)
{
    if (get_post_type($post_id) !== 'owbn_chronicle') {
        return '<p>Invalid Chronicle.</p>';
    }

    ob_start();

    $title = get_the_title($post_id);
    $content = apply_filters('the_content', get_post_field('post_content', $post_id));
    $field_groups = owbn_get_chronicle_field_definitions();

?>
    <div class="owbn-chronicle-full">
        <h1 class="chronicle-title"><?php echo esc_html($title); ?></h1>

        <?php
        foreach ($field_groups as $group_label => $fields) {
            echo '<h2>' . esc_html($group_label) . '</h2>';
            echo '<div class="owbn-chronicle-group">';

            foreach ($fields as $field_key => $field_def) {
                $value = get_post_meta($post_id, $field_key, true);
                if (empty($value)) continue;

                echo '<div class="owbn-chronicle-field">';
                echo '<strong>' . esc_html($field_def['label']) . ':</strong> ';

                switch ($field_def['type']) {
                    case 'text':
                    case 'slug':
                    case 'select':
                    case 'date':
                    case 'time':
                        echo esc_html($value);
                        break;

                    case 'url':
                        echo '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">' . esc_html($value) . '</a>';
                        break;

                    case 'email':
                        echo '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
                        break;

                    case 'boolean':
                        echo $value ? 'Yes' : 'No';
                        break;

                    case 'multi_select':
                        echo is_array($value) ? implode(', ', array_map('esc_html', $value)) : esc_html($value);
                        break;

                    case 'wysiwyg':
                        echo wp_kses_post(wpautop($value));
                        break;

                    case 'user_info':
                        if (is_array($value)) {
                            echo esc_html($value['display_name'] ?? '[Unknown]');
                            // Use display_email only - never expose actual_email
                            $email = $value['display_email'] ?? '';
                            if (!empty($email)) {
                                echo ' &mdash; <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                            }
                        }
                        break;

                    case 'chronicle_select':
                        $linked_post = get_page_by_path($value, OBJECT, 'owbn_chronicle');
                        if ($linked_post) {
                            echo '<a href="' . esc_url(get_permalink($linked_post)) . '">' . esc_html(get_the_title($linked_post)) . '</a>';
                        } else {
                            echo esc_html($value);
                        }
                        break;

                    case 'ooc_location':
                    case 'ic_location':
                        // Single location object
                        if (is_array($value)) {
                            $parts = [];
                            if (!empty($value['country'])) $parts[] = strtoupper($value['country']);
                            if (!empty($value['region']))  $parts[] = $value['region'];
                            if (!empty($value['city']))    $parts[] = $value['city'];
                            if (!empty($value['county']))  $parts[] = $value['county'];
                            if (!empty($value['name']))    $parts[] = $value['name'];
                            echo esc_html(implode(', ', $parts));
                            if (!empty($value['notes'])) {
                                echo '<br><em>' . esc_html($value['notes']) . '</em>';
                            }
                        }
                        break;

                    case 'location_group':
                        // Array of locations
                        if (is_array($value)) {
                            echo '<ul class="owbn-location-list">';
                            foreach ($value as $loc) {
                                if (!is_array($loc)) continue;
                                $parts = [];
                                if (!empty($loc['country'])) $parts[] = strtoupper($loc['country']);
                                if (!empty($loc['region']))  $parts[] = $loc['region'];
                                if (!empty($loc['city']))    $parts[] = $loc['city'];
                                if (!empty($loc['county']))  $parts[] = $loc['county'];
                                if (!empty($loc['name']))    $parts[] = $loc['name'];
                                echo '<li>' . esc_html(implode(', ', $parts));
                                if (!empty($loc['notes'])) {
                                    echo ' <em>(' . esc_html($loc['notes']) . ')</em>';
                                }
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                        break;

                    case 'session_group':
                    case 'ast_group':
                    case 'document_links_group':
                    case 'social_links_group':
                    case 'email_lists_group':
                        echo wp_kses_post(owbn_render_group_field($value, $field_def));
                        break;

                    default:
                        echo '[Unhandled field type: ' . esc_html($field_def['type']) . ']';
                        break;
                }

                echo '</div>';
            }

            echo '</div>';
        }
        ?>

        <div class="chronicle-content">
            <?php echo wp_kses_post($content); ?>
        </div>
    </div>
<?php

    return ob_get_clean();
}

/**
 * Render grouped fields (repeatable sets)
 */
function owbn_render_group_field($group_data, $field_def)
{
    if (!is_array($group_data)) return '';

    ob_start();

    foreach ($group_data as $entry) {
        echo '<div class="owbn-group-entry">';

        foreach ($field_def['fields'] as $sub_key => $sub_def) {
            // NEVER output actual_email to client - sensitive data
            if ($sub_key === 'actual_email') continue;

            if (empty($entry[$sub_key])) continue;

            echo '<div class="owbn-group-field">';
            echo '<strong>' . esc_html($sub_def['label']) . ':</strong> ';

            switch ($sub_def['type']) {
                case 'text':
                case 'slug':
                case 'date':
                case 'time':
                    echo esc_html($entry[$sub_key]);
                    break;

                case 'url':
                    $url = $entry[$sub_key];
                    echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($url) . '</a>';
                    break;

                case 'email':
                    $email = $entry[$sub_key];
                    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                    break;

                case 'boolean':
                    echo $entry[$sub_key] ? 'Yes' : 'No';
                    break;

                case 'wysiwyg':
                    echo wp_kses_post(wpautop($entry[$sub_key]));
                    break;

                case 'select':
                    echo esc_html($entry[$sub_key]);
                    break;

                case 'multi_select':
                    echo is_array($entry[$sub_key])
                        ? implode(', ', array_map('esc_html', $entry[$sub_key]))
                        : esc_html($entry[$sub_key]);
                    break;

                case 'upload':
                    $file_url = wp_get_attachment_url($entry[$sub_key]);
                    if ($file_url) {
                        echo '<a href="' . esc_url($file_url) . '" target="_blank">' . esc_html(basename($file_url)) . '</a>';
                    }
                    break;

                case 'user':
                    $user = get_user_by('id', $entry[$sub_key]);
                    echo $user ? esc_html($user->display_name) : '[Unknown User]';
                    break;

                default:
                    echo esc_html($entry[$sub_key]);
                    break;
            }

            echo '</div>';
        }

        echo '</div>';
    }

    return ob_get_clean();
}
