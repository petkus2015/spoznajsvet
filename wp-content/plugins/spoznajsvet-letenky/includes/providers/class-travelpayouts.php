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
		return in_array( $group, array( 'exotika', 'europa' ), true );
	}

	public function fetch( $group ) {
		return 'exotika' === $group ? $this->fetch_exotic() : $this->fetch_europe();
	}

	private function query( array $args ) {
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
				$rows          = $this->query(
					array(
						'origin'      => $o,
						'destination' => $d,
						'one_way'     => 'false',
						'limit'       => 30,
					)
				);
				$out   = array();
				$limit = strtotime( '+' . $months . ' months' );
				foreach ( $rows as $row ) {
					if ( empty( $row['price'] ) || empty( $row['departure_at'] ) || strtotime( $row['departure_at'] ) > $limit ) {
						continue;
					}
					// Z/do rovnakej destinácie (napr. BKK/DMK) zapíšeme pod konfigurovaný kód mesta.
					$row['destination'] = $d;
					$deal               = $this->to_deal( 'exotika', $row, $o );
					$deal['origin']     = $o;
					$out[]              = $deal;
					if ( count( $out ) >= 3 ) {
						break;
					}
				}
				return $out;
			}
		);
	}

	private function fetch_europe() {
		return $this->each_tolerant(
			SVL_Settings::codes( 'europe_origins' ),
			function ( $o ) {
				$rows  = $this->query(
					array(
						'origin'  => $o,
						'one_way' => 'true',
						'limit'   => 300,
					)
				);
				$limit = strtotime( '+' . (int) SVL_Settings::get( 'europe_days' ) . ' days' );
				$out   = array();
				foreach ( $rows as $row ) {
					if ( empty( $row['price'] ) || empty( $row['destination'] ) || empty( $row['departure_at'] ) || strtotime( $row['departure_at'] ) > $limit ) {
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
