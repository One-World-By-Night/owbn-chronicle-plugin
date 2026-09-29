<?php
/**
 * OWBN Dues status box on the chronicle edit screen. Reads a chronicle's paid-through year from
 * council.owbn.net's owbn-dues plugin over a key-protected REST call, cached briefly.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_owbn_chronicle', 'owbn_dues_status_add_meta_box' );

/**
 * Registers the status box on the chronicle edit screen's side column.
 */
function owbn_dues_status_add_meta_box(): void {
	$key = trim( (string) get_option( 'owbn_dues_remote_api_key', '' ) );
	if ( '' === $key ) {
		return;
	}
	add_meta_box(
		'owbn_dues_status',
		__( 'OWBN Dues', 'owbn-chronicle-manager' ),
		'owbn_dues_status_render_meta_box',
		'owbn_chronicle',
		'side',
		'default'
	);
}

/**
 * Fetches and caches a chronicle's dues status from council.owbn.net.
 *
 * @return array|null Status array (paid_through, billing_year, current, last_paid_at), or null on failure.
 */
function owbn_dues_status_fetch( string $slug ) {
	$cache_key = 'owbn_dues_status_' . $slug;
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached;
	}

	$key = trim( (string) get_option( 'owbn_dues_remote_api_key', '' ) );
	if ( '' === $key ) {
		return null;
	}

	$response = wp_remote_get( 'https://council.owbn.net/wp-json/owbn-dues/v1/status?slug=' . rawurlencode( $slug ), array(
		'timeout' => 8,
		'headers' => array( 'x-api-key' => $key ),
	) );

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		return null;
	}

	set_transient( $cache_key, $data, 15 * MINUTE_IN_SECONDS );
	return $data;
}

/**
 * Renders the status box: paid-through year and last payment date, or a link to check manually
 * when the status can't be reached.
 */
function owbn_dues_status_render_meta_box( WP_Post $post ): void {
	$slug = get_post_meta( $post->ID, 'chronicle_slug', true );
	if ( '' === trim( (string) $slug ) ) {
		echo '<p>' . esc_html__( 'Save the chronicle slug first.', 'owbn-chronicle-manager' ) . '</p>';
		return;
	}

	$status = owbn_dues_status_fetch( (string) $slug );
	if ( null === $status ) {
		echo '<p>' . esc_html__( 'Dues status is unavailable right now.', 'owbn-chronicle-manager' ) . '</p>';
		return;
	}

	if ( $status['paid_through'] ) {
		printf(
			'<p><strong>%s</strong> %s</p>',
			esc_html__( 'Paid through:', 'owbn-chronicle-manager' ),
			esc_html( (string) $status['paid_through'] )
		);
	} else {
		echo '<p><strong style="color:#d63638;">' . esc_html__( 'No dues paid on record.', 'owbn-chronicle-manager' ) . '</strong></p>';
	}

	if ( ! $status['current'] ) {
		printf(
			'<p style="color:#d63638;">%s</p>',
			esc_html( sprintf( __( 'Not current for %d.', 'owbn-chronicle-manager' ), (int) $status['billing_year'] ) )
		);
	}

	if ( $status['last_paid_at'] ) {
		printf(
			'<p>%s %s</p>',
			esc_html__( 'Last payment:', 'owbn-chronicle-manager' ),
			esc_html( mysql2date( get_option( 'date_format' ), (string) $status['last_paid_at'] ) )
		);
	}
}
