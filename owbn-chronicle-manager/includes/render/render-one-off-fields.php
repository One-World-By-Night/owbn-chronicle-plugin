<?php
if (!defined('ABSPATH')) exit;

function owbn_render_one_off_group($key, $value, $meta, $post_id = 0) {
    $groups = is_array($value) ? $value : [];
    $subfields = $meta['fields'];

    $tz_label = $post_id ? get_post_meta($post_id, 'timezone', true) : '';
    $tz_hint = $tz_label
        ? sprintf(__('Times below are in %s', 'owbn-chronicle-manager'), esc_html($tz_label))
        : __('Set Chronicle Timezone above so event times can be labeled.', 'owbn-chronicle-manager');
    echo '<p class="description">' . $tz_hint . '</p>' . "\n";

    echo '<div class="owbn-repeatable-group owbn-one-off-group" data-key="' . esc_attr($key) . '" data-tz="' . esc_attr($tz_label) . '">' . "\n";

    $today = current_time('Y-m-d');
    foreach ($groups as $i => $group) {
        $event_date = $group['event_date'] ?? '';
        $is_past = $event_date && $event_date < $today;
        $past_class = $is_past ? ' owbn-one-off-past' : '';
        $body_style = $is_past ? ' style="display:none;"' : '';

        echo '<div class="owbn-one-off-block' . $past_class . '">' . "\n";
        echo '<div class="owbn-session-header">' . "\n";
        $title = $group['event_title'] ?? '';
        $start = $group['start_time'] ?? '';
        $header = trim(($event_date ?: __('New Event', 'owbn-chronicle-manager')) . ' ' . $start . ($title ? ' – ' . $title : ''));
        if ($is_past) $header .= ' [' . __('past', 'owbn-chronicle-manager') . ']';
        echo '<strong>' . esc_html($header) . '</strong>' . "\n";
        echo '<button type="button" class="toggle-one-off button">' . esc_html__('Toggle', 'owbn-chronicle-manager') . '</button>' . "\n";
        echo '</div>' . "\n";

        echo '<div class="owbn-session-body"' . $body_style . '>' . "\n";
        echo '<div class="owbn-session-row">' . "\n";
        owbn_render_one_off_field($key, $i, 'event_date',  $subfields['event_date'],  $event_date);
        owbn_render_one_off_field($key, $i, 'start_time',  $subfields['start_time'],  $start);
        owbn_render_one_off_field($key, $i, 'event_title', $subfields['event_title'], $title);
        echo '</div>' . "\n";
        echo '<div class="owbn-session-row">' . "\n";
        owbn_render_one_off_field($key, $i, 'genres', $subfields['genres'], $group['genres'] ?? []);
        echo '</div>' . "\n";
        echo '<div class="owbn-session-row-full">' . "\n";
        owbn_render_one_off_field($key, $i, 'notes', $subfields['notes'], $group['notes'] ?? '');
        echo '</div>' . "\n";
        echo '<button type="button" class="button remove-one-off">' . esc_html__('Remove', 'owbn-chronicle-manager') . '</button>' . "\n";
        echo '</div>' . "\n";
        echo '</div>' . "\n";
    }

    // Template (cloned by JS).
    echo '<fieldset class="owbn-one-off-template-wrap" aria-hidden="true" disabled style="display:none;border:0;padding:0;margin:0;">' . "\n";
    echo '<div class="owbn-one-off-block owbn-one-off-template">' . "\n";
    echo '<div class="owbn-session-header"><strong>' . esc_html__('New Event', 'owbn-chronicle-manager') . '</strong>';
    echo '<button type="button" class="toggle-one-off button">' . esc_html__('Toggle', 'owbn-chronicle-manager') . '</button></div>' . "\n";
    echo '<div class="owbn-session-body">' . "\n";
    echo '<div class="owbn-session-row">' . "\n";
    owbn_render_one_off_template_field($key, '__INDEX__', 'event_date',  $subfields['event_date']);
    owbn_render_one_off_template_field($key, '__INDEX__', 'start_time',  $subfields['start_time']);
    owbn_render_one_off_template_field($key, '__INDEX__', 'event_title', $subfields['event_title']);
    echo '</div>' . "\n";
    echo '<div class="owbn-session-row">' . "\n";
    owbn_render_one_off_template_field($key, '__INDEX__', 'genres', $subfields['genres']);
    echo '</div>' . "\n";
    echo '<div class="owbn-session-row-full">' . "\n";
    owbn_render_one_off_template_field($key, '__INDEX__', 'notes', $subfields['notes']);
    echo '</div>' . "\n";
    echo '<button type="button" class="button remove-one-off">' . esc_html__('Remove', 'owbn-chronicle-manager') . '</button>' . "\n";
    echo '</div></div></fieldset>' . "\n";

    echo '<button type="button" class="button add-one-off" data-field="' . esc_attr($key) . '">' . esc_html__('Add Event', 'owbn-chronicle-manager') . '</button>' . "\n";
    if (!empty(array_filter($groups, function ($g) use ($today) { return ($g['event_date'] ?? '') < $today; }))) {
        echo ' <button type="button" class="button owbn-show-past-one-offs">' . esc_html__('Show Past Events', 'owbn-chronicle-manager') . '</button>' . "\n";
    }
    echo '</div>' . "\n";
}

function owbn_render_one_off_field($key, $index, $subkey, $meta, $value) {
    $field_id   = "{$key}_{$index}_{$subkey}";
    $field_name = "{$key}[{$index}][{$subkey}]";
    echo '<div class="owbn-session-field">' . "\n";
    echo '<label for="' . esc_attr($field_id) . '">' . esc_html($meta['label']) . '</label><br>' . "\n";

    switch ($meta['type']) {
        case 'date':
            echo '<input type="date" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '">' . "\n";
            break;
        case 'time':
            echo '<input type="time" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '">' . "\n";
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
        case 'wysiwyg':
            wp_editor($value, $field_id, [
                'textarea_name' => $field_name,
                'textarea_rows' => 4,
                'media_buttons' => false,
            ]);
            break;
        default:
            echo '<input type="text" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '">' . "\n";
    }
    echo '</div>' . "\n";
}

function owbn_render_one_off_template_field($key, $index, $subkey, $meta) {
    $field_id   = "{$key}_{$index}_{$subkey}";
    $field_name = "{$key}[{$index}][{$subkey}]";
    echo '<div class="owbn-session-field">' . "\n";
    echo '<label for="' . esc_attr($field_id) . '">' . esc_html($meta['label']) . '</label><br>' . "\n";

    switch ($meta['type']) {
        case 'date':
            echo '<input type="date" name="' . esc_attr($field_name) . '" value="">' . "\n";
            break;
        case 'time':
            echo '<input type="time" name="' . esc_attr($field_name) . '" value="">' . "\n";
            break;
        case 'multi_select':
            $opts = get_option('owbn_genre_list', []);
            echo '<select class="owbn-select2" name="' . esc_attr($field_name) . '[]" multiple="multiple">' . "\n";
            foreach ($opts as $opt) {
                echo '<option value="' . esc_attr($opt) . '">' . esc_html($opt) . '</option>' . "\n";
            }
            echo '</select>' . "\n";
            break;
        case 'wysiwyg':
            echo '<textarea name="' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '" rows="4" style="width:100%;"></textarea>' . "\n";
            break;
        default:
            echo '<input type="text" name="' . esc_attr($field_name) . '" value="">' . "\n";
    }
    echo '</div>' . "\n";
}
