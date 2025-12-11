<?php
/** File: includes/hooks/coordinator-validate.php
 * Text Domain: owbn-chronicle-manager
 * @version 2.3.0
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

    // Validate coordinator_slug format
    if (isset($postarr['coordinator_slug'])) {
        $slug = strtolower(sanitize_text_field($postarr['coordinator_slug']));
        if (!empty($slug) && !preg_match('/^[a-z0-9-]{2,32}$/', $slug)) {
            $errors[] = 'coordinator_slug';
        }
    }

    // Validate required coordinator info
    if (isset($postarr['coord_info']) && is_array($postarr['coord_info'])) {
        $info = $postarr['coord_info'];
        if (empty(trim($info['display_name'] ?? '')) || empty(trim($info['display_email'] ?? ''))) {
            $errors[] = 'coord_info';
        }
    }

    return $errors;
}