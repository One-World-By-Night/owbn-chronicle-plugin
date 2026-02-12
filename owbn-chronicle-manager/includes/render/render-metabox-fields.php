<?php
/** File: includes/render/render-metabox-fields.php
 * Text Domain: owbn-chronicle-manager
 * @version 3.0.0
 * @author greghacke
 * Function: Entity metabox field rendering helpers
 *
 * Note: The main metabox rendering function owbn_render_entity_metabox()
 * lives in includes/core/entity-init.php. This file contains only the
 * individual field-type rendering helpers.
 */

if (!defined('ABSPATH')) exit;

// Render the slug field with optional disabling
function owbn_render_slug_field($key, $value, $disabled_attr = '')
{
    $disabled_html = $disabled_attr ? ' disabled' : '';

    echo "<input type=\"text\" class=\"regular-text\" name=\"" . esc_attr($key) . "\" value=\"" . esc_attr($value) . "\" " .
        "minlength=\"2\" maxlength=\"6\" pattern=\"[a-z0-9]{2,8}\" " . esc_attr($disabled_html) . " " .
        "placeholder=\"" . esc_attr__('2–8 lowercase alphanumeric characters', 'owbn-chronicle-manager') . "\">\n";

    echo "<p class=\"description\">" . esc_html__('Allowed: lowercase letters and numbers, 2–8 characters.', 'owbn-chronicle-manager') . "</p>\n";
}

// Render the boolean field as a switch, with optional disabling
function owbn_render_boolean_field($key, $value, $disabled_attr = '')
{
    $is_checked = ($value === '1');
    $disabled_html = $disabled_attr ? ' disabled' : '';

    echo '<div class="owbn-boolean-switch">' . "\n";
    echo '  <span class="switch-label switch-label-left">' . esc_html__('No', 'owbn-chronicle-manager') . '</span>' . "\n";
    echo '  <label class="switch">' . "\n";
    echo '    <input type="checkbox" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="1" ' . checked($is_checked, true, false) . esc_attr($disabled_html) . '>' . "\n";
    echo '    <span class="slider round"></span>' . "\n";
    echo '  </label>' . "\n";
    echo '  <span class="switch-label switch-label-right">' . esc_html__('Yes', 'owbn-chronicle-manager') . '</span>' . "\n";
    echo '</div>' . "\n";
}

// Render the select field with optional disabling support
function owbn_render_select_field($key, $value, $meta, $disabled_attr = '')
{
    $options = $meta['options'] ?? [];

    echo "<select name=\"" . esc_attr($key) . "\" id=\"" . esc_attr($key) . "\" class=\"regular-text owbn-select2 single\" " . esc_attr($disabled_attr) . ">\n";
    echo "<option value=\"\">" . esc_html__('— Select —', 'owbn-chronicle-manager') . "</option>\n";

    foreach ($options as $option) {
        echo "<option value=\"" . esc_attr($option) . "\" " . selected($value, $option, false) . ">" . esc_html($option) . "</option>\n";
    }

    echo "</select>\n";
}

// Render the multi-select field with AJAX loading
function owbn_render_multi_select_field($key, $value, $meta)
{
    $selected = is_array($value) ? $value : [];
    $options = [];

    if (!empty($meta['source']) && $meta['source'] === 'owbn_genre_list') {
        $options = get_option('owbn_genre_list', []);
    }

    echo "<select name=\"" . esc_attr($key) . "[]\" multiple=\"multiple\" size=\"8\" class=\"owbn-select2 multi\" style=\"width: 100%;\">\n";
    foreach ($options as $opt) {
        $is_selected = in_array($opt, $selected, true);
        echo "<option value=\"" . esc_attr($opt) . "\" " . selected($is_selected, true, false) . ">" . esc_html($opt) . "</option>\n";
    }
    echo "</select>\n";
    echo "<p class=\"description\">" . esc_html__('Select one or more options.', 'owbn-chronicle-manager') . "</p>\n";
}

// Render the wysiwyg editor field
function owbn_render_wysiwyg_editor($key, $value)
{
    wp_editor(
        is_scalar($value) ? $value : '',
        $key,
        [
            'textarea_name' => $key,
            'textarea_rows' => 6,
            'media_buttons' => false,
        ]
    );
}

