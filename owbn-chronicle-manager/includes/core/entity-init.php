<?php
/**
 * Entity Type Initialization
 *
 * Generic CPT registration, meta registration, metabox setup, permission
 * mapping, permalinks, rewrites, and template loading -- all driven by
 * entity config arrays from the registry.
 *
 */

if (!defined('ABSPATH')) exit;

/**
 * Produce a short human-readable validation error message for a field.
 *
 * Generic by field type so every field gets a sensible default without
 * per-field configuration. Callers can override by setting
 * `$meta['error_message']` in field definitions.
 */
function owbn_format_field_error_message(array $meta): string
{
    if (!empty($meta['error_message'])) {
        return (string) $meta['error_message'];
    }
    $type = $meta['type'] ?? '';
    switch ($type) {
        case 'slug':
            return __('Invalid or duplicate slug. Your other edits were saved; this field was not.', 'owbn-chronicle-manager');
        case 'user_info':
            return __('User, display name, and display email are all required. Your other edits were saved; this field was not.', 'owbn-chronicle-manager');
        case 'select':
            return __('Please choose a value. Your other edits were saved; this field was not.', 'owbn-chronicle-manager');
        default:
            return __('This field is required. Your other edits were saved; this field was not.', 'owbn-chronicle-manager');
    }
}

function owbn_register_entity_cpt(array $config): void
{
    if (!owbn_is_entity_enabled($config['post_type'])) return;

    $singular = $config['singular'];
    $plural   = $config['plural'];
    $lc_singular = strtolower($singular);
    $lc_plural   = strtolower($plural);

    $menu_name = $config['menu_name'] ?? $plural;

    $labels = [
        'name'                     => _x($plural, 'Post type general name', 'owbn-chronicle-manager'),
        'singular_name'            => _x($singular, 'Post type singular name', 'owbn-chronicle-manager'),
        'menu_name'                => _x($menu_name, 'Admin Menu text', 'owbn-chronicle-manager'),
        'name_admin_bar'           => _x($singular, 'Add New on Toolbar', 'owbn-chronicle-manager'),
        'add_new'                  => isset($config['add_new_label']) ? __($config['add_new_label'], 'owbn-chronicle-manager') : _x('Add New', $singular, 'owbn-chronicle-manager'),
        'add_new_item'             => __("Add New {$singular}", 'owbn-chronicle-manager'),
        'new_item'                 => __("New {$singular}", 'owbn-chronicle-manager'),
        'edit_item'                => __("Edit {$singular}", 'owbn-chronicle-manager'),
        'view_item'                => __("View {$singular}", 'owbn-chronicle-manager'),
        'view_items'               => __("View {$plural}", 'owbn-chronicle-manager'),
        'all_items'                => __("All {$plural}", 'owbn-chronicle-manager'),
        'search_items'             => __("Search {$plural}", 'owbn-chronicle-manager'),
        'parent_item_colon'        => __("Parent {$plural}:", 'owbn-chronicle-manager'),
        'not_found'                => __("No {$lc_plural} found.", 'owbn-chronicle-manager'),
        'not_found_in_trash'       => __("No {$lc_plural} found in Trash.", 'owbn-chronicle-manager'),
        'archives'                 => __("{$singular} Archives", 'owbn-chronicle-manager'),
        'insert_into_item'         => __("Insert into {$lc_singular}", 'owbn-chronicle-manager'),
        'uploaded_to_this_item'    => __("Uploaded to this {$lc_singular}", 'owbn-chronicle-manager'),
        'filter_items_list'        => __("Filter {$lc_plural} list", 'owbn-chronicle-manager'),
        'items_list_navigation'    => __("{$plural} list navigation", 'owbn-chronicle-manager'),
        'items_list'               => __("{$plural} list", 'owbn-chronicle-manager'),
    ];

    // Build the full capabilities map from the config's base capabilities.
    $base_caps = $config['capabilities'] ?? [];
    $capabilities = array_merge([
        'edit_post'              => $base_caps['edit_post'] ?? "edit_{$config['post_type']}",
        'read_post'              => $base_caps['read_post'] ?? "read_{$config['post_type']}",
        'delete_post'            => $base_caps['delete_post'] ?? "delete_{$config['post_type']}",
        'edit_posts'             => $base_caps['edit_posts'] ?? 'ocm_view_list',
        'edit_others_posts'      => $base_caps['edit_post'] ?? "edit_{$config['post_type']}",
        'publish_posts'          => $base_caps['publish_posts'] ?? "publish_{$config['post_type']}",
        'read_private_posts'     => $base_caps['read_post'] ?? "read_{$config['post_type']}",
        'delete_posts'           => $base_caps['delete_post'] ?? "delete_{$config['post_type']}",
        'delete_others_posts'    => $base_caps['delete_post'] ?? "delete_{$config['post_type']}",
        'delete_published_posts' => $base_caps['delete_post'] ?? "delete_{$config['post_type']}",
        'delete_private_posts'   => $base_caps['delete_post'] ?? "delete_{$config['post_type']}",
        'edit_published_posts'   => $base_caps['edit_post'] ?? "edit_{$config['post_type']}",
        'edit_private_posts'     => $base_caps['edit_post'] ?? "edit_{$config['post_type']}",
    ], $base_caps);

    $cpt_args = [
        'labels'          => $labels,
        'public'          => true,
        'show_ui'         => true,
        'show_in_menu'    => isset($config['show_in_menu']) ? $config['show_in_menu'] : true,
        'has_archive'     => false,
        'rewrite'         => ['slug' => $config['url_slug'], 'with_front' => false],
        'supports'        => ['title', 'editor', 'author', 'revisions'],
        'show_in_rest'    => true,
        'menu_icon'       => $config['menu_icon'] ?? 'dashicons-admin-generic',
        'capability_type' => $config['post_type'],
        'map_meta_cap'    => true,
        'capabilities'    => $capabilities,
    ];

    if (isset($config['menu_position'])) {
        $cpt_args['menu_position'] = (int) $config['menu_position'];
    }

    register_post_type($config['post_type'], $cpt_args);
}

