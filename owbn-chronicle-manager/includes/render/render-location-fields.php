<?php
if (!defined('ABSPATH')) exit;

// Render the location group fields for the Chronicle custom post type
function owbn_render_location_group($key, $value, $meta) {
    $groups = is_array($value) ? $value : [];
    $subfields = $meta['fields'];

    if (empty($groups)) {
        $groups[] = []; // Start with one blank
    }

    echo '<div class="owbn-repeatable-group" data-key="' . esc_attr($key) . '">' . "\n";

    foreach ($groups as $i => $group) {
        echo '<div class="owbn-location-block">' . "\n";
        echo '<div class="owbn-location-header">' . "\n";

        $name    = $group['name'] ?? __('(Unnamed)', 'owbn-chronicle-manager');
        $city    = $group['city'] ?? '';
        $region  = $group['region'] ?? '';
        $country = $group['country'] ?? '';
        $online  = !empty($group['online_only']) ? __('(Online Only)', 'owbn-chronicle-manager') : '';
        $online = !empty($group['online']) || !empty($group['online_only']);

        $location_parts = array_filter([$city, $region, $country]);
        $header = esc_html($name);

        if (!empty($location_parts)) {
            $header .= ' – ' . esc_html(implode(', ', $location_parts));
        }

        if ($online) {
            $header .= ' ' . esc_html($online);
        }

        echo '<strong>' . $header . '</strong>' . "\n";
        echo '<button type="button" class="toggle-location button">Toggle</button>' . "\n";
        echo '</div>' . "\n"; // .header

        echo '<div class="owbn-location-body" style="display: none;">' . "\n";

        // Row 1: name, online_only, url (always render name + toggle + url if available)
        echo '<div class="owbn-location-row">' . "\n";
        if (isset($subfields['name'])) {
            render_location_field($key, $i, 'name', $subfields['name'], $group['name'] ?? '');
        }
        if (isset($subfields['online_only'])) {
            render_location_field($key, $i, 'online_only', $subfields['online_only'], $group['online_only'] ?? '');
        } elseif (isset($subfields['online'])) {
            render_location_field($key, $i, 'online', $subfields['online'], $group['online'] ?? '');
        }
        if (isset($subfields['url'])) {
            render_location_field($key, $i, 'url', $subfields['url'], $group['url'] ?? '');
        }
        echo '</div>' . "\n";

        // Conditionally render location fields if not online-only
        if (!$online) {
            // Row 2: country, region, city
            echo '<div class="owbn-location-row">' . "\n";
            if (isset($subfields['country'])) {
                render_location_field($key, $i, 'country', $subfields['country'], $group['country'] ?? '');
            }
            if (isset($subfields['region'])) {
                render_location_field($key, $i, 'region', $subfields['region'], $group['region'] ?? '');
            }
            if (isset($subfields['city'])) {
                render_location_field($key, $i, 'city', $subfields['city'], $group['city'] ?? '');
            }
            echo '</div>' . "\n";

            // Row 3: address
            echo '<div class="owbn-location-row">' . "\n";
            if (isset($subfields['address'])) {
                render_location_field($key, $i, 'address', $subfields['address'], $group['address'] ?? '');
            }
            echo '</div>' . "\n";
        }

        echo '<button type="button" class="button remove-location">Remove</button>' . "\n";
        echo '</div>' . "\n"; // .body
        echo '</div>' . "\n"; // .block
    }

    echo '<button type="button" class="button add-location" data-field="' . esc_attr($key) . '">Add</button>' . "\n";
    echo '</div>' . "\n"; // .repeatable-group
}

