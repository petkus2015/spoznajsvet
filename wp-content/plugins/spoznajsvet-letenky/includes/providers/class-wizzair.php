<?php
defined( 'ABSPATH' ) || exit;

/**
 * Wizz Air – neoficiálne API webu wizzair.com (mapa liniek + cenový kalendár „timetable“).
 *
 * Aby sme Wizz Air nezahltili, v každom behu (30 min) obnovíme len obmedzený
 * počet liniek; ostatné sa berú z vyrovnávacej pamäte (max. 24 h).
 */
class SVL_Provider_Wizzair extends SVL_Provider {

	const CACHE_OPTION = 'svl_wizz_cache';
	const ROUTE_TTL    = 3 * HOUR_IN_SECONDS;
	const PER_RUN      = 14;
	const WINDOW_DAYS  = 42; // Timetable API prijme max. ~6 týždňov.

	public function id() {
		return 'wizzair';
	}

	public function label() {
		return 'Wizz Air';
	}

	public function supports( $group ) {
		return in_array( $group, array( 'exotika', 'europa' ), true );
	}

	private function base() {
		$base = get_transient( 'svl_wizz_base' );
		if ( $base ) {
			return $base;
		}
		$res = wp_remote_get( 'https://wizzair.com/buildnumber', array( 'timeout' => 15, 'headers' => $this->headers() ) );
		if ( is_wp_error( $res ) ) {
			throw new RuntimeException( $res->get_error_message() );
		}
		if ( ! preg_match( '#https://be\.wizzair\.com/[0-9.]+#', wp_remote_retrieve_body( $res ), $m ) ) {
			throw new RuntimeException( 'Nepodarilo sa zistiť verziu API Wizz Air.' );
		}
		set_transient( 'svl_wizz_base', $m[0], 6 * HOUR_IN_SECONDS );
		return $m[0];
	}

	private function wizz_headers() {
		return array(
			'Origin'  => 'https://wizzair.com',
			'Referer' => 'https://wizzair.com/',
		);
	}

	/** @return array origin => [dest, ...] */
	private function routes() {
		$routes = get_transient( 'svl_wizz_routes' );
		if ( is_array( $routes ) ) {
			return $routes;
		}
		$json   = $this->get_json( $this->base() . '/Api/asset/map?languageCode=en-gb', $this->wizz_headers() );
		$routes = array();
		foreach ( (array) ( $json['cities'] ?? array() ) as $city ) {
			if ( empty( $city['iata'] ) ) {
				continue;
			}
			$routes[ $city['iata'] ] = array();
			foreach ( (array) ( $city['connections'] ?? array() ) as $c ) {
				if ( ! empty( $c['iata'] ) ) {
					$routes[ $city['iata'] ][] = $c['iata'];
				}
			}
		}
		if ( ! $routes ) {
			throw new RuntimeException( 'Prázdna mapa liniek Wizz Air.' );
		}
		set_transient( 'svl_wizz_routes', $routes, DAY_IN_SECONDS );
		return $routes;
	}

	private function pairs( $group, array $routes ) {
		$pairs = array();
		if ( 'exotika' === $group ) {
			foreach ( SVL_Settings::codes( 'exotic_origins' ) as $o ) {
				foreach ( SVL_Settings::codes( 'exotic_destinations' ) as $d ) {
					if ( in_array( $d, $routes[ $o ] ?? array(), true ) ) {
						$pairs[] = array( $o, $d );
					}
				}
			}
			return $pairs;
		}
		$europe_only = (bool) SVL_Settings::get( 'europe_only' );
		foreach ( SVL_Settings::codes( 'europe_origins' ) as $o ) {
			foreach ( $routes[ $o ] ?? array() as $d ) {
				$info = svl_airport( $d );
				if ( $europe_only && $info['continent'] && 'eu' !== $info['continent'] ) {
					continue;
				}
				$pairs[] = array( $o, $d );
			}
		}
		return $pairs;
	}

	public function fetch( $group ) {
		$routes = $this->routes();
		$pairs  = $this->pairs( $group, $routes );
		if ( ! $pairs ) {
			return array();
		}
		$cache = get_option( self::CACHE_OPTION, array() );
		$cache = is_array( $cache ) ? $cache : array();

		// Najstaršie linky obnovíme ako prvé.
		$stale = array();
		foreach ( $pairs as $p ) {
			$key = $group . '|' . $p[0] . '|' . $p[1];
			$age = isset( $cache[ $key ]['t'] ) ? time() - $cache[ $key ]['t'] : PHP_INT_MAX;
			if ( $age > self::ROUTE_TTL ) {
				$stale[ $key ] = $age;
			}
		}
		arsort( $stale );
		$todo   = array_slice( array_keys( $stale ), 0, self::PER_RUN );
		$base   = $this->base();
		$errors = 0;

		foreach ( $todo as $key ) {
			list( , $o, $d ) = explode( '|', $key );
			try {
				$cache[ $key ] = array(
					't'     => time(),
					'deals' => 'exotika' === $group ? $this->round_trip( $base, $o, $d ) : $this->timetable( $base, $group, $o, $d ),
				);
			} catch ( Throwable $e ) {
				++$errors;
				$last_error = $e->getMessage();
			}
			usleep( 250000 );
		}

		if ( $todo && count( $todo ) === $errors ) {
			update_option( self::CACHE_OPTION, $cache, false );
			throw new RuntimeException( $last_error );
		}

		$out   = array();
		$valid = array();
		foreach ( $pairs as $p ) {
			$key = $group . '|' . $p[0] . '|' . $p[1];
			if ( isset( $cache[ $key ] ) && time() - $cache[ $key ]['t'] < DAY_IN_SECONDS ) {
				$valid[ $key ] = $cache[ $key ];
				foreach ( $cache[ $key ]['deals'] as $deal ) {
					$out[] = $deal;
				}
			}
		}
		// Zachováme aj záznamy druhej skupiny.
		foreach ( $cache as $key => $row ) {
			if ( 0 !== strpos( $key, $group . '|' ) && time() - $row['t'] < DAY_IN_SECONDS ) {
				$valid[ $key ] = $row;
			}
		}
		update_option( self::CACHE_OPTION, $valid, false );
		return $out;
	}

