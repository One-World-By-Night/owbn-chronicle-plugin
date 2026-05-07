<?php
if (!defined('ABSPATH')) exit;

/**
 * Compute the locked CM email for a chronicle.
 *
 * Format: {slug}-cm@owbn.net. For satellite chronicles, uses the parent
 * chronicle's slug since satellites inherit the parent's CM.
 *
 * Returns '' if the chronicle (or required parent) has no slug yet.
 */
function owbn_chronicle_cm_email($post_id)
{
    $post_id = (int) $post_id;
    if ($post_id <= 0) return '';
    if (get_post_type($post_id) !== 'owbn_chronicle') return '';

    $is_satellite = (string) get_post_meta($post_id, 'chronicle_satellite', true) === '1';

    if ($is_satellite) {
        $parent_id = (int) get_post_meta($post_id, 'chronicle_parent', true);
        if ($parent_id <= 0) return '';
        $slug = (string) get_post_meta($parent_id, 'chronicle_slug', true);
    } else {
        $slug = (string) get_post_meta($post_id, 'chronicle_slug', true);
    }

    $slug = strtolower(trim($slug));
    if ($slug === '') return '';

    return $slug . '-cm@owbn.net';
}
