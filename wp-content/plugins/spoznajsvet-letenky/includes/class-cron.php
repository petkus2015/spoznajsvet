<?php
defined( 'ABSPATH' ) || exit;

/**
 * Automatická aktualizácia každých 30 minút cez WP-Cron.
 *
 * Tip: na produkcii vypnite „pseudo-cron“ (DISABLE_WP_CRON) a pridajte systémový
 * cron, ktorý každých 30 minút zavolá wp-cron.php (pozri README).
 */
class SVL_Cron {

	const HOOK     = 'svl_refresh_deals';
	const HOOK_NOW = 'svl_refresh_deals_now';
	const INTERVAL = 30 * MINUTE_IN_SECONDS;

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
		add_action( self::HOOK_NOW, array( __CLASS__, 'run' ) );
		add_action( 'init', array( __CLASS__, 'ensure' ) );
		add_action( 'update_option_' . SVL_Settings::OPTION, array( __CLASS__, 'refresh_soon' ) );
	}

	public static function schedules( $schedules ) {
		$schedules['svl_half_hour'] = array(
			'interval' => self::INTERVAL,
			'display'  => 'Každých 30 minút',
		);
		return $schedules;
	}

	public static function run() {
		SVL_Store::refresh();
	}

	public static function ensure() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + 30, 'svl_half_hour', self::HOOK );
		}
		// Poistka: ak cron dlho nebežal (málo návštev), naplánujeme okamžitú obnovu.
		$updated = (int) SVL_Store::data()['updated'];
		if ( time() - $updated > self::INTERVAL + 10 * MINUTE_IN_SECONDS ) {
			self::refresh_soon();
		}
	}

	public static function refresh_soon() {
		if ( ! wp_next_scheduled( self::HOOK_NOW ) ) {
			wp_schedule_single_event( time(), self::HOOK_NOW );
		}
	}

	public static function activate() {
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		wp_clear_scheduled_hook( self::HOOK );
		wp_schedule_event( time() + 30, 'svl_half_hour', self::HOOK );
		wp_schedule_single_event( time(), self::HOOK_NOW );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( self::HOOK );
		wp_clear_scheduled_hook( self::HOOK_NOW );
	}

	public static function next_run() {
		$next = wp_next_scheduled( self::HOOK );
		return $next ? $next : (int) SVL_Store::data()['updated'] + self::INTERVAL;
	}
}
