<?php
defined( 'ABSPATH' ) || exit;

/**
 * Travelpayouts / Aviasales Data API – oficiálne API s najnižšími cenami
 * nájdenými vo vyhľadávaniach (vrátane klasických aerolínií ako Qatar, Emirates, Etihad, Turkish).
 * Vyžaduje bezplatný token z https://www.travelpayouts.com/ (Tools → API).
 */
class SVL_Provider_Travelpayouts extends SVL_Provider {

	const ENDPOINT = 'https://api.travelpayouts.com/aviasales/v3/prices_for_dates';

	public function id() {
		return 'travelpayouts';
	}

	public function label() {
		return 'Aviasales (Travelpayouts)';
	}

	public function enabled() {
		return parent::enabled() && '' !== (string) SVL_Settings::get( 'tp_token' );
	}

	public function supports( $group ) {
		return in_array( $group, array( 'exotika', 'azia', 'europa' ), true );
	}

	public function fetch( $group ) {
		if ( 'azia' === $group ) {
			return $this->fetch_asia();
		}
		return 'exotika' === $group ? $this->fetch_exotic() : $this->fetch_europe();
	}

	/** Odpovede v rámci jednej aktualizácie (Európa a Ázia sa pýtajú na to isté). */
	private $memo = array();

	private function query( array $args ) {
		$key = md5( wp_json_encode( $args ) );
		if ( isset( $this->memo[ $key ] ) ) {
			return $this->memo[ $key ];
		}
		return $this->memo[ $key ] = $this->query_api( $args );
	}

	private function query_api( array $args ) {
		$token = (string) SVL_Settings::get( 'tp_token' );
		$args  = array_merge(
			array(
				'currency' => 'eur',
				'sorting'  => 'price',
				'direct'   => 'false',
				'page'     => 1,
				'token'    => $token,
			),
			$args
		);
		$json = $this->get_json( add_query_arg( $args, self::ENDPOINT ), array( 'X-Access-Token' => $token ) );
		if ( empty( $json['success'] ) ) {
			throw new RuntimeException( isset( $json['error'] ) ? (string) $json['error'] : 'Travelpayouts vrátil chybu.' );
		}
		return (array) ( $json['data'] ?? array() );
	}

	private function link( $path ) {
		if ( ! $path ) {
			return '';
		}
		$url    = 'https://www.aviasales.com' . $path;
		$marker = (string) SVL_Settings::get( 'tp_marker' );
		if ( $marker ) {
			$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'marker=' . rawurlencode( $marker );
		}
		return $url;
	}

	private function to_deal( $group, array $row, $origin_fallback ) {
		$airlines = svl_airlines();
		$code     = $row['airline'] ?? '';
		return SVL_Format::deal(
			array(
				'group'       => $group,
				'source'      => 'travelpayouts',
				'origin'      => $row['origin_airport'] ?? ( $row['origin'] ?? $origin_fallback ),
				'destination' => $row['destination'],
				'depart'      => $row['departure_at'],
				'return'      => $row['return_at'] ?? '',
				'price'       => $row['price'],
				'stops'       => isset( $row['transfers'] ) ? (int) $row['transfers'] : null,
				'airline'     => $airlines[ $code ] ?? $code,
				'airline_code' => $code,
				'url'         => $this->link( $row['link'] ?? '' ),
			)
		);
	}

	private function fetch_exotic() {
		$pairs = array();
		foreach ( SVL_Settings::codes( 'exotic_origins' ) as $o ) {
			foreach ( SVL_Settings::codes( 'exotic_destinations' ) as $d ) {
				$pairs[] = array( $o, $d );
			}
		}
		$months = (int) SVL_Settings::get( 'exotic_months' );

		return $this->each_tolerant(
			$pairs,
			function ( $pair ) use ( $months ) {
				list( $o, $d ) = $pair;
				$limit         = strtotime( '+' . $months . ' months' );
				$rows          = $this->query(
					array(
						'origin'      => $o,
						'destination' => $d,
						'one_way'     => 'false',
						'limit'       => 30,
					)
				);
				// Záloha: staršie API „najlacnejšie lety“ (podľa počtu prestupov) – často má dáta aj tam, kde nové nie.
				if ( ! $rows ) {
					$rows = $this->cheap( $o, $d );
				}
				$out = array();
				foreach ( $rows as $row ) {
					if ( empty( $row['price'] ) || empty( $row['departure_at'] ) || strtotime( $row['departure_at'] ) > $limit || strtotime( $row['departure_at'] ) < time() ) {
						continue;
					}
					// Z/do rovnakej destinácie (napr. BKK/DMK) zapíšeme pod konfigurovaný kód mesta.
					$row['destination'] = $d;
					$deal               = $this->to_deal( 'exotika', $row, $o );
					$deal['origin']     = $o;
					if ( ! $deal['url'] ) {
						$deal['url'] = $this->search_link( $o, $d, $deal['depart'], $deal['return'] );
					}
					$out[] = $deal;
					if ( count( $out ) >= 3 ) {
						break;
					}
				}
				return $out;
			}
		);
	}

