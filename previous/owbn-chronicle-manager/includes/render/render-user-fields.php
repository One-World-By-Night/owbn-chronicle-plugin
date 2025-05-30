<?php
if (!defined('ABSPATH')) exit;

// Render the user info fields for the Chronicle custom post typ
function owbn_render_user_info($key, $value, $meta) {
    $is_cm = ($key === 'cm_info');

    if ($is_cm) {
        echo "<div class=\"owbn-cm-info-container\">\n";
        echo "<div id=\"owbn-cm-info-wrapper\">\n";
    }

    $user_id = $value['user'] ?? '';
    $display_name = $value['display_name'] ?? '';
    $email = $value['email'] ?? '';

    $users = get_users(['fields' => ['ID', 'display_name']]);

    echo "<div class=\"owbn-user-info-row\">\n";

    // User dropdown
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . __('User', 'owbn-chronicle-manager') . "<br>\n";
    echo "<select name=\"" . esc_attr($key) . "[user]\" class=\"owbn-select2\">\n";
    echo "<option value=\"\">" . __('— Select —', 'owbn-chronicle-manager') . "</option>\n";
    foreach ($users as $user) {
        echo "<option value=\"" . esc_attr($user->ID) . "\" " . selected($user_id, $user->ID, false) . ">" . esc_html($user->display_name) . "</option>\n";
    }
    echo "</select>\n";
    echo "</label>\n";
    echo "</div>\n";

    // Display Name
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . __('Display Name', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"text\" name=\"" . esc_attr($key) . "[display_name]\" value=\"" . esc_attr($display_name) . "\" class=\"regular-text\">\n";
    echo "</label>\n";
    echo "</div>\n";

    // Email
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . __('Display Email', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"email\" name=\"" . esc_attr($key) . "[email]\" value=\"" . esc_attr($email) . "\" class=\"regular-text\">\n";
    echo "</label>\n";
    echo "</div>\n";

    echo "</div>\n"; // .owbn-user-info-row

    if ($is_cm) {
        echo "</div>\n"; // #owbn-cm-info-wrapper

        // Satellite note toggle target
        echo "<div id=\"owbn-cm-info-message\" style=\"display:none; margin-top: 10px;\">\n";
        echo "<em>" . esc_html__('Satellite Chronicles are represented on council by their parent chronicle.', 'owbn-chronicle-manager') . "</em>\n";
        echo "</div>\n";
        echo "</div>\n"; // .owbn-cm-info-container
    }
}

// Render the AST group fields for the Chronicle custom post type using user_info fields
function owbn_render_ast_group($key, $value, $meta) {
    $fields = $meta['fields'] ?? [];
    $value = is_array($value) ? $value : [];
    $users = get_users(['fields' => ['ID', 'display_name']]);

    echo "<div class=\"owbn-repeatable-group\" id=\"ast-group-wrapper\">\n";

    foreach ($value as $index => $entry) {
        echo "<div class=\"owbn-ast-block\">\n";

        // Row 1: User Select
        echo "<div class=\"owbn-user-info-row\">\n";
        echo "<div class=\"owbn-user-info-field\">\n";
        echo "<label>" . __('User', 'owbn-chronicle-manager') . "<br>\n";
        echo "<select name=\"ast_list[{$index}][user]\" class=\"owbn-select2 single\">\n";
        echo "<option value=\"\">" . __('— Select —', 'owbn-chronicle-manager') . "</option>\n";
        foreach ($users as $user) {
            echo "<option value=\"" . esc_attr($user->ID) . "\" " . selected($entry['user'] ?? '', $user->ID, false) . ">" . esc_html($user->display_name) . "</option>\n";
        }
        echo "</select>\n</label>\n</div>\n";
        echo "</div>\n";

        // Row 2: Display Name, Email, Role
        echo "<div class=\"owbn-user-info-row\">\n";

        echo "<div class=\"owbn-user-info-field\">\n";
        echo "<label>" . __('Display Name', 'owbn-chronicle-manager') . "<br>\n";
        echo "<input type=\"text\" name=\"ast_list[{$index}][display_name]\" value=\"" . esc_attr($entry['display_name'] ?? '') . "\" class=\"regular-text\">\n";
        echo "</label>\n</div>\n";

        echo "<div class=\"owbn-user-info-field\">\n";
        echo "<label>" . __('Email', 'owbn-chronicle-manager') . "<br>\n";
        echo "<input type=\"email\" name=\"ast_list[{$index}][email]\" value=\"" . esc_attr($entry['email'] ?? '') . "\" class=\"regular-text\">\n";
        echo "</label>\n</div>\n";

        echo "<div class=\"owbn-user-info-field\">\n";
        echo "<label>" . __('Role', 'owbn-chronicle-manager') . "<br>\n";
        echo "<input type=\"text\" name=\"ast_list[{$index}][role]\" value=\"" . esc_attr($entry['role'] ?? '') . "\" class=\"regular-text\">\n";
        echo "</label>\n</div>\n";

        echo "</div>\n"; // Row 2

        echo "<button type=\"button\" class=\"button owbn-remove-ast\">" . __('Remove AST', 'owbn-chronicle-manager') . "</button>\n";
        echo "</div>\n"; // end .owbn-ast-block
    }

    // Template block for JS clone
    echo "<div class=\"owbn-ast-block owbn-ast-template\" style=\"display:none;\">\n";

    // Row 1: User
    echo "<div class=\"owbn-user-info-row\">\n";
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . __('User', 'owbn-chronicle-manager') . "<br>\n";
    echo "<select name=\"ast_list[__INDEX__][user]\" class=\"owbn-select2 single\">\n";
    echo "<option value=\"\">" . __('— Select —', 'owbn-chronicle-manager') . "</option>\n";
    foreach ($users as $user) {
        echo "<option value=\"" . esc_attr($user->ID) . "\">" . esc_html($user->display_name) . "</option>\n";
    }
    echo "</select>\n</label>\n</div>\n";
    echo "</div>\n";

    // Row 2: Display Name, Email, Role
    echo "<div class=\"owbn-user-info-row\">\n";

    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . __('Display Name', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"text\" name=\"ast_list[__INDEX__][display_name]\" class=\"regular-text\">\n";
    echo "</label>\n</div>\n";

    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . __('Email', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"email\" name=\"ast_list[__INDEX__][email]\" class=\"regular-text\">\n";
    echo "</label>\n</div>\n";

    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . __('Role', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"text\" name=\"ast_list[__INDEX__][role]\" class=\"regular-text\">\n";
    echo "</label>\n</div>\n";

    echo "</div>\n";

    echo "<button type=\"button\" class=\"button owbn-remove-ast\">" . __('Remove AST', 'owbn-chronicle-manager') . "</button>\n";
    echo "</div>\n"; // template

    echo "<button type=\"button\" class=\"button button-primary owbn-add-ast\">" . __('Add AST', 'owbn-chronicle-manager') . "</button>\n";
    echo "</div>\n"; // .owbn-repeatable-group
}
