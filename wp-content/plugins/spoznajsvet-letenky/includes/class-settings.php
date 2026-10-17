<?php
defined( 'ABSPATH' ) || exit;

/**
 * Nastavenia pluginu (Nastavenia → Letenky).
 */
class SVL_Settings {

	const OPTION = 'svl_settings';

	public static function defaults() {
		return array(
			'ryanair'             => 1,
			'wizzair'             => 1,
			'travelpayouts'       => 1,
			'tp_token'            => '',
			'tp_marker'           => '',
			'exotic_origins'      => 'VIE,BTS,BUD',
			'exotic_destinations' => 'BKK,DXB,AUH',
			'exotic_months'       => 6,
			'exotic_min_nights'   => 5,
			'exotic_max_nights'   => 21,
			// Oblasti rebríčka Exotika: as = Ázia a Perzský záliv, ex = tropy, af = Egypt/Maroko/Tunisko, me = Blízky východ a Kaukaz.
			'exotic_regions'      => 'as,ex,af',
			'europe_origins'      => 'BTS,VIE',
			'europe_days'         => 90,
			'europe_max_price'    => 150,
			'europe_min_nights'   => 2,
			'europe_max_nights'   => 7,
			'europe_only'         => 1,
			'weather'             => 1,
			'kayak_domain'        => 'www.kayak.com',
			'momondo_domain'      => 'www.momondo.com',
			'manual'              => '',
		);
	}

	public static function all() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Zoznam IATA kódov z textového poľa „VIE, BTS“.
	 */
	public static function codes( $key ) {
		$codes = array_map( 'trim', explode( ',', strtoupper( (string) self::get( $key ) ) ) );
		return array_values( array_unique( array_filter( $codes, static function ( $c ) {
			return (bool) preg_match( '/^[A-Z]{3}$/', $c );
		} ) ) );
	}

	/** Oblasti, ktoré môže obsahovať rebríček Ázie a exotiky. */
	public static function regions() {
		return array(
			'as' => 'Ázia a Perzský záliv (Bangkok, Bali, Dubaj, Kazachstan, Saudská Arábia…)',
			'ex' => 'Tropická exotika (Maldivy, Maurícius, Zanzibar, Karibik…)',
			'af' => 'Severná Afrika (Egypt, Maroko, Tunisko) – sem lieta aj Ryanair',
			'me' => 'Blízky východ a Kaukaz (Jordánsko, Izrael, Gruzínsko, Arménsko)',
		);
	}

	/**
	 * Najnavštevovanejšie mestá Európy (podľa počtu zahraničných návštevníkov) a ich letiská.
	 * Doplniť / zmeniť sa dajú filtrom `svl_top_cities`.
	 *
	 * @return array<string, array{0:string,1:string,2:string[]}> kľúč => [názov, kód krajiny, letiská a mestské kódy]
	 */
	public static function top_cities() {
		return apply_filters(
			'svl_top_cities',
			array(
				'londyn'     => array( 'Londýn', 'GB', array( 'LON', 'STN', 'LTN', 'LGW', 'LHR', 'SEN', 'LCY' ) ),
				'pariz'      => array( 'Paríž', 'FR', array( 'PAR', 'CDG', 'ORY', 'BVA' ) ),
				'istanbul'   => array( 'Istanbul', 'TR', array( 'IST', 'SAW' ) ),
				'rim'        => array( 'Rím', 'IT', array( 'ROM', 'FCO', 'CIA' ) ),
				'barcelona'  => array( 'Barcelona', 'ES', array( 'BCN', 'GRO', 'REU' ) ),
				'amsterdam'  => array( 'Amsterdam', 'NL', array( 'AMS', 'EIN' ) ),
				'milano'     => array( 'Miláno', 'IT', array( 'MIL', 'MXP', 'BGY', 'LIN' ) ),
				'madrid'     => array( 'Madrid', 'ES', array( 'MAD' ) ),
				'ateny'      => array( 'Atény', 'GR', array( 'ATH' ) ),
				'dublin'     => array( 'Dublin', 'IE', array( 'DUB' ) ),
				'lisabon'    => array( 'Lisabon', 'PT', array( 'LIS' ) ),
				'benatky'    => array( 'Benátky', 'IT', array( 'VCE', 'TSF' ) ),
				'berlin'     => array( 'Berlín', 'DE', array( 'BER' ) ),
				'florencia'  => array( 'Florencia / Pisa', 'IT', array( 'FLR', 'PSA' ) ),
				'kodan'      => array( 'Kodaň', 'DK', array( 'CPH' ) ),
				'edinburgh'  => array( 'Edinburgh', 'GB', array( 'EDI' ) ),
				'brusel'     => array( 'Brusel', 'BE', array( 'BRU', 'CRL' ) ),
				'nizza'      => array( 'Nice', 'FR', array( 'NCE' ) ),
				'neapol'     => array( 'Neapol', 'IT', array( 'NAP' ) ),
				'porto'      => array( 'Porto', 'PT', array( 'OPO' ) ),
				'sevilla'    => array( 'Sevilla', 'ES', array( 'SVQ' ) ),
				'malaga'     => array( 'Málaga', 'ES', array( 'AGP' ) ),
				'mallorca'   => array( 'Mallorca', 'ES', array( 'PMI' ) ),
				'stockholm'  => array( 'Štokholm', 'SE', array( 'STO', 'ARN', 'NYO', 'BMA' ) ),
			)
		);
	}

