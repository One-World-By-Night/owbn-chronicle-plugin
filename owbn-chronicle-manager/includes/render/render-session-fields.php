<?php
if (!defined('ABSPATH')) exit;

// Render the session group fields for the Chronicle custom post type
function owbn_render_session_group($key, $value, $meta, $post_id = 0) {
    $groups = is_array($value) ? $value : [];
    $subfields = $meta['fields'];

    if (empty($groups)) {
        $groups[] = []; // Start with one blank
    }

    $tz_label = $post_id ? get_post_meta($post_id, 'timezone', true) : '';
    $tz_hint = $tz_label
        ? sprintf(__('Times below are in %s', 'owbn-chronicle-manager'), esc_html($tz_label))
        : __('Set Chronicle Timezone above so session times can be labeled.', 'owbn-chronicle-manager');
    echo '<p class="description owbn-session-tz-hint">' . $tz_hint . '</p>' . "\n";

    echo '<div class="owbn-repeatable-group" data-key="' . esc_attr($key) . '" data-tz="' . esc_attr($tz_label) . '">' . "\n";

    foreach ($groups as $i => $group) {
        echo '<div class="owbn-session-block">' . "\n";
        echo '<div class="owbn-session-header">' . "\n";

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

        echo '<strong>' . esc_html($header) . '</strong>' . "\n";
        echo '<button type="button" class="toggle-session button">Toggle</button>' . "\n";
        echo '</div>' . "\n"; // .header

        echo '<div class="owbn-session-body" style="display: none;">' . "\n";

        // Row 1: Session Type | Genres
        echo '<div class="owbn-session-row">' . "\n";
        render_session_field($key, $i, 'session_type', $subfields['session_type'], $group['session_type'] ?? '');
        render_session_field($key, $i, 'genres', $subfields['genres'], $group['genres'] ?? []);
        echo '</div>' . "\n";

        // Row 2: Frequency | Day | Check-in Time | Start Time
        echo '<div class="owbn-session-row">' . "\n";
        render_session_field($key, $i, 'frequency', $subfields['frequency'], $group['frequency'] ?? '');
        render_session_field($key, $i, 'day', $subfields['day'], $group['day'] ?? '');
        render_session_field($key, $i, 'checkin_time', $subfields['checkin_time'], $group['checkin_time'] ?? '');
        render_session_field($key, $i, 'start_time', $subfields['start_time'], $group['start_time'] ?? '');
        echo '</div>' . "\n";

        // Row 2b: Anchor Date (only meaningful for "Every Other"; JS shows/hides)
        $anchor_visible = (($group['frequency'] ?? '') === 'Every Other') ? '' : ' style="display:none;"';
        echo '<div class="owbn-session-row owbn-anchor-date-row"' . $anchor_visible . '>' . "\n";
        render_session_field($key, $i, 'anchor_date', $subfields['anchor_date'], $group['anchor_date'] ?? '');
        echo '</div>' . "\n";

        // Row 3: Game Date Notes (full width)
        echo '<div class="owbn-session-row-full">' . "\n";
        render_session_field($key, $i, 'notes', $subfields['notes'], $group['notes'] ?? '');
        echo '</div>' . "\n";

        echo '<button type="button" class="button remove-session">Remove</button>' . "\n";
        echo '</div>' . "\n"; // .body
        echo '</div>' . "\n"; // .block
    }

    // Hidden template block for JS cloning (uses __INDEX__ placeholders).
    // Marked aria-hidden and wrapped in a fieldset disabled so NO template
    // inputs submit on form POST. The JS clone step removes the disabled
    // attribute on the clone before inserting it into the form.
    echo '<fieldset class="owbn-session-template-wrap" aria-hidden="true" disabled style="display:none;border:0;padding:0;margin:0;">' . "\n";
    echo '<div class="owbn-session-block owbn-session-template">' . "\n";
    echo '<div class="owbn-session-header">' . "\n";
    echo '<strong>New Session</strong>' . "\n";
    echo '<button type="button" class="toggle-session button">Toggle</button>' . "\n";
    echo '</div>' . "\n";
    echo '<div class="owbn-session-body">' . "\n";

    echo '<div class="owbn-session-row">' . "\n";
    render_session_template_field($key, '__INDEX__', 'session_type', $subfields['session_type']);
    render_session_template_field($key, '__INDEX__', 'genres', $subfields['genres']);
    echo '</div>' . "\n";

    echo '<div class="owbn-session-row">' . "\n";
    render_session_template_field($key, '__INDEX__', 'frequency', $subfields['frequency']);
    render_session_template_field($key, '__INDEX__', 'day', $subfields['day']);
    render_session_template_field($key, '__INDEX__', 'checkin_time', $subfields['checkin_time']);
    render_session_template_field($key, '__INDEX__', 'start_time', $subfields['start_time']);
    echo '</div>' . "\n";

    echo '<div class="owbn-session-row owbn-anchor-date-row" style="display:none;">' . "\n";
    render_session_template_field($key, '__INDEX__', 'anchor_date', $subfields['anchor_date']);
    echo '</div>' . "\n";

    echo '<div class="owbn-session-row-full">' . "\n";
    render_session_template_field($key, '__INDEX__', 'notes', $subfields['notes']);
    echo '</div>' . "\n";

    echo '<button type="button" class="button remove-session">Remove</button>' . "\n";
    echo '</div>' . "\n"; // .body
    echo '</div>' . "\n"; // .template block
    echo '</fieldset>' . "\n"; // .template wrapper

    echo '<button type="button" class="button add-session" data-field="' . esc_attr($key) . '">Add</button>' . "\n";
    echo '</div>' . "\n"; // .repeatable-group
}