function owbn_register_entity_meta(array $config): void
{
    if (!owbn_is_entity_enabled($config['post_type'])) return;

    $callable = $config['field_definitions'] ?? null;
    if (!$callable || !is_callable($callable)) return;

    $field_groups = call_user_func($callable);
    if (!is_array($field_groups)) return;

    // Field types that store array/object data.
    $complex_types = [
        'ooc_location',
        'location_group',
        'session_group',
        'one_off_group',
        'ast_group',
        'repeatable_group',
        'readonly_history',
        'document_links_group',
        'social_links_group',
        'email_lists_group',
        'player_lists_group',
        'user_info',
        'multi_select',
    ];

    foreach ($field_groups as $fields) {
        foreach ($fields as $key => $meta) {
            // Skip render-only hints (e.g. `__description__` tab hint).
            if (!is_array($meta)) continue;
            $type = $meta['type'] ?? 'text';
            $is_complex = in_array($type, $complex_types, true);

            register_post_meta($config['post_type'], $key, [
                'type'              => $is_complex ? 'array' : 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => null,
            ]);
        }
    }
}

function owbn_add_entity_meta_box(array $config): void
{
    if (!owbn_is_entity_enabled($config['post_type'])) return;

    $entity_key = $config['entity_key'];
    $singular   = $config['singular'];

    add_meta_box(
        "owbn_{$entity_key}_fields",
        "{$singular} Fields",
        'owbn_render_entity_metabox',
        $config['post_type'],
        'normal',
        'default'
    );
}

/**
 * Generic metabox render callback.
 *
 * Looks up the entity config for the current post type and delegates to
 * the appropriate rendering logic.
 *
 * @param WP_Post $post The post being edited.
 */