	/** Najlacnejšia cena z odpovede timetable pre jeden smer: [ 'Y-m-d' => EUR ]. */
	private function cheapest_by_day( array $flights ) {
		$out = array();
		foreach ( $flights as $f ) {
			if ( ( $f['priceType'] ?? '' ) !== 'price' || empty( $f['price']['amount'] ) ) {
				continue;
			}
			$eur = SVL_Currency::to_eur( $f['price']['amount'], $f['price']['currencyCode'] ?? 'EUR' );
			$day = substr( (string) ( $f['departureDate'] ?? '' ), 0, 10 );
			if ( null !== $eur && $day && ( ! isset( $out[ $day ] ) || $eur < $out[ $day ] ) ) {
				$out[ $day ] = $eur;
			}
		}
		return $out;
	}

	/**
	 * Spiatočná letenka pre Exotiku: tam aj späť v jednej požiadavke,
	 * najlacnejšia kombinácia s pobytom 5 – 21 nocí.
	 */
	private function round_trip( $base, $origin, $dest ) {
		$json = $this->post_json(
			$base . '/Api/search/timetable',
			array(
				'flightList'  => array(
					array(
						'departureStation' => $origin,
						'arrivalStation'   => $dest,
						'from'             => gmdate( 'Y-m-d', strtotime( '+1 day' ) ),
						'to'               => gmdate( 'Y-m-d', strtotime( '+26 days' ) ),
					),
					array(
						'departureStation' => $dest,
						'arrivalStation'   => $origin,
						'from'             => gmdate( 'Y-m-d', strtotime( '+6 days' ) ),
						'to'               => gmdate( 'Y-m-d', strtotime( '+46 days' ) ),
					),
				),
				'priceType'   => 'regular',
				'adultCount'  => 1,
				'childCount'  => 0,
				'infantCount' => 0,
			),
			$this->wizz_headers()
		);
		$out_days = $this->cheapest_by_day( (array) ( $json['outboundFlights'] ?? array() ) );
		$ret_days = $this->cheapest_by_day( (array) ( $json['returnFlights'] ?? array() ) );
		$best     = null;
		foreach ( $out_days as $dep => $p1 ) {
			foreach ( $ret_days as $ret => $p2 ) {
				$nights = SVL_Format::nights( $dep, $ret );
				if ( $nights < 5 || $nights > 21 ) {
					continue;
				}
				if ( null === $best || $p1 + $p2 < $best['price'] ) {
					$best = array(
						'price'  => $p1 + $p2,
						'depart' => $dep,
						'return' => $ret,
					);
				}
			}
		}
		if ( ! $best ) {
			return array();
		}
		return array(
			SVL_Format::deal(
				array(
					'group'        => 'exotika',
					'source'       => 'wizzair',
					'origin'       => $origin,
					'destination'  => $dest,
					'depart'       => $best['depart'],
					'return'       => $best['return'],
					'price'        => $best['price'],
					'stops'        => 0,
					'airline'      => 'Wizz Air',
					'airline_code' => 'W6',
					'url'          => sprintf( 'https://wizzair.com/sk-sk/booking/select-flight/%s/%s/%s/%s/1/0/0/null', $origin, $dest, $best['depart'], $best['return'] ),
				)
			),
		);
	}

	private function timetable( $base, $group, $origin, $dest ) {
		$from = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
		$to   = gmdate( 'Y-m-d', strtotime( '+' . self::WINDOW_DAYS . ' days' ) );
		$json = $this->post_json(
			$base . '/Api/search/timetable',
			array(
				'flightList'  => array(
					array(
						'departureStation' => $origin,
						'arrivalStation'   => $dest,
						'from'             => $from,
						'to'               => $to,
					),
				),
				'priceType'   => 'regular',
				'adultCount'  => 1,
				'childCount'  => 0,
				'infantCount' => 0,
			),
			$this->wizz_headers()
		);

		$best = null;
		foreach ( (array) ( $json['outboundFlights'] ?? array() ) as $f ) {
			if ( ( $f['priceType'] ?? '' ) !== 'price' || empty( $f['price']['amount'] ) ) {
				continue;
			}
			$eur = SVL_Currency::to_eur( $f['price']['amount'], $f['price']['currencyCode'] ?? 'EUR' );
			if ( null !== $eur && ( null === $best || $eur < $best['price'] ) ) {
				$best = array(
					'price' => $eur,
					'date'  => substr( $f['departureDate'], 0, 10 ),
				);
			}
		}
		if ( ! $best ) {
			return array();
		}
		return array(
			SVL_Format::deal(
				array(
					'group'       => $group,
					'source'      => 'wizzair',
					'origin'      => $origin,
					'destination' => $dest,
					'depart'      => $best['date'],
					'price'       => $best['price'],
					'stops'       => 0,
					'airline'     => 'Wizz Air',
					'airline_code' => 'W6',
					'url'         => sprintf( 'https://wizzair.com/sk-sk/booking/select-flight/%s/%s/%s/null/1/0/0/null', $origin, $dest, $best['date'] ),
				)
			),
		);
	}
}