	/**
	 * Rebríček Ázie a exotiky: najlacnejšie spiatočné letenky z každého letiska kamkoľvek,
	 * odfiltrované na Áziu, Perzský záliv a exotiku (Indický oceán, Karibik…).
	 */
	private function fetch_asia() {
		$months = (int) SVL_Settings::get( 'exotic_months' );
		return $this->each_tolerant(
			SVL_Settings::codes( 'exotic_origins' ),
			function ( $o ) use ( $months ) {
				$rows  = $this->query(
					array(
						'origin'  => $o,
						'one_way' => 'false',
						'limit'   => 1000,
					)
				);
				$limit = strtotime( '+' . $months . ' months' );
				$out   = array();
				foreach ( $rows as $row ) {
					if ( empty( $row['price'] ) || empty( $row['destination'] ) || empty( $row['return_at'] ) || empty( $row['departure_at'] ) ) {
						continue;
					}
					$t = strtotime( $row['departure_at'] );
					if ( $t > $limit || $t < time() ) {
						continue;
					}
					$info = svl_airport( strtoupper( $row['destination'] ) );
					if ( ! in_array( $info['continent'], SVL_Settings::exotic_regions(), true ) ) {
						continue;
					}
					$deal           = $this->to_deal( 'azia', $row, $o );
					$deal['origin'] = $o;
					if ( ! $deal['url'] ) {
						$deal['url'] = $this->search_link( $o, $deal['destination'], $deal['depart'], $deal['return'] );
					}
					$out[] = $deal;
				}
				return $out;
			}
		);
	}

	/**
	 * GET /v1/prices/cheap – vráti najlacnejšie spiatočné lety s aerolinkou, zoskupené podľa počtu prestupov.
	 *
	 * @return array Riadky v rovnakom tvare ako prices_for_dates.
	 */
	private function cheap( $origin, $dest ) {
		$token = (string) SVL_Settings::get( 'tp_token' );
		$json  = $this->get_json(
			add_query_arg(
				array(
					'origin'      => $origin,
					'destination' => $dest,
					'currency'    => 'eur',
					'token'       => $token,
				),
				'https://api.travelpayouts.com/v1/prices/cheap'
			),
			array( 'X-Access-Token' => $token )
		);
		$rows = array();
		foreach ( (array) ( $json['data'] ?? array() ) as $by_stops ) {
			foreach ( (array) $by_stops as $stops => $r ) {
				if ( ! is_array( $r ) ) {
					continue;
				}
				$r['transfers'] = (int) $stops;
				$r['origin']    = $origin;
				$rows[]         = $r;
			}
		}
		usort(
			$rows,
			static function ( $a, $b ) {
				return ( $a['price'] ?? 0 ) <=> ( $b['price'] ?? 0 );
			}
		);
		return $rows;
	}

	/** Odkaz na vyhľadávanie Aviasales pre konkrétne dátumy (ak API odkaz nevrátilo). */
	private function search_link( $o, $d, $depart, $return ) {
		$path = '/search/' . $o . gmdate( 'dm', strtotime( $depart ) ) . $d . ( $return ? gmdate( 'dm', strtotime( $return ) ) : '' ) . '1';
		return $this->link( $path );
	}

	private function fetch_europe() {
		$min_n = (int) SVL_Settings::get( 'europe_min_nights' );
		$max_n = (int) SVL_Settings::get( 'europe_max_nights' );
		return $this->each_tolerant(
			SVL_Settings::codes( 'europe_origins' ),
			function ( $o ) use ( $min_n, $max_n ) {
				$rows  = $this->query(
					array(
						'origin'  => $o,
						'one_way' => 'false',
						'limit'   => 500,
					)
				);
				$limit = strtotime( '+' . (int) SVL_Settings::get( 'europe_days' ) . ' days' );
				$out   = array();
				foreach ( $rows as $row ) {
					if ( empty( $row['price'] ) || empty( $row['destination'] ) || empty( $row['departure_at'] ) || empty( $row['return_at'] ) || strtotime( $row['departure_at'] ) > $limit ) {
						continue;
					}
					$nights = SVL_Format::nights( substr( $row['departure_at'], 0, 10 ), substr( $row['return_at'], 0, 10 ) );
					if ( $nights < $min_n || $nights > $max_n ) {
						continue;
					}
					$deal           = $this->to_deal( 'europa', $row, $o );
					$deal['origin'] = $o;
					$out[]          = $deal;
				}
				return $out;
			}
		);
	}
}
