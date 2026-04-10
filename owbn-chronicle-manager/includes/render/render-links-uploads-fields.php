<?php
if (!defined('ABSPATH')) exit;

// Render document links field
function owbn_render_document_links_field($key, $value, $meta)
{
    $groups = is_array($value) ? $value : [];

    // Pre-populate required documents if not already present
    $post_type = get_post_type();
    $config = owbn_get_entity_config($post_type);
    $required_docs = $config['required_documents'] ?? [];
    if (!empty($required_docs)) {
        $existing_titles = array_map(function ($g) {
            return $g['title'] ?? '';
        }, $groups);
        foreach ($required_docs as $doc_title) {
            if (!in_array($doc_title, $existing_titles, true)) {
                array_unshift($groups, ['title' => $doc_title, 'link' => '', 'file_id' => '']);
            }
        }
    }

    if (empty($groups)) $groups[] = [];

    // Summary of incomplete required docs so the user sees the whole problem
    // at a glance, not after expanding every collapsed row.
    $incomplete_required = [];
    if (!empty($required_docs)) {
        foreach ($groups as $g) {
            if (!in_array($g['title'] ?? '', $required_docs, true)) continue;
            $has_url  = !empty(trim((string) ($g['link'] ?? '')));
            $has_file = !empty($g['file_id'] ?? '');
            if (!$has_url && !$has_file) {
                $incomplete_required[] = $g['title'];
            }
        }
    }
    if (!empty($incomplete_required)) {
        echo '<div class="owbn-doc-summary" style="margin:0 0 10px;padding:10px 12px;background:#fffbea;border-left:4px solid #dba617;">';
        echo '<strong>' . esc_html__('Required documents missing a URL or upload:', 'owbn-chronicle-manager') . '</strong>';
        echo '<ul style="margin:6px 0 0 20px;list-style:disc;">';
        foreach ($incomplete_required as $t) {
            echo '<li>' . esc_html($t) . '</li>';
        }
        echo '</ul>';
        echo '<small>' . esc_html__('Filling these will unblock publication. Other edits on this page save normally regardless.', 'owbn-chronicle-manager') . '</small>';
        echo '</div>' . "\n";
    }

    echo '<div class="owbn-repeatable-group" data-key="' . esc_attr($key) . '">' . "\n";

    foreach ($groups as $i => $group) {
        $is_required_doc = in_array($group['title'] ?? '', $required_docs, true);
        $is_incomplete_required = $is_required_doc && in_array($group['title'] ?? '', $incomplete_required, true);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo render_document_link_block($key, $i, $group, $is_required_doc, $is_incomplete_required);
    }

    // Template block (hidden)
    echo '<template class="owbn-document-template">';
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo render_document_link_block($key, '__INDEX__', []);
    echo '</template>';

    echo '<button type="button" class="button add-document-link" data-field="' . esc_attr($key) . '">Add</button>' . "\n";
    echo '</div>' . "\n";
}

