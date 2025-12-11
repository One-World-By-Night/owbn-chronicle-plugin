<?php
/** File: includes/hooks/coordinator-validate.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.3.1
 * @author greghacke
 * Function: Coordinator validation functions
 */

if (!defined('ABSPATH')) exit;

/**
 * Validate Coordinator submission fields
 */
function owbn_validate_coordinator_submission($postarr)
{
    $errors = [];
    $post_id = $postarr['ID'] ?? 0;

    // Fields that are immutable once set - skip validation if DB has value
    $immutable_fields = ['coordinator_slug'];

    // Validate coordinator_slug format
    if (isset($postarr['coordinator_slug'])) {
        // Check if immutable and already has value
        if (in_array('coordinator_slug', $immutable_fields, true) && $post_id) {
            $existing = get_post_meta($post_id, 'coordinator_slug', true);
            if (!empty($existing)) {
                // Field has value in DB, skip validation
                goto skip_slug_validation;
            }
        }

        $slug = strtolower(sanitize_text_field($postarr['coordinator_slug']));
        if (!empty($slug) && !preg_match('/^[a-z0-9-]{2,32}$/', $slug)) {
            $errors[] = 'coordinator_slug';
        }
    }
    skip_slug_validation:

    // Validate required coordinator info
    if (isset($postarr['coord_info']) && is_array($postarr['coord_info'])) {
        $info = $postarr['coord_info'];

        // If no data submitted, check DB
        if (empty($info) && $post_id) {
            $info = get_post_meta($post_id, 'coord_info', true);
            $info = is_array($info) ? $info : [];
        }

        if (empty(trim($info['display_name'] ?? '')) || empty(trim($info['display_email'] ?? ''))) {
            // Check if value exists in DB (for disabled fields)
            if ($post_id) {
                $existing = get_post_meta($post_id, 'coord_info', true);
                if (is_array($existing) && !empty($existing['display_name']) && !empty($existing['display_email'])) {
                    goto skip_coord_info_validation;
                }
            }
            $errors[] = 'coord_info';
        }
    }
    skip_coord_info_validation:

    return $errors;
}