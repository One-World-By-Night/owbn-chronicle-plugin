<?php
/** File: includes/render/render-user-fields.php
 * Text Domain: owbn-chronicle-manager
 * @version 3.0.0
 * @author greghacke
 * Function: User info and AST/Subcoord group field rendering
 */

if (!defined('ABSPATH')) exit;

// Render the user info fields for entity post types
function owbn_render_user_info($key, $value, $meta)
{
    // Check if this field has visibility rules (e.g., cm_info is hidden for satellite chronicles)
    $post_type = get_post_type();
    $config = owbn_get_entity_config($post_type);
    $has_visibility_rule = false;
    if ($config) {
        foreach ($config['exclusive_fields'] ?? [] as $rule) {
            if (in_array($key, $rule['clear'] ?? [], true)) {
                $has_visibility_rule = true;
                break;
            }
        }
    }

    if ($has_visibility_rule) {
        echo "<div class=\"owbn-cm-info-container\">\n";
        echo "<div id=\"owbn-cm-info-wrapper\">\n";
    }

    // Ensure value is an array
    $value = is_array($value) ? $value : [];

    $user_id = $value['user'] ?? '';
    $display_name = $value['display_name'] ?? '';
    $actual_email = $value['actual_email'] ?? '';
    $display_email = $value['display_email'] ?? $actual_email;

    $users = get_users(['orderby' => 'display_name', 'order' => 'ASC', 'fields' => ['ID', 'display_name']]);

    // Check if stored user_id matches any user or special value
    $user_exists = false;
    if ($user_id === '' || $user_id === '__new__') {
        $user_exists = true;
    } else {
        foreach ($users as $user) {
            if ((string)$user_id === (string)$user->ID) {
                $user_exists = true;
                break;
            }
        }
    }

    // Row 1: User + Display Name
    echo "<div class=\"owbn-user-info-row\">\n";

    // User dropdown
    echo "<div class=\"owbn-user-info-field\">\n";
    echo "<label>" . esc_html__('User', 'owbn-chronicle-manager') . "<br>\n";
    echo "<select name=\"" . esc_attr($key) . "[user]\" class=\"owbn-select2 single\">\n";
    echo "<option value=\"\">" . esc_html__('— Select —', 'owbn-chronicle-manager') . "</option>\n";
    echo "<option value=\"__new__\" " . selected($user_id, '__new__', false) . ">" . esc_html__('[New User]', 'owbn-chronicle-manager') . "</option>\n";

    // If stored value doesn't match any user, add it as an option to preserve it
    if (!$user_exists && !empty($user_id)) {
        $preserved_label = sprintf(__('[User ID: %s - Not Found]', 'owbn-chronicle-manager'), $user_id);
        echo "<option value=\"" . esc_attr($user_id) . "\" selected>" . esc_html($preserved_label) . "</option>\n";
    }

    foreach ($users as $user) {
        // Cast both to string for reliable comparison
        $is_selected = ((string)$user_id === (string)$user->ID);
        echo "<option value=\"" . esc_attr($user->ID) . "\" " . selected($is_selected, true, false) . ">" . esc_html($user->display_name) . "</option>\n";
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

    if ($has_visibility_rule) {
        echo "</div>\n"; // #owbn-cm-info-wrapper

        echo "<div id=\"owbn-cm-info-message\" style=\"display:none; margin-top: 10px;\">\n";
        echo "<em>" . esc_html__('Satellite Chronicles are represented on council by their parent chronicle.', 'owbn-chronicle-manager') . "</em>\n";
        echo "</div>\n";
        echo "</div>\n"; // .owbn-cm-info-container
    }
}

// Render the AST/Subcoord group fields for Chronicle and Coordinator post types
function owbn_render_ast_group($field_key, $entries, $meta, $context = 'ast_list')
{
    $prefix = ($context === 'subcoord_list') ? 'subcoord' : 'ast';
    $entries = is_array($entries) ? $entries : [];

    $users = get_users(['orderby' => 'display_name', 'order' => 'ASC']);

    echo '<div id="' . esc_attr($prefix) . '-group-wrapper" class="owbn-' . esc_attr($prefix) . '-wrapper">' . "\n";

    foreach ($entries as $index => $entry) {
        render_ast_subcoord_block($prefix, $field_key, $index, $entry, $users);
    }

    // Template for JS clone
    echo '<div class="owbn-' . esc_attr($prefix) . '-block owbn-' . esc_attr($prefix) . '-template" style="display:none;">' . "\n";
    render_ast_subcoord_block($prefix, $field_key, '__INDEX__', [], $users, true);
    echo '</div>' . "\n";

    echo '<button type="button" class="button owbn-add-' . esc_attr($prefix) . '">Add</button>' . "\n";
    echo '</div>' . "\n";
}

function render_ast_subcoord_block($prefix, $field_key, $index, $entry, $users, $is_template = false)
{
    // Ensure entry is an array
    $entry = is_array($entry) ? $entry : [];

    $user_id = $entry['user'] ?? '';
    $display_name = $entry['display_name'] ?? '';
    $role = $entry['role'] ?? '';
    $actual_email = $entry['actual_email'] ?? '';
    $display_email = $entry['display_email'] ?? '';

    // Check if stored user_id matches any user or special value
    $user_exists = false;
    if ($user_id === '' || $user_id === '__new__') {
        $user_exists = true;
    } else {
        foreach ($users as $user) {
            if ((string)$user_id === (string)$user->ID) {
                $user_exists = true;
                break;
            }
        }
    }

?>
    <div class="owbn-<?php echo esc_attr($prefix); ?>-block">
        <div class="owbn-user-row">
            <div class="owbn-user-field owbn-user-field--wide">
                <label><?php esc_html_e('User', 'owbn-chronicle-manager'); ?></label>
                <select name="<?php echo esc_attr("{$field_key}[{$index}][user]"); ?>" class="owbn-select2 single">
                    <option value=""><?php esc_html_e('— Select —', 'owbn-chronicle-manager'); ?></option>
                    <option value="__new__" <?php selected($user_id, '__new__'); ?>><?php esc_html_e('[New User]', 'owbn-chronicle-manager'); ?></option>
                    <?php if (!$user_exists && !empty($user_id)): ?>
                        <option value="<?php echo esc_attr($user_id); ?>" selected>
                            <?php echo esc_html(sprintf(__('[User ID: %s - Not Found]', 'owbn-chronicle-manager'), $user_id)); ?>
                        </option>
                    <?php endif; ?>
                    <?php foreach ($users as $user): ?>
                        <?php $is_selected = ((string)$user_id === (string)$user->ID); ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($is_selected, true); ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="owbn-user-field owbn-user-field--action">
                <button type="button" class="button button-small owbn-remove-<?php echo esc_attr($prefix); ?>">Remove</button>
            </div>
        </div>
        <div class="owbn-user-row">
            <div class="owbn-user-field">
                <label><?php esc_html_e('Display Name', 'owbn-chronicle-manager'); ?></label>
                <input type="text" name="<?php echo esc_attr("{$field_key}[{$index}][display_name]"); ?>" value="<?php echo esc_attr($display_name); ?>" class="regular-text">
            </div>
            <div class="owbn-user-field">
                <label><?php esc_html_e('Role', 'owbn-chronicle-manager'); ?></label>
                <input type="text" name="<?php echo esc_attr("{$field_key}[{$index}][role]"); ?>" value="<?php echo esc_attr($role); ?>" class="regular-text">
            </div>
            <div class="owbn-user-field">
                <label><?php esc_html_e('Actual Email', 'owbn-chronicle-manager'); ?></label>
                <input type="email" name="<?php echo esc_attr("{$field_key}[{$index}][actual_email]"); ?>" value="<?php echo esc_attr($actual_email); ?>" class="regular-text">
            </div>
            <div class="owbn-user-field">
                <label><?php esc_html_e('Display Email', 'owbn-chronicle-manager'); ?></label>
                <input type="email" name="<?php echo esc_attr("{$field_key}[{$index}][display_email]"); ?>" value="<?php echo esc_attr($display_email); ?>" class="regular-text">
            </div>
        </div>
    </div>
<?php
}