function owbn_render_entity_metabox($post)
{
    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return;

    $user_id    = get_current_user_id();
    $entity_key = $config['entity_key'];
    $singular   = $config['singular'];

    // Permission check
    if (!owbn_user_can_edit_entity($user_id, $post->ID)) {
        echo '<p>' . esc_html(
            sprintf(
                /* translators: %s: entity singular name */
                __('You do not have permission to edit this %s.', 'owbn-chronicle-manager'),
                $singular
            )
        ) . '</p>';
        return;
    }

    wp_nonce_field("owbn_{$entity_key}_save", "owbn_{$entity_key}_nonce");

    $callable = $config['field_definitions'] ?? null;
    if (!$callable || !is_callable($callable)) return;

    $field_groups = call_user_func($callable);
    if (!is_array($field_groups)) return;

    $errors            = get_transient("owbn_{$entity_key}_errors_{$post->ID}") ?: [];
    // Normalize errors: the transient may contain plain field keys, legacy
    // `document_links:Title`, or new `publication_gate:Title` tokens. For
    // inline field highlighting we only care about plain field keys.
    $error_field_keys = [];
    $error_messages   = [];
    foreach ($errors as $err) {
        if (!is_string($err)) continue;
        if (strpos($err, 'publication_gate:') === 0 || strpos($err, 'document_links:') === 0) {
            continue;
        }
        $error_field_keys[] = $err;
    }
    // Submitted-values overlay: when a field failed integrity validation, the
    // user's typed values were stashed in a transient. Re-render those fields
    // with the stashed value so the user can fix and resubmit without losing
    // their typing.
    $submitted_values  = get_transient("owbn_{$entity_key}_submitted_values_{$post->ID}") ?: [];
    $restricted_fields = $config['restricted_fields'] ?? [];
    $immutable_fields  = $config['immutable_fields'] ?? [];
    $can_edit_metadata = owbn_user_can_edit_entity_metadata($user_id);

    // Persistent compliance gap banner — compliance meta is written by the
    // save handler and lives in post meta, not transients, so it survives
    // reloads and only disappears when the deficiency is actually resolved.
    $compliance_meta_key = "_owbn_{$entity_key}_compliance_gaps";
    $compliance_gaps     = get_post_meta($post->ID, $compliance_meta_key, true);
    if (is_array($compliance_gaps) && !empty($compliance_gaps)) {
        echo '<div class="owbn-compliance-banner" style="margin-bottom:15px;padding:12px 14px;background:#fffbea;border-left:4px solid #dba617;">';
        echo '<strong><span class="dashicons dashicons-flag" style="vertical-align:text-bottom;"></span> ';
        echo esc_html__('Required documents missing', 'owbn-chronicle-manager');
        echo '</strong><br><small>';
        echo esc_html__('This chronicle is missing URLs or uploads for the following required documents. Add them in the Document Links section below. Your other edits are still being saved normally.', 'owbn-chronicle-manager');
        echo '</small><ul style="margin:8px 0 0 20px;list-style:disc;">';
        foreach ($compliance_gaps as $gap_title) {
            echo '<li>' . esc_html($gap_title) . '</li>';
        }
        echo '</ul></div>' . "\n";
    }

    echo "\n<div class=\"owbn-meta-view\">\n";

    // Data source banner — derived from site-level settings
    $url_slug    = $config['url_slug'] ?? '';
    $mode_option = "owbn_{$url_slug}_mode";
    $data_mode   = get_option($mode_option, 'local');

    if ($data_mode === 'remote') {
        $remote_url = get_option("owbn_{$url_slug}_remote_url", '');
        $banner_text = __('REMOTE', 'owbn-chronicle-manager');
        if ($remote_url) {
            $banner_text .= ': ' . $remote_url;
        }
        echo '<div class="owbn-data-source-banner" style="margin-bottom: 15px; padding: 10px 14px; background-color: #fff3cd; border-left: 4px solid #ffc107; font-weight: 600;">';
        echo esc_html($banner_text);
        echo '</div>' . "\n";
    } else {
        echo '<div class="owbn-data-source-banner" style="margin-bottom: 15px; padding: 10px 14px; background-color: #e8f5e9; border-left: 4px solid #4CAF50; font-weight: 600;">';
        echo esc_html__('LOCAL', 'owbn-chronicle-manager');
        echo '</div>' . "\n";
    }

    // Pending changeset banner (inside metabox, above fields)
    $pending = get_post_meta($post->ID, '_owbn_pending_changes', true);
    if (!empty($pending) && !empty($pending['fields'])) {
        $is_current_admin = owbn_is_admin_user();

        if ($is_current_admin) {
            // Admin sees details of pending changes
            $pending_labels = [];
            foreach ($pending['fields'] as $pkey => $pval) {
                foreach ($field_groups as $section => $sfields) {
                    if (isset($sfields[$pkey])) {
                        $pending_labels[] = $sfields[$pkey]['label'];
                    }
                }
            }

            $submitted_by = get_userdata($pending['submitted_by'] ?? 0);
            $submitted_name = $submitted_by ? $submitted_by->display_name : __('Unknown', 'owbn-chronicle-manager');

            echo '<div style="margin-bottom: 15px; padding: 12px 14px; background-color: #fff8e5; border-left: 4px solid #d63638;">';
            echo '<strong><span class="dashicons dashicons-clock" style="vertical-align: text-bottom;"></span> ';
            echo esc_html__('Pending Staff Changes', 'owbn-chronicle-manager');
            echo '</strong>';
            echo '<br><small>';
            printf(
                esc_html__('Submitted by %s on %s', 'owbn-chronicle-manager'),
                esc_html($submitted_name),
                esc_html($pending['submitted_at'] ?? '')
            );
            if (!empty($pending['self_promoted'])) {
                echo ' &mdash; <span style="color: #d63638; font-weight: bold;">' . esc_html__('Self-promotion detected', 'owbn-chronicle-manager') . '</span>';
            }
            echo '</small>';
            if (!empty($pending_labels)) {
                echo '<br><small>' . esc_html__('Fields:', 'owbn-chronicle-manager') . ' ' . esc_html(implode(', ', $pending_labels)) . '</small>';
            }
            echo '<br><small><em>' . esc_html__('Use the Approve/Reject buttons above to act on these changes.', 'owbn-chronicle-manager') . '</em></small>';
            echo '</div>' . "\n";
        } else {
            // Non-admin sees pending status
            echo '<div style="margin-bottom: 15px; padding: 12px 14px; background-color: #fff8e5; border-left: 4px solid #0073aa;">';
            echo '<strong><span class="dashicons dashicons-clock" style="vertical-align: text-bottom;"></span> ';
            echo esc_html__('Pending Staff Changes', 'owbn-chronicle-manager');
            echo '</strong>';
            echo '<br><small><em>' . esc_html__('Your staff changes are awaiting admin approval. Other field changes were saved.', 'owbn-chronicle-manager') . '</em></small>';
            echo '</div>' . "\n";
        }
    }

    // ── Header / Tabs / Content editor layout ──────────────────────────
    // Group keys determine layout:
    //   `__header__`  → rendered above the tab nav, not as a tab
    //   empty groups  → tab panel hosts wp_editor() for post_content
    //   everything else → normal tab with form-table field list

    // Factor field-row rendering into a closure so header and tab panels share
    // exactly the same dispatcher without copy-paste drift.
    $render_field_row = function ($key, $meta) use ($post, $submitted_values, $error_field_keys, $immutable_fields, $restricted_fields, $can_edit_metadata) {
        $value       = get_post_meta($post->ID, $key, true);
        if (isset($submitted_values[$key])) {
            $value = $submitted_values[$key];
        }
        $label       = $meta['label'] ?? $key;
        $type        = $meta['type'] ?? 'text';
        $is_errored  = in_array($key, $error_field_keys, true);
        $error_class = $is_errored ? ' owbn-error-field' : '';

        $is_immutable  = in_array($key, $immutable_fields, true) && !empty($value);
        $is_restricted = in_array($key, $restricted_fields, true) && !$can_edit_metadata;
        $disabled_attr = ($is_immutable || $is_restricted) ? ' disabled' : '';

        echo '<tr>';
        echo '<th><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td class="' . esc_attr(trim($error_class)) . '">';

        if (!empty($meta['description'])) {
            echo '<p class="description owbn-field-description" style="margin:0 0 6px;color:#646970;font-style:italic;">'
                . wp_kses_post($meta['description'])
                . '</p>';
        }

        switch ($type) {
            case 'wysiwyg':
                owbn_render_wysiwyg_editor($key, $value);
                break;
            case 'select':
                owbn_render_select_field($key, $value, $meta, $disabled_attr);
                break;
            case 'chronicle_select':
                owbn_render_entity_select_field($key, $value, $meta, $label, $error_class, $disabled_attr);
                break;
            case 'multi_select':
                owbn_render_multi_select_field($key, $value, $meta, $disabled_attr);
                break;
            case 'session_group':
                owbn_render_session_group($key, $value, $meta, $post->ID);
                break;
            case 'one_off_group':
                owbn_render_one_off_group($key, $value, $meta, $post->ID);
                break;
            case 'repeatable_group':
                owbn_render_repeatable_group($key, $value, $meta);
                break;
            case 'document_links_group':
                owbn_render_document_links_field($key, $value, $meta);
                break;
            case 'social_links_group':
                owbn_render_social_links_field($key, $value, $meta);
                break;
            case 'email_lists_group':
                owbn_render_email_lists_field($key, $value, $meta);
                break;
            case 'player_lists_group':
                owbn_render_player_lists_field($key, $value, $meta);
                break;
            case 'user_info':
                owbn_render_user_info($key, $value, $meta);
                break;
            case 'ast_group':
                owbn_render_ast_group($key, $value, $meta, $key);
                break;
            case 'boolean':
                owbn_render_boolean_field($key, $value, $disabled_attr);
                break;
            case 'ooc_location':
                owbn_render_ooc_location($key, $value, $meta);
                break;
            case 'location_group':
                owbn_render_location_group($key, $value, $meta);
                break;
            case 'readonly_history':
                owbn_render_readonly_history($key, $value, $meta);
                break;
            case 'date':
                echo '<input type="date" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="regular-text"' . esc_attr($disabled_attr) . '>';
                break;
            case 'number':
                echo '<input type="number" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '"' . esc_attr($disabled_attr) . '>';
                break;
            case 'json':
                echo '<textarea class="large-text code" rows="4" name="' . esc_attr($key) . '"' . esc_attr($disabled_attr) . '>' .
                    esc_textarea(is_scalar($value) ? $value : wp_json_encode($value)) .
                    '</textarea>';
                break;
            case 'slug':
                $slug_disabled = !empty($value) ? ' disabled' : $disabled_attr;
                owbn_render_slug_field($key, $value, $slug_disabled);
                break;
            default:
                echo '<input type="text" class="regular-text" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '"' . esc_attr($disabled_attr) . '>';
                break;
        }

        if ($is_errored) {
            echo '<p class="owbn-field-error-message" style="color:#b32d2e;margin:6px 0 0;font-weight:600;">';
            echo esc_html(owbn_format_field_error_message($meta));
            echo '</p>';
        }

        echo '</td></tr>';
    };

    // Header fields (above the tabs). Emits a single table in .owbn-header-block.
    if (!empty($field_groups['__header__']) && is_array($field_groups['__header__'])) {
        echo '<div class="owbn-header-block">';
        echo '<table class="form-table"><tbody>';
        foreach ($field_groups['__header__'] as $key => $meta) {
            if (strpos((string) $key, '__') === 0) continue;
            $render_field_row($key, $meta);
        }
        echo '</tbody></table>';
        echo '</div>';

        // Live-toggle: chronicle_parent is only relevant for satellite chronicles.
        // Hide its row when the satellite switch is off; show when flipped on.
        // The exclusive_fields config already clears the parent value on save
        // when satellite=0, so hiding the UI is purely cosmetic.
        echo '<script>
        (function(){
            var sat = document.getElementById("chronicle_satellite");
            var parentLabel = document.querySelector(".owbn-header-block label[for=\"chronicle_parent\"]");
            var parentRow = parentLabel ? parentLabel.closest("tr") : null;
            if (!sat || !parentRow) return;
            function sync() { parentRow.style.display = sat.checked ? "" : "none"; }
            sync();
            sat.addEventListener("change", sync);
        })();
        </script>' . "\n";
    }

    // Tabbed groups: everything except __header__.
    $tab_groups = array_filter(
        $field_groups,
        function ($key) { return $key !== '__header__'; },
        ARRAY_FILTER_USE_KEY
    );

    // Determine active tab (first by default, or first one containing an error).
    $active_tab_index = 0;
    if (!empty($error_field_keys)) {
        $idx = 0;
        foreach ($tab_groups as $section_label => $fields) {
            foreach (array_keys($fields) as $fkey) {
                if (strpos((string) $fkey, '__') === 0) continue;
                if (in_array($fkey, $error_field_keys, true)) {
                    $active_tab_index = $idx;
                    break 2;
                }
            }
            $idx++;
        }
    }

    echo '<style>
        .owbn-header-block { margin-bottom:14px; padding:10px 0; border-bottom:1px solid #e0e0e0; }
        .owbn-header-block .form-table { margin-top:0; }
        .owbn-header-block .form-table th,
        .owbn-header-block .form-table td { padding:4px 10px; vertical-align:middle; }
        .owbn-header-block .form-table .description { margin:2px 0 0; }
        .owbn-header-block .owbn-boolean-switch { margin:0; }
        .owbn-metabox-tabs { display:flex; flex-wrap:wrap; gap:2px; margin:0 0 12px; padding:0; border-bottom:1px solid #c3c4c7; list-style:none; }
        .owbn-metabox-tabs button { background:#f0f0f1; border:1px solid #c3c4c7; border-bottom:none; border-radius:4px 4px 0 0; padding:8px 14px; margin:0 2px -1px 0; cursor:pointer; font-size:13px; font-weight:500; color:#2c3338; }
        .owbn-metabox-tabs button:hover { background:#fff; }
        .owbn-metabox-tabs button.is-active { background:#fff; border-bottom:1px solid #fff; color:#2271b1; font-weight:600; }
        .owbn-metabox-tabs button.has-error { color:#b32d2e; }
        .owbn-metabox-tabs button.has-error::after { content:" \26A0"; }
        .owbn-field-group { display:none; }
        .owbn-field-group.is-active { display:block; }
        .owbn-field-group > h3 { display:none; }
    </style>' . "\n";

    echo '<ul class="owbn-metabox-tabs" role="tablist">' . "\n";
    $i_tab = 0;
    foreach ($tab_groups as $label => $fields) {
        $tab_has_error = false;
        foreach (array_keys($fields) as $fkey) {
            if (strpos((string) $fkey, '__') === 0) continue;
            if (in_array($fkey, $error_field_keys, true)) {
                $tab_has_error = true;
                break;
            }
        }
        $btn_classes = array();
        if ($i_tab === $active_tab_index) $btn_classes[] = 'is-active';
        if ($tab_has_error) $btn_classes[] = 'has-error';
        $class_attr = !empty($btn_classes) ? ' class="' . esc_attr(implode(' ', $btn_classes)) . '"' : '';
        echo '<li><button type="button"' . $class_attr . ' data-owbn-tab="' . esc_attr($i_tab) . '">' . esc_html($label) . '</button></li>' . "\n";
        $i_tab++;
    }
    echo '</ul>' . "\n";

    $i_tab = 0;
    foreach ($tab_groups as $section_label => $fields) {
        $is_active = ($i_tab === $active_tab_index) ? ' is-active' : '';
        echo '<div class="owbn-field-group' . esc_attr($is_active) . '" data-owbn-panel="' . esc_attr($i_tab) . '">';
        echo '<h3>' . esc_html($section_label) . '</h3>';

        $has_renderable_fields = false;
        foreach (array_keys($fields) as $fkey) {
            if (strpos((string) $fkey, '__') !== 0) {
                $has_renderable_fields = true;
                break;
            }
        }

        if (!$has_renderable_fields) {
            // Empty group → host wp_editor() for post_content here. The user's
            // typed content submits via the standard `post_content` POST key
            // that WP's own save_post handler picks up. We removed native editor
            // support for this CPT elsewhere so this is the only editor on the
            // page.
            if (!empty($fields['__description__'])) {
                echo '<p class="description owbn-tab-description" style="margin:0 0 8px;color:#646970;font-style:italic;">'
                    . wp_kses_post($fields['__description__'])
                    . '</p>';
            }
            wp_editor(
                $post->post_content,
                'content', // matches the standard #wp-content-wrap id WP expects
                array(
                    'textarea_name' => 'content',
                    'textarea_rows' => 15,
                    'media_buttons' => true,
                )
            );
        } else {
            echo '<table class="form-table"><tbody>';
            foreach ($fields as $key => $meta) {
                if (strpos((string) $key, '__') === 0) continue;
                $render_field_row($key, $meta);
            }
            echo '</tbody></table>';
        }

        echo '</div>';
        $i_tab++;
    }

    // Tab switching JS — kept inline to avoid a separate enqueue for a single
    // metabox feature. Uses event delegation on the tab list.
    echo '<script>
    (function(){
        var metabox = document.querySelector(".owbn-meta-view");
        if (!metabox) return;
        var tabs = metabox.querySelector(".owbn-metabox-tabs");
        if (!tabs) return;
        tabs.addEventListener("click", function(e){
            var btn = e.target.closest("button[data-owbn-tab]");
            if (!btn) return;
            e.preventDefault();
            var target = btn.getAttribute("data-owbn-tab");
            Array.prototype.forEach.call(
                tabs.querySelectorAll("button[data-owbn-tab]"),
                function(b){ b.classList.toggle("is-active", b === btn); }
            );
            Array.prototype.forEach.call(
                metabox.querySelectorAll(".owbn-field-group[data-owbn-panel]"),
                function(p){ p.classList.toggle("is-active", p.getAttribute("data-owbn-panel") === target); }
            );
        });
    })();
    </script>' . "\n";

    echo '</div>';

    // One-shot: consume the submitted-values transient now that we've rendered
    // the form. The error-list transient stays for admin-notices to display,
    // and is cleared by admin-notices after the banner renders.
    if (!empty($submitted_values)) {
        delete_transient("owbn_{$entity_key}_submitted_values_{$post->ID}");
    }
}

/**
 * Generic map_meta_cap filter for all registered entity types.
 *
 * @param array  $caps    Primitive capabilities required.
 * @param string $cap     Capability being checked.
 * @param int    $user_id User ID.
 * @param array  $args    Additional arguments (post ID, etc.).
 * @return array Filtered capabilities.
 */
function owbn_entity_map_meta_cap($caps, $cap, $user_id, $args)
{
    if (!in_array($cap, ['edit_post', 'delete_post', 'read_post'], true)) return $caps;

    $post_id = !empty($args[0]) ? (int) $args[0] : 0;
    if (!$post_id && !empty($_REQUEST['post'])) $post_id = (int) $_REQUEST['post'];   // phpcs:ignore WordPress.Security.NonceVerification
    if (!$post_id && !empty($_POST['post_ID'])) $post_id = (int) $_POST['post_ID'];   // phpcs:ignore WordPress.Security.NonceVerification

    $post = get_post($post_id);
    if (!$post) return $caps;

    // Only act on registered entity types.
    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return $caps;
    if (!owbn_is_entity_enabled($config['post_type'])) return $caps;

    $user = get_userdata($user_id);
    if (!$user instanceof WP_User) return ['do_not_allow'];

    // Administrators and exec_team always have access.
    if (array_intersect($user->roles, ['administrator', 'exec_team'])) {
        return ['read'];
    }

    // AccessSchema pattern check — use cached roles for performance and consistency.
    $slug_meta_key = $config['slug_meta_key'] ?? '';
    $entity_slug   = $slug_meta_key ? get_post_meta($post_id, $slug_meta_key, true) : '';

    if ($entity_slug) {
        $access_patterns = $config['access_patterns'] ?? [];

        // Check cached roles first (fast, works without live ASC server)
        if (function_exists('owbn_get_cached_user_roles')) {
            $cached_roles = owbn_get_cached_user_roles($user_id, $user->user_email);
            foreach ($access_patterns as $pattern) {
                $resolved = str_replace('{slug}', $entity_slug, $pattern);
                if (in_array($resolved, $cached_roles, true)) {
                    return ['read'];
                }
                // Also check hierarchical (user has a child role)
                if (!empty(preg_grep('#^' . preg_quote($resolved, '#') . '/#', $cached_roles))) {
                    return ['read'];
                }
            }
        }

        // Fallback to direct ASC API call if cached check didn't match
        if (function_exists('accessSchema_client_roles_match_pattern_from_email')) {
            $client_id = defined('ASC_PREFIX') ? strtolower(str_replace('_', '-', ASC_PREFIX)) : 'ccs';
            $email     = $user->user_email;

            foreach ($access_patterns as $pattern) {
                $resolved = str_replace('{slug}', $entity_slug, $pattern);
                if (accessSchema_client_roles_match_pattern_from_email($email, $resolved, $client_id)) {
                    return ['read'];
                }
            }
        }
    }

    // Fallback: check staff_fields for direct user assignment.
    $staff_fields = $config['staff_fields'] ?? [];
    foreach ($staff_fields as $field_key) {
        $field_value = get_post_meta($post_id, $field_key, true);
        $assigned_id = isset($field_value['user']) ? (int) $field_value['user'] : 0;
        if ($assigned_id && $user_id === $assigned_id) {
            return ['read'];
        }
    }

    return ['do_not_allow'];
}

/**
 * Check if a user can edit a specific entity post.
 *
 * Uses the same logic as the map_meta_cap filter but returns a boolean.
 *
 * @param int $user_id WordPress user ID.
 * @param int $post_id WordPress post ID.
 * @return bool
 */
function owbn_user_can_edit_entity(int $user_id, int $post_id): bool
{
    $user = get_userdata($user_id);
    $post = get_post($post_id);
    if (!$user || !$post) return false;

    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return false;
    if (!owbn_is_entity_enabled($config['post_type'])) return false;

    // Administrators and exec_team always have access.
    if (array_intersect($user->roles, ['administrator', 'exec_team'])) {
        return true;
    }

    // AccessSchema pattern check — use cached roles for performance and consistency.
    $slug_meta_key = $config['slug_meta_key'] ?? '';
    $entity_slug   = $slug_meta_key ? get_post_meta($post_id, $slug_meta_key, true) : '';

    if ($entity_slug) {
        $access_patterns = $config['access_patterns'] ?? [];

        // Check cached roles first (fast, works without live ASC server)
        if (function_exists('owbn_get_cached_user_roles')) {
            $cached_roles = owbn_get_cached_user_roles($user_id, $user->user_email);
            foreach ($access_patterns as $pattern) {
                $resolved = str_replace('{slug}', $entity_slug, $pattern);
                if (in_array($resolved, $cached_roles, true)) {
                    return true;
                }
                if (!empty(preg_grep('#^' . preg_quote($resolved, '#') . '/#', $cached_roles))) {
                    return true;
                }
            }
        }

        // Fallback to direct ASC API call
        if (function_exists('accessSchema_client_roles_match_pattern_from_email')) {
            $client_id = defined('ASC_PREFIX') ? strtolower(str_replace('_', '-', ASC_PREFIX)) : 'ccs';
            $email     = $user->user_email;

            foreach ($access_patterns as $pattern) {
                $resolved = str_replace('{slug}', $entity_slug, $pattern);
                if (accessSchema_client_roles_match_pattern_from_email($email, $resolved, $client_id)) {
                    return true;
                }
            }
        }
    }

    // Fallback: check staff_fields for direct user assignment.
    $staff_fields = $config['staff_fields'] ?? [];
    foreach ($staff_fields as $field_key) {
        $field_value = get_post_meta($post_id, $field_key, true);
        $assigned_id = isset($field_value['user']) ? (int) $field_value['user'] : 0;
        if ($assigned_id && (int) $user_id === $assigned_id) {
            return true;
        }
    }

    return false;
}

/**
 * Check if a user can edit restricted entity metadata fields (slug, region, etc.).
 *
 * Only administrators, exec_team, and web_team may modify these fields.
 *
 * @param int|null $user_id Optional user ID. Defaults to current user.
 * @return bool
 */
function owbn_user_can_edit_entity_metadata(int $user_id = null): bool
{
    $user = $user_id ? get_userdata($user_id) : wp_get_current_user();
    if (!$user instanceof WP_User) return false;

    // Admin, exec_team, and web_team can always edit restricted metadata.
    if (array_intersect($user->roles, ['administrator', 'exec_team', 'web_team'])) {
        return true;
    }

    return false;
}

/**
 * Custom permalink filter for entity types using their slug meta key.
 *
 * @param string  $post_link Default permalink.
 * @param WP_Post $post      Post object.
 * @return string Filtered permalink.
 */
function owbn_custom_entity_permalink($post_link, $post)
{
    $config = owbn_get_entity_config($post->post_type);
    if (!$config) return $post_link;
    if (!owbn_is_entity_enabled($config['post_type'])) return $post_link;

    $slug_meta_key = $config['slug_meta_key'] ?? '';
    $url_slug      = $config['url_slug'] ?? '';

    if (!$slug_meta_key || !$url_slug) return $post_link;

    $slug = get_post_meta($post->ID, $slug_meta_key, true);
    $slug = $slug ? sanitize_title($slug) : sanitize_title($post->post_title);

    return home_url("/{$url_slug}/{$slug}/");
}

function owbn_custom_entity_rewrite_rules()
{
    foreach (owbn_get_entity_types() as $post_type => $config) {
        if (!owbn_is_entity_enabled($post_type)) continue;

        $url_slug = $config['url_slug'] ?? '';
        if (!$url_slug) continue;

        add_rewrite_rule(
            "^{$url_slug}/([^/]+)/?$",
            "index.php?post_type={$post_type}&name=\$matches[1]",
            'top'
        );
    }
}

/**
 * Load entity-specific single templates from the plugin.
 *
 * Looks for templates/single-{post_type}.php relative to this file.
 *
 * @param string $template Current template path.
 * @return string Filtered template path.
 */
function owbn_entity_template_include($template)
{
    foreach (owbn_get_entity_types() as $post_type => $config) {
        if (!is_singular($post_type)) continue;
        if (!owbn_is_entity_enabled($post_type)) continue;

        $plugin_template = plugin_dir_path(__FILE__) . "../templates/single-{$post_type}.php";
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }

    return $template;
}

function owbn_add_entity_enctype()
{
    global $post;
    if (!$post) return;

    if (owbn_is_entity_post_type($post->post_type)) {
        echo ' enctype="multipart/form-data"';
    }
}

function owbn_init_entity_types()
{
    foreach (owbn_get_entity_types() as $config) {
        owbn_register_entity_cpt($config);
        owbn_register_entity_meta($config);
    }
}

function owbn_init_entity_meta_boxes()
{
    foreach (owbn_get_entity_types() as $config) {
        owbn_add_entity_meta_box($config);
    }
}

add_action('init', 'owbn_init_entity_types');
add_action('add_meta_boxes', 'owbn_init_entity_meta_boxes');
add_filter('map_meta_cap', 'owbn_entity_map_meta_cap', 10, 4);
add_filter('post_type_link', 'owbn_custom_entity_permalink', 10, 2);
add_action('init', 'owbn_custom_entity_rewrite_rules');
add_filter('template_include', 'owbn_entity_template_include');
add_action('post_edit_form_tag', 'owbn_add_entity_enctype');

// ── Chronicle / Coordinator admin UI tweaks ─────────────────────────────
// Kill the native editor box + Author metabox so our custom tabbed metabox
// (which hosts a wp_editor() for post_content inside the Description tab)
// is the only content-editing surface on the edit page.
add_action('init', function () {
    foreach (['owbn_chronicle', 'owbn_coordinator'] as $pt) {
        remove_post_type_support($pt, 'editor');
        remove_post_type_support($pt, 'author');
    }
}, 20);

add_action('admin_menu', function () {
    foreach (['owbn_chronicle', 'owbn_coordinator'] as $pt) {
        remove_meta_box('authordiv', $pt, 'normal');
        remove_meta_box('authordiv', $pt, 'side');
        remove_meta_box('authordiv', $pt, 'advanced');
    }
});

// Chronicle Name lock: only site admins can rename a chronicle once it has
// been saved. Defence in depth — server-side filter reverts any unauthorized
// title change, client-side JS makes the input visually read-only.
add_filter('wp_insert_post_data', function ($data, $postarr) {
    if (($data['post_type'] ?? '') !== 'owbn_chronicle') {
        return $data;
    }
    if (current_user_can('manage_options')) {
        return $data;
    }
    // Allow new-post creation to set an initial title.
    if (empty($postarr['ID'])) {
        return $data;
    }
    $existing = get_post((int) $postarr['ID']);
    if ($existing && isset($data['post_title']) && $existing->post_title !== $data['post_title']) {
        $data['post_title'] = $existing->post_title;
    }
    return $data;
}, 9, 2);

$owbn_chronicle_title_lock_js = function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'owbn_chronicle') {
        return;
    }
    if (current_user_can('manage_options')) {
        return;
    }
    global $post;
    if (!$post || empty($post->post_title)) {
        return; // new / blank — user must be able to type the initial name
    }
    ?>
    <script>
    (function(){
        var t = document.getElementById('title');
        if (!t) return;
        t.readOnly = true;
        t.style.background = '#f6f7f7';
        t.title = 'Chronicle name can only be changed by a site administrator.';
    })();
    </script>
    <?php
};
add_action('admin_footer-post.php', $owbn_chronicle_title_lock_js);
add_action('admin_footer-post-new.php', $owbn_chronicle_title_lock_js);
