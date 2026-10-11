<?php
defined( 'ABSPATH' ) || exit;

/**
 * Stiahnutie, vyčistenie a uloženie ponúk.
 */
class SVL_Store {

	const OPTION  = 'svl_deals';
	const HISTORY = 'svl_price_history';
	const GROUPS  = array( 'exotika', 'azia', 'europa' );

	public static function providers() {
		$providers = array(
			new SVL_Provider_Ryanair(),
			new SVL_Provider_Wizzair(),
			new SVL_Provider_Travelpayouts(),
			new SVL_Provider_Manual(),
		);
		/** Umožní doplniť ďalšieho poskytovateľa (trieda odvodená od SVL_Provider). */
		return apply_filters( 'svl_providers', $providers );
	}

	public static function data() {
		$d = get_option( self::OPTION );
		return is_array( $d ) ? $d : array(
			'updated' => 0,
			'deals'   => array(),
			'status'  => array(),
		);
	}

	/**
	 * @param string $group exotika|europa
	 */
	public static function deals( $group ) {
		return array_values(
			array_filter(
				self::data()['deals'],
				static function ( $d ) use ( $group ) {
					return $d['group'] === $group && $d['depart'] >= gmdate( 'Y-m-d' );
				}
			)
		);
	}

	/**
	 * Obnoví všetky ponuky. Volá WP-Cron každých 30 minút.
	 */
	public static function refresh( $force = false ) {
		if ( ! $force && get_transient( 'svl_lock' ) ) {
			return false;
		}
		set_transient( 'svl_lock', 1, 5 * MINUTE_IN_SECONDS );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 180 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		$old    = self::data();
		$deals  = array();
		$status = array();

		foreach ( self::providers() as $p ) {
			if ( ! $p instanceof SVL_Provider || ! $p->enabled() ) {
				continue;
			}
			foreach ( self::GROUPS as $group ) {
				if ( ! $p->supports( $group ) ) {
					continue;
				}
				$key = $p->id() . ':' . $group;
				try {
					$got   = $p->fetch( $group );
					$deals = array_merge( $deals, $got );
					$status[ $key ] = array(
						'ok'    => true,
						'count' => count( $got ),
						'time'  => time(),
						'msg'   => '',
					);
				} catch ( Throwable $e ) {
					// Ponecháme posledné platné ponuky (max. 24 h).
					$prev  = array_filter(
						$old['deals'],
						static function ( $d ) use ( $p, $group ) {
							return $d['source'] === $p->id() && $d['group'] === $group && $d['fetched'] > time() - DAY_IN_SECONDS;
						}
					);
					$deals = array_merge( $deals, $prev );
					$status[ $key ] = array(
						'ok'    => false,
						'count' => count( $prev ),
						'time'  => time(),
						'msg'   => wp_strip_all_tags( $e->getMessage() ),
					);
				}
			}
		}

		$deals = self::mark_lows( self::clean( $deals ) );
		$data  = array(
			'updated' => time(),
			'deals'   => $deals,
			'status'  => $status,
		);
		update_option( self::OPTION, $data, false );
		delete_transient( 'svl_lock' );

		do_action( 'svl_deals_updated', $deals );
		return $data;
	}

	public static function clean( array $deals ) {
		$today       = gmdate( 'Y-m-d' );
		$exotic      = SVL_Settings::codes( 'exotic_destinations' );
		$eu_origins  = SVL_Settings::codes( 'europe_origins' );
		$max_price   = (int) SVL_Settings::get( 'europe_max_price' );
		$europe_only = (bool) SVL_Settings::get( 'europe_only' );

		$best = array();
		foreach ( $deals as $d ) {
			if ( empty( $d['price'] ) || $d['price'] <= 0 || $d['depart'] < $today ) {
				continue;
			}
			// Zobrazujeme iba spiatočné letenky (Európa aj Ázia a exotika).
			if ( empty( $d['return'] ) ) {
				continue;
			}
			if ( 'azia' === $d['group'] ) {
				if ( ! in_array( $d['continent'], array( 'as', 'ex' ), true ) ) {
					continue;
				}
				// Rebríček: najlacnejšia letenka do každého mesta (z ktoréhokoľvek letiska).
				$key = $d['group'] . '|' . ( $d['dest_city'] ? $d['dest_city'] : $d['destination'] );
			} elseif ( 'exotika' === $d['group'] ) {
				if ( ! in_array( $d['destination'], $exotic, true ) ) {
					continue;
				}
				$key = implode( '|', array( $d['group'], $d['origin'], $d['destination'], $d['source'], $d['depart'], $d['return'], $d['airline_code'] ?? '' ) );
			} else {
				if ( in_array( $d['destination'], $eu_origins, true ) || $d['origin'] === $d['destination'] ) {
					continue;
				}
				if ( $max_price && $d['price'] > $max_price ) {
					continue;
				}
				if ( $europe_only && $d['continent'] && 'eu' !== $d['continent'] ) {
					continue;
				}
				// Európa: najlacnejší let do každej destinácie z každého letiska.
				$key = implode( '|', array( $d['group'], $d['origin'], $d['destination'] ) );
			}
			if ( ! isset( $best[ $key ] ) || $d['price'] < $best[ $key ]['price'] ) {
				$best[ $key ] = $d;
			}
		}
		$deals = array_values( $best );
		usort(
			$deals,
			static function ( $a, $b ) {
				return $a['price'] <=> $b['price'];
			}
		);
		return $deals;
	}

	/**
	 * Označí ponuky, ktoré sú najlacnejšie za posledných 30 dní.
	 */
	private static function mark_lows( array $deals ) {
		$history = get_option( self::HISTORY, array() );
		$history = is_array( $history ) ? $history : array();
		$today   = gmdate( 'Y-m-d' );
		$cutoff  = gmdate( 'Y-m-d', strtotime( '-30 days' ) );

		foreach ( $deals as &$d ) {
			$key  = $d['group'] . '|' . $d['origin'] . '|' . $d['destination'];
			$past = array_filter(
				$history[ $key ] ?? array(),
				static function ( $v, $day ) use ( $today, $cutoff ) {
					return $day !== $today && $day >= $cutoff;
				},
				ARRAY_FILTER_USE_BOTH
			);
			$d['low'] = count( $past ) >= 3 && $d['price'] < min( $past );
		}
		unset( $d );

		foreach ( $deals as $d ) {
			$key = $d['group'] . '|' . $d['origin'] . '|' . $d['destination'];
			if ( ! isset( $history[ $key ][ $today ] ) || $d['price'] < $history[ $key ][ $today ] ) {
				$history[ $key ][ $today ] = $d['price'];
			}
		}
		foreach ( $history as $key => $days ) {
			$history[ $key ] = array_filter(
				$days,
				static function ( $day ) use ( $cutoff ) {
					return $day >= $cutoff;
				},
				ARRAY_FILTER_USE_KEY
			);
			if ( ! $history[ $key ] ) {
				unset( $history[ $key ] );
			}
		}
		update_option( self::HISTORY, $history, false );
		return $deals;
	}
}
