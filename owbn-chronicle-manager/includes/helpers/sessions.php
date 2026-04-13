<?php
/**
 * Session recurrence helper. Expands session_list rules into UTC timestamps in [$from, $to].
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owbn_chronicle_expand_session_dates' ) ) :

function owbn_chronicle_expand_session_dates( array $session, $tz_name, $from, $to ) {
	$frequency  = $session['frequency'] ?? '';
	$day        = $session['day'] ?? '';
	$start_time = $session['start_time'] ?? '';

	$day_map = [
		'Monday'    => 1,
		'Tuesday'   => 2,
		'Wednesday' => 3,
		'Thursday'  => 4,
		'Friday'    => 5,
		'Saturday'  => 6,
		'Sunday'    => 7,
	];

	if ( ! isset( $day_map[ $day ] ) ) {
		return [];
	}
	if ( empty( $start_time ) || ! preg_match( '/^(\d{1,2}):(\d{2})$/', $start_time, $m ) ) {
		return [];
	}

	$target_dow = $day_map[ $day ];
	$hour       = (int) $m[1];
	$minute     = (int) $m[2];

	try {
		$tz = new DateTimeZone( $tz_name );
	} catch ( Exception $e ) {
		$tz = new DateTimeZone( 'UTC' );
	}

	$utc = new DateTimeZone( 'UTC' );

	$from = (int) $from;
	$to   = (int) $to;

	$start_dt = ( new DateTime( '@' . $from ) )->setTimezone( $tz );
	$start_dt->setTime( 0, 0, 0 );
	$end_dt = ( new DateTime( '@' . $to ) )->setTimezone( $tz );

	$dates  = [];
	$cursor = clone $start_dt;

	$cursor_dow = (int) $cursor->format( 'N' );
	$offset     = ( $target_dow - $cursor_dow + 7 ) % 7;
	$cursor->modify( '+' . $offset . ' days' );

	$week_count = 0;

	while ( $cursor <= $end_dt ) {
		$day_of_month  = (int) $cursor->format( 'j' );
		$week_of_month = (int) ceil( $day_of_month / 7 );

		$include = false;
		switch ( $frequency ) {
			case 'Every':
				$include = true;
				break;

			case 'Every Other':
				// Prefer staff-supplied anchor_date so parity matches their calendar.
				// Falls back to epoch parity if anchor is missing or invalid.
				$anchor = $session['anchor_date'] ?? '';
				if ( $anchor && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $anchor ) ) {
					try {
						$anchor_dt = new DateTime( $anchor, $tz );
						$diff_days = (int) floor( ( $cursor->getTimestamp() - $anchor_dt->getTimestamp() ) / DAY_IN_SECONDS );
						$include   = ( 0 === ( ( $diff_days / 7 ) % 2 ) );
					} catch ( Exception $e ) {
						$global_week = (int) floor( $cursor->getTimestamp() / ( 7 * DAY_IN_SECONDS ) );
						$include     = ( 0 === $global_week % 2 );
					}
				} else {
					$global_week = (int) floor( $cursor->getTimestamp() / ( 7 * DAY_IN_SECONDS ) );
					$include     = ( 0 === $global_week % 2 );
				}
				break;

			case '1st':
				$include = ( 1 === $week_of_month );
				break;

			case '2nd':
				$include = ( 2 === $week_of_month );
				break;

			case '3rd':
				$include = ( 3 === $week_of_month );
				break;

			case '4th':
				$include = ( 4 === $week_of_month );
				break;

			case '5th':
				$include = ( 5 === $week_of_month );
				break;

			default:
				$include = false;
		}

		if ( $include ) {
			$event_local = clone $cursor;
			$event_local->setTime( $hour, $minute, 0 );
			$event_utc = clone $event_local;
			$event_utc->setTimezone( $utc );
			$ts = $event_utc->getTimestamp();
			if ( $ts >= $from && $ts <= $to ) {
				$dates[] = $ts;
			}
		}

		$cursor->modify( '+7 days' );
		$week_count++;
		if ( $week_count > 52 ) {
			break;
		}
	}

	sort( $dates, SORT_NUMERIC );
	return $dates;
}

endif;
