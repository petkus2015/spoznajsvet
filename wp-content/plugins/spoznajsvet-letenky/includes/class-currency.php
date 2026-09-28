<?php
defined( 'ABSPATH' ) || exit;

/**
 * Prepočet mien na EUR podľa denných kurzov ECB (napr. Wizz Air z Budapešti vracia HUF).
 */
class SVL_Currency {

	const TRANSIENT = 'svl_ecb_rates';

	/** Záložné kurzy (1 EUR = x), ak ECB nie je dostupná. */
	private static $fallback = array(
		'EUR' => 1.0,
		'HUF' => 395.0,
		'CZK' => 25.0,
		'PLN' => 4.3,
		'GBP' => 0.86,
		'USD' => 1.1,
		'RON' => 5.0,
		'CHF' => 0.94,
		'SEK' => 11.2,
		'NOK' => 11.6,
		'DKK' => 7.46,
		'BGN' => 1.96,
		'AED' => 4.0,
	);

	public static function rates() {
		$rates = get_transient( self::TRANSIENT );
		if ( is_array( $rates ) ) {
			return $rates;
		}
		$rates = self::$fallback;
		$res   = wp_remote_get( 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml', array( 'timeout' => 10 ) );
		if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
			if ( preg_match_all( "/currency='([A-Z]{3})'\s+rate='([0-9.]+)'/", wp_remote_retrieve_body( $res ), $m, PREG_SET_ORDER ) ) {
				foreach ( $m as $row ) {
					$rates[ $row[1] ] = (float) $row[2];
				}
			}
		}
		set_transient( self::TRANSIENT, $rates, 12 * HOUR_IN_SECONDS );
		return $rates;
	}

	/**
	 * @return float|null Suma v EUR alebo null pri neznámej mene.
	 */
	public static function to_eur( $amount, $currency ) {
		$currency = strtoupper( (string) $currency );
		if ( '' === $currency || 'EUR' === $currency ) {
			return (float) $amount;
		}
		$rates = self::rates();
		if ( empty( $rates[ $currency ] ) ) {
			return null;
		}
		return (float) $amount / $rates[ $currency ];
	}
}
