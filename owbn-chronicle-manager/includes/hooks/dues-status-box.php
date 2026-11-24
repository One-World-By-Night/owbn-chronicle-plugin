<?php
/**
 * OWBN Dues status box on the chronicle edit screen. Reads a chronicle's paid-through year and any payment
 * waiting for finance to confirm from council.owbn.net's owbn-dues plugin over a key-protected REST call,
 * cached briefly.
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
 * @return array|null Status array (paid_through, billing_year, current, last_paid_at, pending_years, pending_since, years), or null on failure.
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

	set_transient( $cache_key, $data, owbn_dues_status_ttl( $data ) );
	return $data;
}

/**
 * The billing year and the two after it, each with its status, from a fetched status. Empty when council does not send them.
 *
 * @return array<int,array{year:int,status:string}>
 */
function owbn_dues_status_years( array $status ): array {
	$allowed = array( 'paid', 'waived', 'pending', 'partial', 'unpaid' );
	$out     = array();
	foreach ( (array) ( $status['years'] ?? array() ) as $row ) {
		$year = (int) ( is_array( $row ) ? ( $row['year'] ?? 0 ) : 0 );
		$st   = is_array( $row ) ? (string) ( $row['status'] ?? '' ) : '';
		if ( $year > 0 && in_array( $st, $allowed, true ) ) {
			$out[] = array( 'year' => $year, 'status' => $st );
		}
	}
	return $out;
}

/**
 * Label and colour for one year's status in the list: unpaid is red for the billing year and grey for a later year.
 *
 * @return array{0:string,1:string} Label and colour.
 */
function owbn_dues_status_year_label( string $status, bool $is_billing_year ): array {
	switch ( $status ) {
		case 'paid':
			return array( __( 'Paid', 'owbn-chronicle-manager' ), '#00a32a' );
		case 'waived':
			return array( __( 'Waived', 'owbn-chronicle-manager' ), '#00a32a' );
		case 'pending':
			return array( __( 'Payment pending', 'owbn-chronicle-manager' ), '#8a5a00' );
		case 'partial':
			return array( __( 'Partly paid', 'owbn-chronicle-manager' ), $is_billing_year ? '#d63638' : '#50575e' );
		default:
			return $is_billing_year
				? array( __( 'Unpaid', 'owbn-chronicle-manager' ), '#d63638' )
				: array( __( 'Not paid yet', 'owbn-chronicle-manager' ), '#50575e' );
	}
}

/**
 * How long a fetched status is kept: a minute while any year shown is unpaid or pending, so a chronicle that has
 * just paid sees it on its next visit; fifteen minutes once every year shown is paid or waived and nothing is waiting.
 */
function owbn_dues_status_ttl( array $status ): int {
	$settled = ! empty( $status['current'] ) && empty( $status['pending_years'] );
	foreach ( owbn_dues_status_years( $status ) as $row ) {
		if ( ! in_array( $row['status'], array( 'paid', 'waived' ), true ) ) {
			$settled = false;
		}
	}
	return $settled ? 15 * MINUTE_IN_SECONDS : MINUTE_IN_SECONDS;
}

/**
 * Renders the status box: paid-through year, a payment that arrived and waits for confirmation, the status of the
 * billing year and the two after it, and the last payment date, or a note when the status can't be reached.
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

	$pending = array_map( 'intval', (array) ( $status['pending_years'] ?? array() ) );

	if ( $status['paid_through'] ) {
		printf(
			'<p><strong>%s</strong> %s</p>',
			esc_html__( 'Paid through:', 'owbn-chronicle-manager' ),
			esc_html( (string) $status['paid_through'] )
		);
	} elseif ( ! $pending ) {
		echo '<p><strong style="color:#d63638;">' . esc_html__( 'No dues paid on record.', 'owbn-chronicle-manager' ) . '</strong></p>';
	}

	if ( $pending ) {
		echo '<div style="background:#fff8e5;border-left:4px solid #dba617;padding:6px 10px;margin:8px 0;">';
		echo '<p style="margin:0 0 4px;"><strong style="color:#8a5a00;">' . esc_html__( 'Payment received, pending confirmation', 'owbn-chronicle-manager' ) . '</strong></p>';
		printf(
			'<p style="margin:0 0 4px;">%s %s</p>',
			esc_html( _n( 'Year:', 'Years:', count( $pending ), 'owbn-chronicle-manager' ) ),
			esc_html( implode( ', ', $pending ) )
		);
		if ( ! empty( $status['pending_since'] ) ) {
			printf(
				'<p style="margin:0 0 4px;">%s %s</p>',
				esc_html__( 'Received:', 'owbn-chronicle-manager' ),
				esc_html( mysql2date( get_option( 'date_format' ), (string) $status['pending_since'] ) )
			);
		}
		echo '<p style="margin:0;">' . esc_html__( 'OWBN finance confirms it, and then this shows Paid through.', 'owbn-chronicle-manager' ) . '</p>';
		echo '</div>';
	}

	if ( ! $status['current'] && ! in_array( (int) $status['billing_year'], $pending, true ) ) {
		printf(
			'<p style="color:#d63638;">%s</p>',
			esc_html( sprintf( __( 'Not current for %d.', 'owbn-chronicle-manager' ), (int) $status['billing_year'] ) )
		);
	}

	$years = owbn_dues_status_years( $status );
	if ( $years ) {
		echo '<p style="margin:10px 0 4px;"><strong>' . esc_html__( 'Dues by year:', 'owbn-chronicle-manager' ) . '</strong></p>';
		echo '<ul style="margin:0 0 8px;padding:0;list-style:none;">';
		foreach ( $years as $i => $row ) {
			list( $label, $color ) = owbn_dues_status_year_label( $row['status'], 0 === $i );
			printf(
				'<li style="display:flex;justify-content:space-between;gap:8px;margin:0;padding:3px 0;border-bottom:1px solid #f0f0f1;"><span>%1$s%2$s</span><strong style="color:%3$s;">%4$s</strong></li>',
				esc_html( (string) $row['year'] ),
				0 === $i ? ' <span style="color:#646970;">' . esc_html__( '(current)', 'owbn-chronicle-manager' ) . '</span>' : '',
				esc_attr( $color ),
				esc_html( $label )
			);
		}
		echo '</ul>';
	}

	if ( $status['last_paid_at'] ) {
		printf(
			'<p>%s %s</p>',
			esc_html__( 'Last payment:', 'owbn-chronicle-manager' ),
			esc_html( mysql2date( get_option( 'date_format' ), (string) $status['last_paid_at'] ) )
		);
	}
}
