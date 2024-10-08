<?php
/*
Plugin Name: Bookly ARMember Plan Limits (Add-on)
Plugin URI: https://www.github.com/nioniosfr/bookly-addon-armember-plan-limits
Description: Bookly ARMember Plan Limits add-on allows you to limit customers from booking services outside the active period of their subsriction plan.
Version: 0.0.1
Author: Dionysios Fryganas <dfryganas@gmail.com>
Author URI: https://www.github.com/nioniosfr
Text Domain: baarmpl
Domain Path: /languages
License: MIT
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Display a warning in admin sections when the plugin cannot be used.
 *
 * @param array $missing_plugins The list of missing plugins.
 */
function dfr_baarmpl_plugin_required_admin_notice( $missing_plugins ) {
	printf(
		'<div class="error"><h3>Bookly ARMember Customer Sync (Add-on)</h3><p>To install this plugin - <strong>%s</strong> plugin is required.</p></div>',
		esc_html( implode( ', ', $missing_plugins ) )
	);
}

/**
 * Initialization logic of this plugin.
 */
function dfr_baarmpl_init() {
	$missing_plugins = array();
	if ( ! is_plugin_active( 'bookly-responsive-appointment-booking-tool/main.php' ) ) {

		$missing_plugins[] = 'Bookly';
	}
	if ( ! is_plugin_active( 'armember/armember.php' ) ) {

		$missing_plugins[] = 'ARMember';
	}

	if ( ! empty( $missing_plugins ) ) {
		add_action(
			is_network_admin() ? 'network_admin_notices' : 'admin_notices',
			function () use ( $missing_plugins ) {
				dfr_baarmpl_plugin_required_admin_notice( $missing_plugins );
			}
		);
	}

	add_filter( 'bookly_appointments_limit', 'dfr_baarmpl_subscription_plan_limit', 10, 4 );
}

add_action( 'init', 'dfr_baarmpl_init' );

/**
 * Filter the current limit based on the users group.
 *
 * In bookly-responsive-appointment-booking-tool/lib/entities/Service.php, inside appointmentsLimitReached method, line ~375
 * Add the following filter:
 *
 * $limit = apply_filters( 'bookly_appointments_limit', $this->getAppointmentsLimit(), $service_id, $customer_id, $appointment_dates );
 * if ( $db_count + $cart_count > $limit ) {
 *   return true;
 * }
 *
 * @param int      $default_limit The service limit.
 *
 * @param int      $service_id    The service being checked for limits.
 *
 * @param int      $customer_id   The bookly customer.
 *
 * @param string[] $appointment_dates The dates being booked by the customer.
 *
 * @return int
 */
function dfr_baarmpl_subscription_plan_limit( $default_limit, $service_id, $customer_id, $appointment_dates ) {
	$customer = new \Bookly\Lib\Entities\Customer();
	$customer->load( $customer_id );

	if ( null === $customer || ! $customer->isLoaded() ) {
		return $default_limit;
	}

	$plan_ids = get_user_meta( $customer->getWpUserId(), 'arm_user_plan_ids', true );
	$plan_ids = ! empty( $plan_ids ) ? $plan_ids : array();

	require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

	foreach ( $plan_ids as $plan_id ) {
		$plan_data = get_user_meta( $customer->getWpUserId(), 'arm_user_plan_' . $plan_id, true );
		foreach ( $appointment_dates as $appointment_date ) {
			if ( ! dfr_baarmpl_can_customer_book_appointments( $customer_id, $appointment_date, $plan_id ) ) {
				return 0;
			}
		}
	}
	return $default_limit;
}