function render_document_link_block($key, $index, $group, $is_required_doc = false, $is_incomplete_required = false)
{
    ob_start();

    $title = $group['title'] ?? '';
    $link = $group['link'] ?? '';
    $file_id = $group['file_id'] ?? '';
    $file_url = $file_id ? wp_get_attachment_url($file_id) : '';
    $header = $title ?: 'Document Link';
    if ($is_required_doc) {
        $header .= ' ' . ($is_incomplete_required
            ? __('(required — needs URL or upload)', 'owbn-chronicle-manager')
            : __('(required)', 'owbn-chronicle-manager'));
    }

    // Identify if this is a template or an empty block
    $is_template = ($index === '__INDEX__');
    $is_empty = !$title && !$link && !$file_id;

    // Only require if it's not a template and not empty
    $required_attr = (!$is_template && !$is_empty) ? ' required' : '';
    $disabled_attr = $is_template ? ' disabled' : '';

    // Auto-expand bodies that need user action: incomplete required docs.
    // Other rows stay collapsed to keep the metabox tidy.
    $body_style = ($is_incomplete_required && !$is_template) ? '' : 'display:none;';
    $block_class = 'owbn-document-block';
    if ($is_incomplete_required && !$is_template) $block_class .= ' owbn-document-block--needs-action';

?>
    <div class="<?php echo esc_attr($block_class); ?>">
        <div class="owbn-document-header">
            <strong><?php echo esc_html($header); ?></strong>
            <button type="button" class="toggle-document button">Toggle</button>
        </div>

        <div class="owbn-document-body" style="<?php echo esc_attr($body_style); ?>">

            <div class="owbn-document-row-wrap">
                <div class="owbn-document-row">
                    <label>Title (required)</label><br>
                    <?php if ($is_required_doc): ?>
                        <input type="hidden"
                            name="<?php echo esc_attr("{$key}[{$index}][title]"); ?>"
                            value="<?php echo esc_attr($title); ?>">
                        <input type="text" value="<?php echo esc_attr($title); ?>" class="regular-text" disabled>
                    <?php else: ?>
                    <input type="text"
                        name="<?php echo esc_attr("{$key}[{$index}][title]"); ?>"
                        value="<?php echo esc_attr($title); ?>"
                        class="regular-text"
                        <?php echo $required_attr ? esc_attr($required_attr) : ''; ?>
                        <?php echo $disabled_attr ? esc_attr($disabled_attr) : ''; ?>>
                    <?php endif; ?>
                </div>

                <div class="owbn-document-row">
                    <label>External URL (if no upload)</label><br>
                    <input type="url"
                        name="<?php echo esc_attr("{$key}[{$index}][link]"); ?>"
                        value="<?php echo esc_url($link); ?>"
                        class="regular-text"
                        <?php echo esc_attr($disabled_attr); ?>>
                </div>

                <div class="owbn-document-row">
                    <label>Upload File</label><br>
                    <input type="file"
                        name="<?php echo esc_attr("{$key}_{$index}_upload"); ?>"
                        <?php echo esc_attr($disabled_attr); ?>>
                    <?php if ($file_url): ?>
                        <p><a href="<?php echo esc_url($file_url); ?>" target="_blank">Current file</a></p>
                    <?php endif; ?>
                </div>

                <div class="owbn-document-row">
                    <label>Last Updated</label><br>
                    <?php $last_updated = $group['last_updated'] ?? ''; ?>
                    <input type="date"
                        name="<?php echo esc_attr("{$key}[{$index}][last_updated]"); ?>"
                        value="<?php echo esc_attr($last_updated); ?>"
                        class="regular-text"
                        <?php echo esc_attr($disabled_attr); ?>>
                </div>
            </div>

            <?php if (!$is_required_doc): ?>
                <button type="button" class="button remove-document-link">Remove</button>
            <?php endif; ?>
        </div>
    </div>
<?php

    return ob_get_clean();
}

// Render social links field
function owbn_render_social_links_field($key, $value, $meta)
{
    $groups = is_array($value) ? $value : [];
    if (empty($groups)) $groups[] = [];

    echo '<div class="owbn-repeatable-group" data-key="' . esc_attr($key) . '">' . "\n";

    foreach ($groups as $i => $group) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo render_social_link_block($key, $i, $group, $meta);
    }

    // Template block (hidden)
    echo '<div class="owbn-social-template" style="display:none;">';
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo render_social_link_block($key, '__INDEX__', [], $meta);
    echo '</div>';

    echo '<button type="button" class="button add-social-link" data-field="' . esc_attr($key) . '">Add Social Link</button>' . "\n";
    echo '</div>' . "\n";
}

function render_social_link_block($key, $index, $group, $field_meta = [])
{
    ob_start();

    $platform = $group['platform'] ?? '';
    $url = $group['url'] ?? '';
    $header = $platform ? ucfirst($platform) : __('Social Link', 'owbn-chronicle-manager');

    // Get platform and url field definitions from the passed-in field meta
    $platform_meta = $field_meta['fields']['platform'] ?? [];
    $platform_options = $platform_meta['options'] ?? [];
    $url_meta = $field_meta['fields']['url'] ?? [];

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
                    <label><?php echo esc_html($url_meta['label'] ?? 'URL'); ?></label><br>
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
function owbn_render_email_lists_field($key, $value, $meta)
{
    $groups = is_array($value) ? $value : [];
    if (empty($groups)) $groups[] = [];

    $subfields = $meta['fields'];

    echo '<div class="owbn-repeatable-group" data-key="' . esc_attr($key) . '">' . "\n";

    foreach ($groups as $i => $group) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo render_email_list_block($key, $i, $group, $subfields);
    }

    // Hidden template
    echo '<div class="owbn-email-template" style="display:none;">';
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo render_email_list_block($key, '__INDEX__', [], $subfields);
    echo '</div>';

    echo '<button type="button" class="button add-email-list" data-field="' . esc_attr($key) . '">Add</button>' . "\n";
    echo '</div>' . "\n";
}

