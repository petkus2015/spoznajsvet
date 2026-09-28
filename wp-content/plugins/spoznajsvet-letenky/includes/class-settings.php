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
			'europe_origins'      => 'BTS,VIE',
			'europe_days'         => 90,
			'europe_max_price'    => 150,
			'europe_only'         => 1,
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

	public static function sanitize( $input ) {
		$d   = self::defaults();
		$out = array();
		foreach ( array( 'ryanair', 'wizzair', 'travelpayouts', 'europe_only' ) as $k ) {
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
		$out['europe_days']      = max( 7, min( 180, (int) ( $input['europe_days'] ?? $d['europe_days'] ) ) );
		$out['europe_max_price'] = max( 0, (int) ( $input['europe_max_price'] ?? $d['europe_max_price'] ) );
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
