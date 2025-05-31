<?php
if (!defined('ABSPATH')) exit;

// Render the session group fields for the Chronicle custom post type
function owbn_render_session_group($key, $value, $meta) {
    $groups = is_array($value) ? $value : [];
    $subfields = $meta['fields'];

    if (empty($groups)) {
        $groups[] = []; // Start with one blank
    }

    echo '<div class="owbn-repeatable-group" data-key="' . esc_attr($key) . '">' . "\n";

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

        // Row 3: Game Date Notes (full width)
        echo '<div class="owbn-session-row-full">' . "\n";
        render_session_field($key, $i, 'notes', $subfields['notes'], $group['notes'] ?? '');
        echo '</div>' . "\n";

        echo '<button type="button" class="button remove-session">Remove</button>' . "\n";
        echo '</div>' . "\n"; // .body
        echo '</div>' . "\n"; // .block
    }

    echo '<button type="button" class="button add-session" data-field="' . esc_attr($key) . '">Add</button>' . "\n";
    echo '</div>' . "\n"; // .repeatable-group
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