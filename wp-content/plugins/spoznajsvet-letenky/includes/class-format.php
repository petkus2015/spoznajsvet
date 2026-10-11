<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pomocné funkcie: normalizácia ponuky, formát ceny/dátumu, odkazy na porovnávače.
 */
class SVL_Format {

	/**
	 * Zjednotí ponuku od poskytovateľa do spoločného tvaru.
	 */
	public static function deal( array $d ) {
		$origin = strtoupper( $d['origin'] );
		$dest   = strtoupper( $d['destination'] );
		$o      = svl_airport( $origin, $d['origin_city'] ?? '', $d['origin_iso'] ?? '' );
		$t      = svl_airport( $dest, $d['dest_city'] ?? '', $d['dest_iso'] ?? '' );
		$return = empty( $d['return'] ) ? '' : substr( $d['return'], 0, 10 );

		return array(
			'group'        => $d['group'],
			'source'       => $d['source'],
			'origin'       => $origin,
			'origin_city'  => $o['city'],
			'destination'  => $dest,
			'dest_city'    => $t['city'],
			'dest_iso'     => $t['iso'],
			'dest_country' => $t['country'] ? $t['country'] : ( $d['dest_country'] ?? '' ),
			'continent'    => $t['continent'],
			'depart'       => substr( $d['depart'], 0, 10 ),
			'return'       => $return,
			'price'        => round( (float) $d['price'], 2 ),
			'currency'     => 'EUR',
			'stops'        => isset( $d['stops'] ) ? (int) $d['stops'] : null,
			'airline'      => $d['airline'] ?? '',
			'airline_code' => strtoupper( (string) ( $d['airline_code'] ?? '' ) ),
			'url'          => $d['url'] ?? '',
			'fetched'      => isset( $d['fetched'] ) ? (int) $d['fetched'] : time(),
			'low'          => false,
		);
	}

	/** Logo aerolinky (verejné CDN Aviasales podľa IATA kódu). */
	public static function airline_logo( $code, $size = 28 ) {
		$code = strtoupper( (string) $code );
		if ( ! preg_match( '/^[A-Z0-9]{2}$/', $code ) ) {
			return '';
		}
		return sprintf(
			'<img class="svl-airline__logo" src="https://pics.avs.io/%1$d/%1$d/%2$s@2x.png" width="%3$d" height="%3$d" alt="" loading="lazy" decoding="async" onerror="this.remove()">',
			(int) $size * 2,
			esc_attr( $code ),
			(int) $size
		);
	}

	public static function price( $value ) {
		$value = (float) $value;
		$dec   = ( floor( $value ) == $value ) ? 0 : 2; // phpcs:ignore WordPress.PHP.StrictComparisons
		return number_format( $value, $dec, ',', "\u{00A0}" ) . "\u{00A0}€";
	}

	/** „st 12. 11.“ */
	public static function date( $ymd, $with_day = true ) {
		$ts = strtotime( $ymd . ' 12:00:00 UTC' );
		if ( ! $ts ) {
			return '';
		}
		$days = array( 'ne', 'po', 'ut', 'st', 'št', 'pi', 'so' );
		$out  = gmdate( 'j. n.', $ts );
		return $with_day ? $days[ (int) gmdate( 'w', $ts ) ] . ' ' . $out : $out;
	}

	public static function nights( $from, $to ) {
		if ( ! $from || ! $to ) {
			return 0;
		}
		return (int) round( ( strtotime( $to ) - strtotime( $from ) ) / DAY_IN_SECONDS );
	}

	public static function plural( $n, $one, $few, $many ) {
		$n = abs( (int) $n );
		if ( 1 === $n ) {
			return $one;
		}
		return ( $n >= 2 && $n <= 4 ) ? $few : $many;
	}

	public static function stops( $stops ) {
		if ( null === $stops ) {
			return '';
		}
		if ( 0 === $stops ) {
			return 'priamy let';
		}
		return $stops . ' ' . self::plural( $stops, 'prestup', 'prestupy', 'prestupov' );
	}

	/** Emoji vlajka z ISO kódu. */
	public static function flag( $iso ) {
		$iso = strtoupper( (string) $iso );
		if ( ! preg_match( '/^[A-Z]{2}$/', $iso ) ) {
			return '🌍';
		}
		return mb_chr( 0x1F1E6 + ord( $iso[0] ) - 65 ) . mb_chr( 0x1F1E6 + ord( $iso[1] ) - 65 );
	}

	public static function source_label( $source ) {
		$labels = array(
			'ryanair'       => 'Ryanair',
			'wizzair'       => 'Wizz Air',
			'travelpayouts' => 'Aviasales',
			'kayak'         => 'Kayak',
			'momondo'       => 'momondo',
			'manual'        => 'Tip',
		);
		return $labels[ $source ] ?? ucfirst( (string) $source );
	}

	/**
	 * Odkazy na rovnaké vyhľadávanie v porovnávačoch Kayak a momondo.
	 */
	public static function compare_links( $origin, $dest, $depart, $return = '' ) {
		$path = rawurlencode( $origin ) . '-' . rawurlencode( $dest ) . '/' . $depart . ( $return ? '/' . $return : '' );
		return array(
			'kayak'   => 'https://' . SVL_Settings::get( 'kayak_domain' ) . '/flights/' . $path . '?sort=price_a',
			'momondo' => 'https://' . SVL_Settings::get( 'momondo_domain' ) . '/flight-search/' . $path . '?sort=price_a',
		);
	}

	/** Relatívny čas v slovenčine. */
	public static function ago( $ts ) {
		$min = (int) floor( ( time() - (int) $ts ) / 60 );
		if ( $min < 1 ) {
			return 'práve teraz';
		}
		if ( $min < 60 ) {
			return 'pred ' . $min . ' min';
		}
		$h = (int) floor( $min / 60 );
		if ( $h < 24 ) {
			return 'pred ' . $h . ' h';
		}
		$d = (int) floor( $h / 24 );
		return 'pred ' . $d . ' ' . self::plural( $d, 'dňom', 'dňami', 'dňami' );
	}
}
