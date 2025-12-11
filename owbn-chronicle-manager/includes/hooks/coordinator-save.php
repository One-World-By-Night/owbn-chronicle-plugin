<?php
/** File: includes/hooks/coordinator-save.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.3.1
 * @author greghacke
 * Function: Coordinator post meta save handler
 */

if (!defined('ABSPATH')) exit;

add_action('save_post_owbn_coordinator', 'owbn_save_coordinator_meta', 10, 2);

/**
 * Save Coordinator meta fields
 */
function owbn_save_coordinator_meta($post_id, $post)
{
    // Verify nonce
    if (!isset($_POST['owbn_coordinator_nonce']) || 
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['owbn_coordinator_nonce'])), 'owbn_coordinator_meta_nonce')) {
        return;
    }

    // Skip autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) return;

    // Fields that can NEVER be changed after initial creation
    $immutable_fields = ['coordinator_slug'];

    // Fields that only admin/exec can modify
    $restricted_fields = [
        'coordinator_slug',
        'coordinator_appointment',
        'coordinator_type',
    ];

    $is_admin = owbn_is_admin_user();

    // Simple text/date fields
    $simple_fields = ['coordinator_title', 'term_start_date', 'web_url', 'coordinator_appointment', 'coordinator_type', 'hosting_chronicle'];
    foreach ($simple_fields as $field) {
        // IMMUTABLE: Never change once set
        if (in_array($field, $immutable_fields, true)) {
            $existing = get_post_meta($post_id, $field, true);
            if (!empty($existing)) {
                continue;
            }
        }

        // RESTRICTED: Skip if field wasn't submitted (was disabled)
        if (in_array($field, $restricted_fields, true) && !$is_admin) {
            if (!isset($_POST[$field])) {
                continue;
            }
        }

        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field(wp_unslash($_POST[$field])));
        }
    }

    // Coordinator slug - immutable after creation
    if (isset($_POST['coordinator_slug'])) {
        $existing_slug = get_post_meta($post_id, 'coordinator_slug', true);
        if (empty($existing_slug)) {
            update_post_meta($post_id, 'coordinator_slug', strtolower(sanitize_text_field(wp_unslash($_POST['coordinator_slug']))));
        }
    }

    // Office description (WYSIWYG)
    if (isset($_POST['office_description'])) {
        update_post_meta($post_id, 'office_description', wp_kses_post(wp_unslash($_POST['office_description'])));
    }

    // Coordinator info (user_info)
    if (isset($_POST['coord_info']) && is_array($_POST['coord_info'])) {
        $cleaned = owbn_sanitize_user_info(wp_unslash($_POST['coord_info']));
        update_post_meta($post_id, 'coord_info', $cleaned);
    }

    // Subcoord list (ast_group)
    if (isset($_POST['subcoord_list']) && is_array($_POST['subcoord_list'])) {
        $meta_fields = [
            'user' => ['type' => 'text'],
            'display_name' => ['type' => 'text'],
            'role' => ['type' => 'text'],
            'actual_email' => ['type' => 'email'],
            'display_email' => ['type' => 'email'],
        ];
        $cleaned = owbn_sanitize_ast_group(wp_unslash($_POST['subcoord_list']), $meta_fields);
        update_post_meta($post_id, 'subcoord_list', $cleaned);
    }

    // Document links
    if (isset($_POST['document_links']) && is_array($_POST['document_links'])) {
        $existing = get_post_meta($post_id, 'document_links', true);
        $cleaned = owbn_sanitize_document_links(wp_unslash($_POST['document_links']), $post_id, 'document_links', $existing);
        update_post_meta($post_id, 'document_links', $cleaned);
    }

    // Email lists
    if (isset($_POST['email_lists']) && is_array($_POST['email_lists'])) {
        $cleaned = owbn_sanitize_email_lists(wp_unslash($_POST['email_lists']));
        update_post_meta($post_id, 'email_lists', $cleaned);
    }

    // Player lists
    if (isset($_POST['player_lists']) && is_array($_POST['player_lists'])) {
        $cleaned = owbn_sanitize_player_lists(wp_unslash($_POST['player_lists']));
        update_post_meta($post_id, 'player_lists', $cleaned);
    }
}