	/** Nízkonákladové letecké spoločnosti (IATA kódy) – v obľúbených mestách majú prednosť. */
	public static function lowcost_airlines() {
		return apply_filters(
			'svl_lowcost_airlines',
			array( 'FR', 'RK', 'AL', 'W6', 'W4', 'W9', '5W', 'U2', 'EC', 'DS', 'VY', 'V7', 'HV', 'TO', 'EW', 'LS', 'PC', 'DY', 'D8', 'XQ', 'FZ', 'G9', 'J9' )
		);
	}

	/** Zapnuté oblasti rebríčka Exotika. */
	public static function exotic_regions() {
		return array_values( array_filter( explode( ',', (string) self::get( 'exotic_regions' ) ) ) );
	}

	public static function sanitize( $input ) {
		$d   = self::defaults();
		$out = array();
		foreach ( array( 'ryanair', 'wizzair', 'travelpayouts', 'europe_only', 'weather' ) as $k ) {
			$out[ $k ] = empty( $input[ $k ] ) ? 0 : 1;
		}
		foreach ( array( 'exotic_origins', 'exotic_destinations', 'europe_origins' ) as $k ) {
			$codes     = preg_split( '/[\s,;]+/', strtoupper( (string) ( $input[ $k ] ?? '' ) ) );
			$codes     = array_filter( $codes, static function ( $c ) {
				return (bool) preg_match( '/^[A-Z]{3}$/', $c );
			} );
			$out[ $k ] = $codes ? implode( ',', array_unique( $codes ) ) : $d[ $k ];
		}
		$out['exotic_months']    = max( 1, min( 12, (int) ( $input['exotic_months'] ?? $d['exotic_months'] ) ) );
		$out['exotic_min_nights'] = max( 1, min( 30, (int) ( $input['exotic_min_nights'] ?? $d['exotic_min_nights'] ) ) );
		$out['exotic_max_nights'] = max( $out['exotic_min_nights'], min( 30, (int) ( $input['exotic_max_nights'] ?? $d['exotic_max_nights'] ) ) );
		$regions                  = array_intersect( (array) ( $input['exotic_regions'] ?? array() ), array_keys( self::regions() ) );
		$out['exotic_regions']    = $regions ? implode( ',', $regions ) : 'as,ex';
		$out['europe_days']      = max( 7, min( 180, (int) ( $input['europe_days'] ?? $d['europe_days'] ) ) );
		$out['europe_max_price'] = max( 0, (int) ( $input['europe_max_price'] ?? $d['europe_max_price'] ) );
		$out['europe_min_nights'] = max( 1, min( 21, (int) ( $input['europe_min_nights'] ?? $d['europe_min_nights'] ) ) );
		$out['europe_max_nights'] = max( $out['europe_min_nights'], min( 30, (int) ( $input['europe_max_nights'] ?? $d['europe_max_nights'] ) ) );
		$out['tp_token']         = sanitize_text_field( $input['tp_token'] ?? '' );
		$out['tp_marker']        = preg_replace( '/[^0-9A-Za-z_.-]/', '', (string) ( $input['tp_marker'] ?? '' ) );
		foreach ( array( 'kayak_domain', 'momondo_domain' ) as $k ) {
			$host      = preg_replace( '#^https?://#', '', trim( (string) ( $input[ $k ] ?? '' ) ) );
			$host      = preg_replace( '/[^a-z0-9.-]/i', '', $host );
			$out[ $k ] = $host ? strtolower( $host ) : $d[ $k ];
		}
		$out['manual'] = sanitize_textarea_field( $input['manual'] ?? '' );
		return $out;
	}
}
