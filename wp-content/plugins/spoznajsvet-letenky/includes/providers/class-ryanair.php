<?php
defined( 'ABSPATH' ) || exit;

/**
 * Ryanair – verejné „fare finder“ API webu ryanair.com.
 * Vráti najlacnejší jednosmerný let do každej destinácie z daného letiska.
 */
class SVL_Provider_Ryanair extends SVL_Provider {

	const ENDPOINT = 'https://www.ryanair.com/api/farfnd/v4/oneWayFares';

	public function id() {
		return 'ryanair';
	}

	public function label() {
		return 'Ryanair';
	}

	public function supports( $group ) {
		return 'europa' === $group;
	}

	public function fetch( $group ) {
		$from = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
		$to   = gmdate( 'Y-m-d', strtotime( '+' . (int) SVL_Settings::get( 'europe_days' ) . ' days' ) );

		return $this->each_tolerant(
			SVL_Settings::codes( 'europe_origins' ),
			function ( $origin ) use ( $group, $from, $to ) {
				$url  = add_query_arg(
					array(
						'departureAirportIataCode'   => $origin,
						'outboundDepartureDateFrom'  => $from,
						'outboundDepartureDateTo'    => $to,
						'outboundDepartureTimeFrom'  => '00:00',
						'outboundDepartureTimeTo'    => '23:59',
						'adultPaxCount'              => 1,
						'currency'                   => 'EUR',
						'market'                     => 'sk-sk',
						'language'                   => 'sk',
					),
					self::ENDPOINT
				);
				$json = $this->get_json( $url );
				$out  = array();
				foreach ( (array) ( $json['fares'] ?? array() ) as $fare ) {
					$f = $fare['outbound'] ?? null;
					if ( empty( $f['arrivalAirport']['iataCode'] ) || empty( $f['price']['value'] ) ) {
						continue;
					}
					$price = SVL_Currency::to_eur( $f['price']['value'], $f['price']['currencyCode'] ?? 'EUR' );
					if ( null === $price ) {
						continue;
					}
					$dest  = $f['arrivalAirport']['iataCode'];
					$date  = substr( $f['departureDate'], 0, 10 );
					$out[] = SVL_Format::deal(
						array(
							'group'       => $group,
							'source'      => 'ryanair',
							'origin'      => $origin,
							'destination' => $dest,
							'dest_city'   => $f['arrivalAirport']['city']['name'] ?? ( $f['arrivalAirport']['name'] ?? '' ),
							'dest_iso'    => $f['arrivalAirport']['city']['countryCode'] ?? '',
							'dest_country' => $f['arrivalAirport']['countryName'] ?? '',
							'depart'      => $date,
							'price'       => $price,
							'stops'       => 0,
							'airline'     => 'Ryanair',
							'url'         => add_query_arg(
								array(
									'adults'          => 1,
									'teens'           => 0,
									'children'        => 0,
									'infants'         => 0,
									'dateOut'         => $date,
									'isReturn'        => 'false',
									'originIata'      => $origin,
									'destinationIata' => $dest,
								),
								'https://www.ryanair.com/sk/sk/trip/flights/select'
							),
						)
					);
				}
				return $out;
			}
		);
	}
}
