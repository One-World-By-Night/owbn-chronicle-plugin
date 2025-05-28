<?php
// Renders the OWbN Options Page in the WP Admin

function owbn_render_options_page() {
    if (!current_user_can('manage_options')) return;

    // Process Genre form submission
    if (isset($_POST['owbn_genres_nonce']) && wp_verify_nonce($_POST['owbn_genres_nonce'], 'save_owbn_genres')) {
        $raw = stripslashes($_POST['owbn_genres'] ?? '');
        $lines = array_filter(array_map('trim', explode("\n", $raw)));
        update_option('owbn_genre_list', $lines);
        echo '<div class="updated notice is-dismissible"><p>' . esc_html__('Genres updated.', 'owbn-chronicle-manager') . '</p></div>' . "\n";
    }

    // Process Region form submission
    if (isset($_POST['owbn_regions_nonce']) && wp_verify_nonce($_POST['owbn_regions_nonce'], 'save_owbn_regions')) {
        $raw = stripslashes($_POST['owbn_regions'] ?? '');
        $lines = array_filter(array_map('trim', explode("\n", $raw)));
        update_option('owbn_region_list', $lines);
        echo '<div class="updated notice is-dismissible"><p>' . esc_html__('Regions updated.', 'owbn-chronicle-manager') . '</p></div>' . "\n";
    }

    // Fetch current values
    $genres = get_option('owbn_genre_list', []);
    $regions = get_option('owbn_region_list', []);

    $genres_text = implode("\n", $genres);
    $regions_text = implode("\n", $regions);

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Manage OWbN Options', 'owbn-chronicle-manager') . '</h1>';

    // GENRES
    echo '<h2>' . esc_html__('Genre List', 'owbn-chronicle-manager') . '</h2>';
    echo '<form method="post">';
    wp_nonce_field('save_owbn_genres', 'owbn_genres_nonce');

    echo '<table class="form-table">';
    echo '<tr><th scope="row">';
    echo esc_html__('Genres (one per line)', 'owbn-chronicle-manager');
    echo '</th><td>';
    echo '<textarea name="owbn_genres" rows="10" class="large-text code">' . esc_textarea($genres_text) . '</textarea>';
    echo '<p class="description">' . esc_html__('Used as available genre entries for Chronicles.', 'owbn-chronicle-manager') . '</p>';
    echo '</td></tr></table>';

    submit_button(__('Save Genres', 'owbn-chronicle-manager'));
    echo '</form>';

    // REGIONS
    echo '<h2>' . esc_html__('Region List', 'owbn-chronicle-manager') . '</h2>';
    echo '<form method="post">';
    wp_nonce_field('save_owbn_regions', 'owbn_regions_nonce');

    echo '<table class="form-table">';
    echo '<tr><th scope="row">';
    echo esc_html__('Regions (one per line)', 'owbn-chronicle-manager');
    echo '</th><td>';
    echo '<textarea name="owbn_regions" rows="10" class="large-text code">' . esc_textarea($regions_text) . '</textarea>';
    echo '<p class="description">' . esc_html__('Used to populate the Region dropdown for Chronicles.', 'owbn-chronicle-manager') . '</p>';
    echo '</td></tr></table>';

    submit_button(__('Save Regions', 'owbn-chronicle-manager'));
    echo '</form></div>';
}