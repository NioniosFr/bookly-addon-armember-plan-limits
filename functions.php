<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the plan limits.
 *
 * @return array
 */
function dfr_baarmpl_get_plan_limits() {
	// TODO: make this dynamic via admin settings.
	return array(
		7 => array(
			'type'  => 'daily',
			'limit' => 1,
		),
		6 => array(
			'type'  => 'daily',
			'limit' => 1,
		),
		5 => array(
			'type'  => 'weekly',
			'limit' => 3,
		),
		4 => array(
			'type'  => 'weekly',
			'limit' => 2,
		),
		3 => array(
			'type'  => 'daily',
			'limit' => 1,
		),
		2 => array(
			'type'  => 'daily',
			'limit' => 1,
		),
	);
}

/**
 * Get the plan data for a given plan ID.
 *
 * @param int    $plan_id      The armember plan ID.
 * @param string $appointment_date The date to check for appointments.
 *
 * @return array
 */
function dfr_baarmpl_get_plan_data( $plan_id, $appointment_date ) {
	$plan_limits = dfr_baarmpl_get_plan_limits();
	if ( ! array_key_exists( $plan_id, $plan_limits ) ) {
		return array(
			'type'  => 'daily',
			'limit' => 1,
			'start' => $appointment_date,
			'end'   => $appointment_date,
		);
	}

	$limit = $plan_limits[ $plan_id ]['limit'];
	$type  = $plan_limits[ $plan_id ]['type'];

	$current_date_start = date_create( $appointment_date )->modify( 'this week' )->format( 'Y-m-d 00:00:00' );
	$current_date_end   = date_create( $appointment_date )->modify( 'this week' )->modify( 'next sunday' )->format( 'Y-m-d 23:59:59' );

	return array(
		'type'  => $type,
		'limit' => $limit,
		'start' => $current_date_start,
		'end'   => $current_date_end,
	);
}

/**
 * Get appointments of a customer.
 *
 * @param int    $customer_id      The customer ID.
 * @param string $appointment_date The date to check for appointments.
 *
 * @return bool
 */
function dfr_baarmpl_can_customer_book_appointments( $customer_id, $appointment_date, $plan_id ) {
	/**
	 * @var \wpdb $wpdb
	 */
	global $wpdb;

	$plan_data = dfr_baarmpl_get_plan_data( $plan_id, $appointment_date );

	$week_date_start = $plan_data['start'];
	$week_date_end   = $plan_data['end'];

	$result = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT
IFNULL(SUM(CASE
            WHEN DATE(a.start_date) = DATE(%s) THEN 1
            ELSE 0
        END), 0) AS daily_count,
    COUNT(*) AS weekly_count
FROM {$wpdb->prefix}bookly_customer_appointments AS ca
LEFT JOIN {$wpdb->prefix}bookly_appointments AS a
    ON a.id = ca.appointment_id
WHERE ca.customer_id = %d
AND a.start_date >= %s
AND a.end_date <= %s
AND ( ca.status = %s OR ca.status = %s )",
			$appointment_date,
			$customer_id,
			$week_date_start,
			$week_date_end,
			\Bookly\Lib\Entities\CustomerAppointment::STATUS_PENDING,
			\Bookly\Lib\Entities\CustomerAppointment::STATUS_APPROVED
		)
	);

	if ( null === $result ) {
		return false;
	}

	$daily_count  = $result[0]->daily_count;
	$weekly_count = $result[0]->weekly_count;

	if ( $daily_count > 0 ) {
		// No matter the plan type, only one booking per day is allowed.
		return false;
	}

	if ( 'weekly' === $plan_data['type'] ) {
		$limit_reached = $weekly_count >= $plan_data['limit'];
		return ! $limit_reached;
	}

	return true;
}