function render_email_list_block($key, $index, $group, $subfields)
{
    ob_start();

    $list_name = $group['list_name'] ?? '';
    $email = $group['email_address'] ?? '';
    $desc = $group['description'] ?? '';
    $header = $list_name ?: ($email ?: 'Email List');

    $is_template = ($index === '__INDEX__');
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
                        class="regular-text"
                        <?php echo esc_attr($disabled_attr); ?>>
                </div>

                <div class="owbn-email-field">
                    <label><?php echo esc_html($subfields['email_address']['label']); ?></label><br>
                    <input type="email"
                        name="<?php echo esc_attr("{$key}[{$index}][email_address]"); ?>"
                        value="<?php echo esc_attr($email); ?>"
                        <?php echo esc_attr($disabled_attr); ?>>
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

function owbn_render_player_lists_field($key, $groups, $meta)
{
    $subfields = $meta['fields'] ?? [];
    $groups = is_array($groups) ? $groups : [];

    echo '<div class="owbn-player-lists-wrapper" data-field="' . esc_attr($key) . '">' . "\n";
    echo '<h4>' . esc_html($meta['label'] ?? 'Player Lists') . '</h4>' . "\n";

    foreach ($groups as $i => $group) {
        echo render_player_list_block($key, $i, $group, $subfields);
    }

    echo '<div class="owbn-player-template" style="display:none;">';
    echo render_player_list_block($key, '__INDEX__', [], $subfields);
    echo '</div>';

    echo '<button type="button" class="button add-player-list" data-field="' . esc_attr($key) . '">Add Player List</button>' . "\n";
    echo '</div>' . "\n";
}

function render_player_list_block($key, $index, $group, $subfields)
{
    ob_start();

    $list_name = $group['list_name'] ?? '';
    $access = $group['access'] ?? 'Public';
    $address = $group['address'] ?? '';
    $ic_ooc = $group['ic_ooc'] ?? 'OOC';
    $moderate = $group['moderate_address'] ?? '';
    $signup = $group['signup_url'] ?? '';
    $header = $list_name ?: 'Player List';

    $is_template = ($index === '__INDEX__');
    $disabled_attr = $is_template ? ' disabled' : '';
?>
    <div class="owbn-player-block">
        <div class="owbn-player-header">
            <strong><?php echo esc_html($header); ?></strong>
            <button type="button" class="toggle-player button button-small">Toggle</button>
        </div>
        <div class="owbn-player-body" style="display:none;">
            <div class="owbn-player-row">
                <div class="owbn-player-field">
                    <label>List Name</label>
                    <input type="text" name="<?php echo esc_attr("{$key}[{$index}][list_name]"); ?>" value="<?php echo esc_attr($list_name); ?>" class="regular-text" <?php echo esc_attr($disabled_attr); ?>>
                </div>
                <div class="owbn-player-field owbn-player-field--narrow">
                    <label>Access</label>
                    <select name="<?php echo esc_attr("{$key}[{$index}][access]"); ?>" <?php echo esc_attr($disabled_attr); ?>>
                        <option value="Public" <?php selected($access, 'Public'); ?>>Public</option>
                        <option value="Private" <?php selected($access, 'Private'); ?>>Private</option>
                    </select>
                </div>
                <div class="owbn-player-field owbn-player-field--narrow">
                    <label>IC/OOC</label>
                    <select name="<?php echo esc_attr("{$key}[{$index}][ic_ooc]"); ?>" <?php echo esc_attr($disabled_attr); ?>>
                        <option value="IC" <?php selected($ic_ooc, 'IC'); ?>>IC</option>
                        <option value="OOC" <?php selected($ic_ooc, 'OOC'); ?>>OOC</option>
                    </select>
                </div>
            </div>
            <div class="owbn-player-row">
                <div class="owbn-player-field">
                    <label>Address</label>
                    <input type="email" name="<?php echo esc_attr("{$key}[{$index}][address]"); ?>" value="<?php echo esc_attr($address); ?>" class="regular-text" <?php echo esc_attr($disabled_attr); ?>>
                </div>
                <div class="owbn-player-field">
                    <label>Moderator Email</label>
                    <input type="email" name="<?php echo esc_attr("{$key}[{$index}][moderate_address]"); ?>" value="<?php echo esc_attr($moderate); ?>" class="regular-text" <?php echo esc_attr($disabled_attr); ?>>
                </div>
                <div class="owbn-player-field">
                    <label>Sign Up URL</label>
                    <input type="url" name="<?php echo esc_attr("{$key}[{$index}][signup_url]"); ?>" value="<?php echo esc_attr($signup); ?>" class="regular-text" <?php echo esc_attr($disabled_attr); ?>>
                </div>
            </div>
            <button type="button" class="button button-small remove-player">Remove</button>
        </div>
    </div>
<?php
    return ob_get_clean();
}