// Render a single location field based on its type
function render_location_field($key, $index, $subkey, $meta, $value, $flat = false) {
    $field_id = $flat ? "{$key}_{$subkey}" : "{$key}_{$index}_{$subkey}";
    $field_name = $flat ? "{$key}[{$subkey}]" : "{$key}[{$index}][{$subkey}]";

    echo '<div class="owbn-location-field">' . "\n";
    echo '<label for="' . esc_attr($field_id) . '">' . esc_html($meta['label']) . '</label><br>' . "\n";

    switch ($meta['type']) {
        case 'select':
            $options = $meta['options'] ?? [];
            $extra_class = !empty($meta['search']) ? 'select2-searchable' : '';
            echo '<select class="owbn-select2 ' . esc_attr($extra_class) . '" name="' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '">' . "\n";
            echo '<option value="">' . esc_html__('— Select —', 'owbn-chronicle-manager') . '</option>' . "\n";
            foreach ($options as $key_option => $label) {
                $value_attr = is_string($key_option) ? $key_option : $label;
                echo '<option value="' . esc_attr($value_attr) . '" ' . selected($value, $value_attr, false) . '>' . esc_html($label) . '</option>' . "\n";
            }
            echo '</select>' . "\n";
            break;

        case 'multi_select':
            $selected = is_array($value) ? $value : [];
            $options = [];

            if (!empty($meta['source']) && $meta['source'] === 'owbn_genre_list') {
                $options = get_option('owbn_genre_list', []);
            }

            echo '<select class="owbn-select2" name="' . esc_attr($field_name) . '[]" multiple="multiple" id="' . esc_attr($field_id) . '">' . "\n";
            foreach ($options as $opt) {
                echo '<option value="' . esc_attr($opt) . '" ' . selected(in_array($opt, $selected, true), true, false) . '>' . esc_html($opt) . '</option>' . "\n";
            }
            echo '</select>' . "\n";
            break;

        case 'boolean':
            $is_checked = ($value === '1' || $value === 1 || $value === true);
            echo '<div class="owbn-boolean-switch">' . "\n";
            echo '  <span class="switch-label switch-label-left">' . __('No', 'owbn-chronicle-manager') . '</span>' . "\n";
            echo '  <label class="switch">' . "\n";
            echo '    <input type="checkbox" name="' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '" value="1" ' . checked($is_checked, true, false) . '>' . "\n";
            echo '    <span class="slider round"></span>' . "\n";
            echo '  </label>' . "\n";
            echo '  <span class="switch-label switch-label-right">' . __('Yes', 'owbn-chronicle-manager') . '</span>' . "\n";
            echo '</div>' . "\n";
            break;

        case 'wysiwyg':
            wp_editor(
                is_scalar($value) ? $value : '',
                $field_id,
                [
                    'textarea_name' => $field_name,
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny' => true,
                ]
            );
            break;

        case 'url':
            echo '<input type="url" name="' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" class="regular-text">' . "\n";
            break;

        default: // text
            echo '<input type="text" name="' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" class="regular-text">' . "\n";
    }

    echo '</div>' . "\n";
}

// Render the OOC location fields for the Chronicle custom post type
function owbn_render_ooc_location($key, $value, $meta) {
    $fields = $meta['fields'];
    $value = is_array($value) ? $value : [];

    echo '<div class="owbn-ooc-location-wrapper">' . "\n";
    echo '<div class="owbn-location-row">' . "\n";

    if (isset($fields['country'])) {
        render_location_field($key, 0, 'country', $fields['country'], $value['country'] ?? '', true);
    }
    if (isset($fields['region'])) {
        render_location_field($key, 0, 'region', $fields['region'], $value['region'] ?? '', true);
    }
    if (isset($fields['city'])) {
        render_location_field($key, 0, 'city', $fields['city'], $value['city'] ?? '', true);
    }

    echo '</div>' . "\n";

    echo '<div class="owbn-location-row">' . "\n";
    if (isset($fields['notes'])) {
        render_location_field($key, 0, 'notes', $fields['notes'], $value['notes'] ?? '', true);
    }
    echo '</div>' . "\n";

    echo '</div>' . "\n";
}