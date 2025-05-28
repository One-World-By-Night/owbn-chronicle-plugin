<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function owbn_activate_plugin() {
    // Register post type and rewrite rules so flush_rewrite_rules works
    owbn_register_chronicle_cpt();
    owbn_custom_chronicle_rewrite_rules();
    flush_rewrite_rules();

    // Set default genres if not already set
    if (!get_option('owbn_genre_list')) {
        update_option('owbn_genre_list', [
            'Vampire - Anarch',
            'Vampire - Camarilla',
            'Vampire - Sabbat',
            'Vampire - Giovanni',
            'Vampire - Clan Specific',
            'Changeling',
            'Changing Breeds',
            'Demon',
            'Hunter',
            'Kuei-Jin',
            'Mage',
            'Other',
            'Wraith'
        ]);
    }
}

///// Closing Content /////
// Flush permalinks on activation/deactivation
register_activation_hook(__FILE__, 'owbn_activate_plugin');
register_deactivation_hook(__FILE__, 'owbn_deactivate_plugin');

function owbn_deactivate_plugin() {
    flush_rewrite_rules();
}