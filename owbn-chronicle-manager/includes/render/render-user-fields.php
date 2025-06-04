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
    $actual_email = $value['actual_email'] ?? '';
    $display_email = $value['display_email'] ?? $actual_email;

    $users = get_users(['fields' => ['ID', 'display_name']]);

    // Row 1: User + Display Name
    echo "<div class=\"owbn-user-info-row\">\n";

    // User dropdown
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . esc_html__('User', 'owbn-chronicle-manager') . "<br>\n";
    echo "<select name=\"" . esc_attr($key) . "[user]\" class=\"owbn-select2\">\n";
    echo "<option value=\"\">" . esc_html__('— Select —', 'owbn-chronicle-manager') . "</option>\n";
    echo "<option value=\"__new__\" " . selected($user_id, '__new__', false) . ">" . esc_html__('[New User]', 'owbn-chronicle-manager') . "</option>\n";

    foreach ($users as $user) {
        echo "<option value=\"" . esc_attr($user->ID) . "\" " . selected($user_id, $user->ID, false) . ">" . esc_html($user->display_name) . "</option>\n";
    }
    echo "</select>\n";
    echo "</label>\n";
    echo "</div>\n";

    // Display Name
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . esc_html__('Display Name', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"text\" name=\"" . esc_attr($key) . "[display_name]\" value=\"" . esc_attr($display_name) . "\" class=\"regular-text\">\n";
    echo "</label>\n";
    echo "</div>\n";

    echo "</div>\n"; // End Row 1

    // Row 2: Emails
    echo "<div class=\"owbn-user-info-row\">\n";

    // Actual Email
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . esc_html__('Actual Email', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"email\" name=\"" . esc_attr($key) . "[actual_email]\" value=\"" . esc_attr($actual_email) . "\" class=\"regular-text\">\n";
    echo "</label>\n</div>\n";

    // Display Email
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . esc_html__('Display Email', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"email\" name=\"" . esc_attr($key) . "[display_email]\" value=\"" . esc_attr($display_email) . "\" class=\"regular-text\">\n";
    echo "</label>\n</div>\n";

    echo "</div>\n"; // End Row 2

    if ($is_cm) {
        echo "</div>\n"; // #owbn-cm-info-wrapper

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

        // Row 1: User
        echo "<div class=\"owbn-user-info-row\">\n";
        echo "<div class=\"owbn-user-info-field\">\n";
        echo "<label>" . esc_html__('User', 'owbn-chronicle-manager') . "<br>\n";
        echo "<select name=\"ast_list[" . esc_attr($index) . "][user]\" class=\"owbn-select2 single\">\n";
        echo "<option value=\"\">" . esc_html__('— Select —', 'owbn-chronicle-manager') . "</option>\n";
        echo "<option value=\"__new__\" " . selected($entry['user'] ?? '', '__new__', false) . ">" . esc_html__('[New User]', 'owbn-chronicle-manager') . "</option>\n";
        foreach ($users as $user) {
            echo "<option value=\"" . esc_attr($user->ID) . "\" " . selected($entry['user'] ?? '', $user->ID, false) . ">" . esc_html($user->display_name) . "</option>\n";
        }
        echo "</select>\n</label>\n</div>\n";
        echo "</div>\n";

        // Row 2: Display Name + Role
        echo "<div class=\"owbn-user-info-row\">\n";
        echo "<div class=\"owbn-user-info-field\">\n";
        echo "<label>" . esc_html__('Display Name', 'owbn-chronicle-manager') . "<br>\n";
        echo "<input type=\"text\" name=\"ast_list[" . esc_attr($index) . "][display_name]\" value=\"" . esc_attr($entry['display_name'] ?? '') . "\" class=\"regular-text\">\n";
        echo "</label>\n</div>\n";
        echo "<div class=\"owbn-user-info-field\">\n";
        echo "<label>" . esc_html__('Role', 'owbn-chronicle-manager') . "<br>\n";
        echo "<input type=\"text\" name=\"ast_list[" . esc_attr($index) . "][role]\" value=\"" . esc_attr($entry['role'] ?? '') . "\" class=\"regular-text\">\n";
        echo "</label>\n</div>\n";
        echo "</div>\n";

        // Row 3: Emails
        echo "<div class=\"owbn-user-info-row\">\n";
        echo "<div class=\"owbn-user-info-field\">\n";
        echo "<label>" . esc_html__('Actual Email', 'owbn-chronicle-manager') . "<br>\n";
        echo "<input type=\"email\" name=\"ast_list[" . esc_attr($index) . "][actual_email]\" value=\"" . esc_attr($entry['actual_email'] ?? '') . "\" class=\"regular-text\">\n";
        echo "</label>\n</div>\n";
        echo "<div class=\"owbn-user-info-field\">\n";
        echo "<label>" . esc_html__('Display Email', 'owbn-chronicle-manager') . "<br>\n";
        echo "<input type=\"email\" name=\"ast_list[" . esc_attr($index) . "][display_email]\" value=\"" . esc_attr($entry['display_email'] ?? '') . "\" class=\"regular-text\">\n";
        echo "</label>\n</div>\n";
        echo "</div>\n";

        echo "<button type=\"button\" class=\"button owbn-remove-ast\">" . esc_html__('Remove AST', 'owbn-chronicle-manager') . "</button>\n";
        echo "</div>\n"; // .owbn-ast-block
    }

    // Template for JS clone
    echo "<div class=\"owbn-ast-block owbn-ast-template\" style=\"display:none;\">\n";

    // Row 1: User
    echo "<div class=\"owbn-user-info-row\">\n";
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . esc_html__('User', 'owbn-chronicle-manager') . "<br>\n";
    echo "<select name=\"ast_list[__INDEX__][user]\" class=\"owbn-select2 single\">\n";
    echo "<option value=\"\">" . esc_html__('— Select —', 'owbn-chronicle-manager') . "</option>\n";
    echo "<option value=\"__new__\">[New User]</option>\n";
    foreach ($users as $user) {
        echo "<option value=\"" . esc_attr($user->ID) . "\">" . esc_html($user->display_name) . "</option>\n";
    }
    echo "</select>\n</label>\n</div>\n";
    echo "</div>\n";

    // Row 2: Display Name + Role
    echo "<div class=\"owbn-user-info-row\">\n";
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . esc_html__('Display Name', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"text\" name=\"ast_list[__INDEX__][display_name]\" class=\"regular-text\">\n";
    echo "</label>\n</div>\n";
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . esc_html__('Role', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"text\" name=\"ast_list[__INDEX__][role]\" class=\"regular-text\">\n";
    echo "</label>\n</div>\n";
    echo "</div>\n";

    // Row 3: Emails
    echo "<div class=\"owbn-user-info-row\">\n";
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . esc_html__('Actual Email', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"email\" name=\"ast_list[__INDEX__][actual_email]\" class=\"regular-text\">\n";
    echo "</label>\n</div>\n";
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . esc_html__('Display Email', 'owbn-chronicle-manager') . "<br>\n";
    echo "<input type=\"email\" name=\"ast_list[__INDEX__][display_email]\" class=\"regular-text\">\n";
    echo "</label>\n</div>\n";
    echo "</div>\n";

    echo "<button type=\"button\" class=\"button owbn-remove-ast\">" . esc_html__('Remove AST', 'owbn-chronicle-manager') . "</button>\n";
    echo "</div>\n"; // end template

    echo "<button type=\"button\" class=\"button button-primary owbn-add-ast\">" . esc_html__('Add AST', 'owbn-chronicle-manager') . "</button>\n";
    echo "</div>\n"; // .owbn-repeatable-group
}