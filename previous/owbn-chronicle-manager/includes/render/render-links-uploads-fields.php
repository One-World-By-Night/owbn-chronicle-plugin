<?php
if (!defined('ABSPATH')) exit;

// Render document links field
function owbn_render_document_links_field($key, $value, $meta) {
    $groups = is_array($value) ? $value : [];
    if (empty($groups)) $groups[] = [];

    echo '<div class="owbn-repeatable-group" data-key="' . esc_attr($key) . '">' . "\n";

    foreach ($groups as $i => $group) {
        echo render_document_link_block($key, $i, $group);
    }

    // Template block (hidden)
    echo '<template class="owbn-document-template">';
    echo render_document_link_block($key, '__INDEX__', []);
    echo '</template>';

    echo '<button type="button" class="button add-document-link" data-field="' . esc_attr($key) . '">Add</button>' . "\n";
    echo '</div>' . "\n";
}

function render_document_link_block($key, $index, $group) {
    ob_start();

    $title = $group['title'] ?? '';
    $link = $group['link'] ?? '';
    $file_id = $group['file_id'] ?? '';
    $file_url = $file_id ? wp_get_attachment_url($file_id) : '';
    $header = $title ?: 'Document Link';

    // Identify if this is a template or an empty block
    $is_template = ($index === '__INDEX__');
    $is_empty = !$title && !$link && !$file_id;

    // Only require if it's not a template and not empty
    $required_attr = (!$is_template && !$is_empty) ? ' required' : '';
    $disabled_attr = $is_template ? ' disabled' : '';

    ?>
    <div class="owbn-document-block">
        <div class="owbn-document-header">
            <strong><?php echo esc_html($header); ?></strong>
            <button type="button" class="toggle-document button">Toggle</button>
        </div>

        <div class="owbn-document-body" style="display:none;">

            <div class="owbn-document-row-wrap">
                <div class="owbn-document-row">
                    <label>Title (required)</label><br>
                    <input type="text"
                           name="<?php echo esc_attr("{$key}[{$index}][title]"); ?>"
                           value="<?php echo esc_attr($title); ?>"
                           class="regular-text"
                           <?php echo $required_attr . $disabled_attr; ?>>
                </div>

                <div class="owbn-document-row">
                    <label>External URL (if no upload)</label><br>
                    <input type="url"
                           name="<?php echo esc_attr("{$key}[{$index}][link]"); ?>"
                           value="<?php echo esc_url($link); ?>"
                           class="regular-text"
                           <?php echo $disabled_attr; ?>>
                </div>

                <div class="owbn-document-row">
                    <label>Upload File</label><br>
                    <input type="file"
                           name="<?php echo esc_attr("{$key}[{$index}][upload]"); ?>"
                           <?php echo $disabled_attr; ?>>
                    <?php if ($file_url): ?>
                        <p><a href="<?php echo esc_url($file_url); ?>" target="_blank">Current file</a></p>
                    <?php endif; ?>
                </div>
            </div>

            <button type="button" class="button remove-document-link">Remove</button>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

// Render social links field
function owbn_render_social_links_field($key, $value, $meta) {
    $groups = is_array($value) ? $value : [];
    if (empty($groups)) $groups[] = [];

    echo '<div class="owbn-repeatable-group" data-key="' . esc_attr($key) . '">' . "\n";

    foreach ($groups as $i => $group) {
        echo render_social_link_block($key, $i, $group);
    }

    // Template block (hidden)
    echo '<div class="owbn-social-template" style="display:none;">';
    echo render_social_link_block($key, '__INDEX__', []);
    echo '</div>';

    echo '<button type="button" class="button add-social-link" data-field="' . esc_attr($key) . '">Add Social Link</button>' . "\n";
    echo '</div>' . "\n";
}

function render_social_link_block($key, $index, $group) {
    ob_start();

    $platform = $group['platform'] ?? '';
    $url = $group['url'] ?? '';
    $header = $platform ? ucfirst($platform) : __('Social Link', 'owbn-chronicle-manager');

    // Get platform field definition from fields.php
    $definitions = owbn_get_chronicle_field_definitions();
    $platform_meta = $definitions['Links']['social_urls']['fields']['platform'] ?? [];
    $platform_options = $platform_meta['options'] ?? [];

    ?>
    <div class="owbn-social-block">
        <div class="owbn-social-header">
            <strong><?php echo esc_html($header); ?></strong>
            <button type="button" class="toggle-social button">Toggle</button>
        </div>

        <div class="owbn-social-body" style="display:none;">
            <div class="owbn-social-row-wrap">
                <div class="owbn-social-row">
                    <label><?php echo esc_html($platform_meta['label'] ?? 'Platform'); ?></label><br>
                    <select name="<?php echo esc_attr("{$key}[{$index}][platform]"); ?>" class="owbn-select2 single">
                        <?php foreach ($platform_options as $opt_key => $label): ?>
                            <option value="<?php echo esc_attr($opt_key); ?>" <?php selected($platform, $opt_key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="owbn-social-row">
                    <label><?php echo esc_html($definitions['Links']['social_urls']['fields']['url']['label']); ?></label><br>
                    <input type="url" name="<?php echo esc_attr("{$key}[{$index}][url]"); ?>" value="<?php echo esc_url($url); ?>" class="regular-text">
                </div>
            </div>

            <button type="button" class="button remove-social-link">Remove</button>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

// Render email lists field
function owbn_render_email_lists_field($key, $value, $meta) {
    $groups = is_array($value) ? $value : [];
    if (empty($groups)) $groups[] = [];

    $subfields = $meta['fields'];

    echo '<div class="owbn-repeatable-group" data-key="' . esc_attr($key) . '">' . "\n";

    foreach ($groups as $i => $group) {
        echo render_email_list_block($key, $i, $group, $subfields);
    }

    // Hidden template
    echo '<div class="owbn-email-template" style="display:none;">';
    echo render_email_list_block($key, '__INDEX__', [], $subfields);
    echo '</div>';

    echo '<button type="button" class="button add-email-list" data-field="' . esc_attr($key) . '">Add</button>' . "\n";
    echo '</div>' . "\n";
}

function render_email_list_block($key, $index, $group, $subfields) {
    ob_start();

    $list_name = $group['list_name'] ?? '';
    $email = $group['email_address'] ?? '';
    $desc = $group['description'] ?? '';
    $header = $list_name ?: 'Email List';

    $is_template = ($index === '__INDEX__');
    $is_empty = !$list_name && !$email && !$desc;

    // Only require name if not template and not empty
    $required_attr = (!$is_template && !$is_empty) ? ' required' : '';
    $disabled_attr = $is_template ? ' disabled' : '';

    ?>
    <div class="owbn-email-block">
        <div class="owbn-email-header">
            <strong><?php echo esc_html($header); ?></strong>
            <button type="button" class="toggle-email button">Toggle</button>
        </div>

        <div class="owbn-email-body" style="display:none;">
            <div class="owbn-email-row">
                <div class="owbn-email-field">
                    <label><?php echo esc_html($subfields['list_name']['label']); ?></label><br>
                    <input type="text"
                           name="<?php echo esc_attr("{$key}[{$index}][list_name]"); ?>"
                           value="<?php echo esc_attr($list_name); ?>"
                           <?php echo $required_attr . $disabled_attr; ?>>
                </div>

                <div class="owbn-email-field">
                    <label><?php echo esc_html($subfields['email_address']['label']); ?></label><br>
                    <input type="email"
                           name="<?php echo esc_attr("{$key}[{$index}][email_address]"); ?>"
                           value="<?php echo esc_attr($email); ?>"
                           <?php echo $disabled_attr; ?>>
                </div>
            </div>

            <div class="owbn-email-row-full">
                <div class="owbn-email-field">
                    <label><?php echo esc_html($subfields['description']['label']); ?></label><br>
                    <?php
                    $editor_id = "{$key}_{$index}_description";
                    wp_editor($desc, $editor_id, [
                        'textarea_name' => "{$key}[{$index}][description]",
                        'textarea_rows' => 4,
                        'media_buttons' => false,
                    ]);
                    ?>
                </div>
            </div>

            <button type="button" class="button remove-email-list">Remove</button>
        </div>
    </div>
    <?php

    return ob_get_clean();
}