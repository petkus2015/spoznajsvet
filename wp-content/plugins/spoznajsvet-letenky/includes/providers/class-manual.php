<?php
defined( 'ABSPATH' ) || exit;

/**
 * Ručne pridané tipy (napr. akcia nájdená na Kayak/momondo).
 *
 * Jeden riadok = jedna ponuka:
 * skupina;odkiaľ;kam;odlet;návrat;cena;zdroj;odkaz
 * exotika;VIE;BKK;2026-11-10;2026-11-24;389;momondo;https://www.momondo.com/...
 * europa;BTS;STN;2026-10-20;;14.99;kayak;
 */
class SVL_Provider_Manual extends SVL_Provider {

	public function id() {
		return 'manual';
	}

	public function label() {
		return 'Ručné tipy';
	}

	public function enabled() {
		return '' !== trim( (string) SVL_Settings::get( 'manual' ) );
	}

	public function supports( $group ) {
		return true;
	}

	public function fetch( $group ) {
		$out = array();
		foreach ( self::parse( (string) SVL_Settings::get( 'manual' ) ) as $row ) {
			if ( $row['group'] === $group ) {
				$out[] = SVL_Format::deal( $row );
			}
		}
		return $out;
	}

	public static function parse( $text ) {
		$rows = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $text ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || '#' === $line[0] ) {
				continue;
			}
			$p = array_map( 'trim', explode( ';', $line ) );
			if ( count( $p ) < 6 ) {
				continue;
			}
			$group = strtolower( $p[0] );
			if ( ! in_array( $group, array( 'exotika', 'europa' ), true )
				|| ! preg_match( '/^[A-Z]{3}$/i', $p[1] ) || ! preg_match( '/^[A-Z]{3}$/i', $p[2] )
				|| ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $p[3] ) ) {
				continue;
			}
			$source = strtolower( $p[6] ?? '' );
			$rows[] = array(
				'group'       => $group,
				'source'      => in_array( $source, array( 'kayak', 'momondo', 'ryanair', 'wizzair' ), true ) ? $source : 'manual',
				'origin'      => strtoupper( $p[1] ),
				'destination' => strtoupper( $p[2] ),
				'depart'      => $p[3],
				'return'      => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $p[4] ) ? $p[4] : '',
				'price'       => (float) str_replace( ',', '.', $p[5] ),
				'url'         => isset( $p[7] ) ? esc_url_raw( $p[7] ) : '',
			);
		}
		return $rows;
	}
}
