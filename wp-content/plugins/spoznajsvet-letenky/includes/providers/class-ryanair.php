<?php
defined( 'ABSPATH' ) || exit;

/**
 * Ryanair – verejné „fare finder“ API webu ryanair.com.
 * Vráti najlacnejšiu spiatočnú letenku do každej destinácie z daného letiska.
 */
class SVL_Provider_Ryanair extends SVL_Provider {

	const ENDPOINT = 'https://www.ryanair.com/api/farfnd/v4/roundTripFares';

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
		$days  = (int) SVL_Settings::get( 'europe_days' );
		$min_n = (int) SVL_Settings::get( 'europe_min_nights' );
		$max_n = (int) SVL_Settings::get( 'europe_max_nights' );
		$from  = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
		$to    = gmdate( 'Y-m-d', strtotime( '+' . $days . ' days' ) );
		$back  = gmdate( 'Y-m-d', strtotime( '+' . ( $days + $max_n ) . ' days' ) );

		return $this->each_tolerant(
			SVL_Settings::codes( 'europe_origins' ),
			function ( $origin ) use ( $group, $from, $to, $back, $min_n, $max_n ) {
				// Spiatočné letenky: najlacnejšia kombinácia tam a späť do každej destinácie.
				$url  = add_query_arg(
					array(
						'departureAirportIataCode'  => $origin,
						'outboundDepartureDateFrom' => $from,
						'outboundDepartureDateTo'   => $to,
						'inboundDepartureDateFrom'  => $from,
						'inboundDepartureDateTo'    => $back,
						'durationFrom'              => $min_n,
						'durationTo'                => $max_n,
						'outboundDepartureTimeFrom' => '00:00',
						'outboundDepartureTimeTo'   => '23:59',
						'inboundDepartureTimeFrom'  => '00:00',
						'inboundDepartureTimeTo'    => '23:59',
						'adultPaxCount'             => 1,
						'currency'                  => 'EUR',
						'market'                    => 'sk-sk',
						'language'                  => 'sk',
					),
					self::ENDPOINT
				);
				$json = $this->get_json( $url );
				$out  = array();
				foreach ( (array) ( $json['fares'] ?? array() ) as $fare ) {
					$f  = $fare['outbound'] ?? null;
					$in = $fare['inbound'] ?? null;
					if ( empty( $f['arrivalAirport']['iataCode'] ) || empty( $in['departureDate'] ) ) {
						continue;
					}
					// Rozpis: cena letu tam a letu späť.
					$p_out  = ! empty( $f['price']['value'] ) ? SVL_Currency::to_eur( $f['price']['value'], $f['price']['currencyCode'] ?? 'EUR' ) : null;
					$p_back = ! empty( $in['price']['value'] ) ? SVL_Currency::to_eur( $in['price']['value'], $in['price']['currencyCode'] ?? 'EUR' ) : null;
					// Celková cena tam aj späť (summary), inak súčet oboch smerov.
					if ( ! empty( $fare['summary']['price']['value'] ) ) {
						$price = SVL_Currency::to_eur( $fare['summary']['price']['value'], $fare['summary']['price']['currencyCode'] ?? 'EUR' );
					} elseif ( ! empty( $f['price']['value'] ) && ! empty( $in['price']['value'] ) ) {
						$p1    = SVL_Currency::to_eur( $f['price']['value'], $f['price']['currencyCode'] ?? 'EUR' );
						$p2    = SVL_Currency::to_eur( $in['price']['value'], $in['price']['currencyCode'] ?? 'EUR' );
						$price = ( null === $p1 || null === $p2 ) ? null : $p1 + $p2;
					} else {
						$price = null;
					}
					if ( null === $price ) {
						continue;
					}
					$dest  = $f['arrivalAirport']['iataCode'];
					$date  = substr( $f['departureDate'], 0, 10 );
					$ret   = substr( $in['departureDate'], 0, 10 );
					$out[] = SVL_Format::deal(
						array(
							'group'        => $group,
							'source'       => 'ryanair',
							'origin'       => $origin,
							'destination'  => $dest,
							'dest_city'    => $f['arrivalAirport']['city']['name'] ?? ( $f['arrivalAirport']['name'] ?? '' ),
							'dest_iso'     => $f['arrivalAirport']['city']['countryCode'] ?? '',
							'dest_country' => $f['arrivalAirport']['countryName'] ?? '',
							'depart'       => $date,
							'return'       => $ret,
							'price'        => $price,
							'price_out'    => $p_out,
							'price_back'   => $p_back,
							'stops'        => 0,
							'airline'      => 'Ryanair',
							'airline_code' => 'FR',
							'url'          => add_query_arg(
								array(
									'adults'          => 1,
									'teens'           => 0,
									'children'        => 0,
									'infants'         => 0,
									'dateOut'         => $date,
									'dateIn'          => $ret,
									'isReturn'        => 'true',
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