// Render repeatable group fields for generating groups
function owbn_render_repeatable_group($key, $value, $meta)
{
    $groups = is_array($value) ? $value : [];
    $subfields = $meta['fields'] ?? [];

    echo '<div class="owbn-repeatable-group" data-key="' . esc_attr($key) . '">';

    foreach ($groups as $i => $group) {
        echo '<div class="owbn-session-block">';
        echo '<div class="owbn-session-header">';

        $session_type = $group['session_type'] ?? 'Session';
        $frequency = $group['frequency'] ?? '';
        $day = $group['day'] ?? '';
        $start_time = $group['start_time'] ?? '';

        $header = $session_type;
        $header_parts = [];

        if ($frequency && $day) {
            $header_parts[] = "{$frequency} {$day}";
        } elseif ($day) {
            $header_parts[] = $day;
        }

        if ($start_time) {
            $header_parts[] = $start_time;
        }

        $genre_list = [];
        $all_genres = get_option('owbn_genre_list', []);
        $session_genres = $group['genres'] ?? [];

        if (is_array($session_genres)) {
            foreach ($session_genres as $genre) {
                if (in_array($genre, $all_genres, true)) {
                    $genre_list[] = $genre;
                }
            }
        }

        if (!empty($genre_list)) {
            $header_parts[] = '(' . implode(', ', $genre_list) . ')';
        }

        if (!empty($header_parts)) {
            $header .= ' – ' . implode(' ', $header_parts);
        }

        echo '<strong>' . esc_html($header) . '</strong>';
        echo '<button type="button" class="toggle-session button">Toggle</button>';
        echo '</div>';

        echo '<div class="owbn-session-body" style="display: none;">';

        foreach ($subfields as $subkey => $submeta) {
            $field_id = "{$key}_{$i}_{$subkey}";
            $field_name = "{$key}[{$i}][{$subkey}]";
            $field_value = $group[$subkey] ?? '';

            echo '<p><label for="' . esc_attr($field_id) . '">' . esc_html($submeta['label']) . '</label><br>';

            switch ($submeta['type']) {
                case 'select':
                    echo '<select class="owbn-select2" name="' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '">';
                    foreach ($submeta['options'] as $opt) {
                        echo '<option value="' . esc_attr($opt) . '" ' . selected($field_value, $opt, false) . '>' . esc_html($opt) . '</option>';
                    }
                    echo '</select>';
                    break;

                case 'time':
                    echo '<input type="time" name="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '">';
                    break;

                case 'wysiwyg':
                    wp_editor($field_value, $field_id, [
                        'textarea_name' => $field_name,
                        'textarea_rows' => 4,
                        'media_buttons' => false,
                    ]);
                    break;

                case 'multi_select':
                    $selected = is_array($field_value) ? $field_value : [];
                    $opts = get_option('owbn_genre_list', []);
                    echo '<select class="owbn-select2" name="' . esc_attr($field_name) . '[]" multiple="multiple">';
                    foreach ($opts as $opt) {
                        echo '<option value="' . esc_attr($opt) . '" ' . selected(in_array($opt, $selected, true), true, false) . '>' . esc_html($opt) . '</option>';
                    }
                    echo '</select>';
                    break;

                default:
                    echo '<input type="text" name="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '">';
            }

            echo '</p>';
        }

        echo '<button type="button" class="button remove-session">Remove Session</button>';
        echo '</div></div>';
    }

    echo '<button type="button" class="button add-session" data-field="' . esc_attr($key) . '">Add Session</button>';
    echo '</div>';
}

// Render the entity select field for selecting related entities (e.g., parent chronicles)
function owbn_render_entity_select_field($key, $value, $meta, $label, $error_class, $disabled_attr = '', $post_type = 'owbn_chronicle')
{
    global $post;
    $value = is_scalar($value) ? $value : '';
    $disabled_html = $disabled_attr ? ' disabled' : '';

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo "<tr id=\"row-{$key}\">\n";
    echo "<th><label for=\"" . esc_attr($key) . "\">" . esc_html($label) . "</label></th>\n";
    echo "<td class=\"" . esc_attr(trim($error_class)) . "\">\n";

    $args = [
        'post_type'      => 'owbn_chronicle',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post__not_in'   => [$post->ID],
    ];

    $chronicles = get_posts($args);

    // Conditional wrapper
    echo "<div id=\"owbn-parent-chronicle-select\" style=\"display:none\">\n";
    echo "<select name=\"" . esc_attr($key) . "\" id=\"" . esc_attr($key) . "\" class=\"regular-text owbn-select2 single\" style=\"width: 100%;\" " . esc_attr($disabled_html) . ">\n";
    echo "<option value=\"\">" . esc_html__('— Select —', 'owbn-chronicle-manager') . "</option>\n";

    foreach ($chronicles as $chron) {
        $id = $chron->ID;
        $title = $chron->post_title;
        echo "<option value=\"" . esc_attr($id) . "\" " . selected($value, $id, false) . ">" . esc_html($title) . "</option>\n";
    }

    echo "</select>\n";
    echo "<p class=\"description\">" . esc_html__('Only applicable to Satellite Chronicles.', 'owbn-chronicle-manager') . "</p>\n";
    echo "</div>\n";

    // Default visible message
    echo "<div id=\"owbn-parent-chronicle-message\" style=\"margin-top: 10px; padding: 8px; background-color: #e8f5e9; border-left: 3px solid #4CAF50;\">\n";
    echo "<em>" . esc_html__('Only Satellite Chronicles have parents.', 'owbn-chronicle-manager') . "</em>\n";
    echo "</div>\n";

    echo "</td>\n</tr>\n";
}