// Render a template field for JS cloning (no wp_editor, uses __INDEX__ placeholder)
function render_session_template_field($key, $index, $subkey, $meta) {
    $field_id = "{$key}_{$index}_{$subkey}";
    $field_name = "{$key}[{$index}][{$subkey}]";

    echo '<div class="owbn-session-field">' . "\n";
    echo '<label for="' . esc_attr($field_id) . '">' . esc_html($meta['label']) . '</label><br>' . "\n";

    switch ($meta['type']) {
        case 'select':
            echo '<select class="owbn-select2" name="' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '">' . "\n";
            foreach ($meta['options'] as $opt) {
                echo '<option value="' . esc_attr($opt) . '">' . esc_html($opt) . '</option>' . "\n";
            }
            echo '</select>' . "\n";
            break;

        case 'time':
            echo '<input type="time" name="' . esc_attr($field_name) . '" value="">' . "\n";
            break;

        case 'date':
            echo '<input type="date" name="' . esc_attr($field_name) . '" value="">' . "\n";
            break;

        case 'wysiwyg':
            // Plain textarea for template — JS will init TinyMCE after cloning
            echo '<textarea name="' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '" rows="4" style="width:100%;"></textarea>' . "\n";
            break;

        case 'multi_select':
            $opts = get_option('owbn_genre_list', []);
            echo '<select class="owbn-select2" name="' . esc_attr($field_name) . '[]" multiple="multiple">' . "\n";
            foreach ($opts as $opt) {
                echo '<option value="' . esc_attr($opt) . '">' . esc_html($opt) . '</option>' . "\n";
            }
            echo '</select>' . "\n";
            break;

        default:
            echo '<input type="text" name="' . esc_attr($field_name) . '" value="">' . "\n";
    }

    echo '</div>' . "\n";
}

// Render a single session field based on its type
function render_session_field($key, $index, $subkey, $meta, $value) {
    $field_id = "{$key}_{$index}_{$subkey}";
    $field_name = "{$key}[{$index}][{$subkey}]";

    echo '<div class="owbn-session-field">' . "\n";
    echo '<label for="' . esc_attr($field_id) . '">' . esc_html($meta['label']) . '</label><br>' . "\n";

    switch ($meta['type']) {
        case 'select':
            echo '<select class="owbn-select2" name="' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '">' . "\n";
            foreach ($meta['options'] as $opt) {
                echo '<option value="' . esc_attr($opt) . '" ' . selected($value, $opt, false) . '>' . esc_html($opt) . '</option>' . "\n";
            }
            echo '</select>' . "\n";
            break;

        case 'time':
            echo '<input type="time" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '">' . "\n";
            break;

        case 'date':
            echo '<input type="date" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '">' . "\n";
            break;

        case 'wysiwyg':
            wp_editor($value, $field_id, [
                'textarea_name' => $field_name,
                'textarea_rows' => 4,
                'media_buttons' => false,
            ]);
            break;

        case 'multi_select':
            $selected = is_array($value) ? $value : [];
            $opts = get_option('owbn_genre_list', []);
            echo '<select class="owbn-select2" name="' . esc_attr($field_name) . '[]" multiple="multiple">' . "\n";
            foreach ($opts as $opt) {
                echo '<option value="' . esc_attr($opt) . '" ' . selected(in_array($opt, $selected, true), true, false) . '>' . esc_html($opt) . '</option>' . "\n";
            }
            echo '</select>' . "\n";
            break;

        default:
            echo '<input type="text" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '">' . "\n";
    }

    echo '</div>' . "\n";
}