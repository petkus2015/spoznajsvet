<?php
defined( 'ABSPATH' ) || exit;

/**
 * WP-CLI: wp letenky refresh | wp letenky status
 */
class SVL_CLI {

	/**
	 * Okamžite stiahne nové ceny.
	 */
	public function refresh() {
		$data = SVL_Store::refresh( true );
		$this->print_status( $data );
		WP_CLI::success( count( $data['deals'] ) . ' ponúk uložených.' );
	}

	/**
	 * Zobrazí stav poskytovateľov.
	 */
	public function status() {
		$data = SVL_Store::data();
		WP_CLI::log( 'Posledná aktualizácia: ' . ( $data['updated'] ? gmdate( 'Y-m-d H:i', $data['updated'] ) . ' UTC' : 'nikdy' ) );
		$this->print_status( $data );
	}

	private function print_status( $data ) {
		$rows = array();
		foreach ( $data['status'] as $key => $s ) {
			$rows[] = array(
				'zdroj'  => $key,
				'stav'   => $s['ok'] ? 'OK' : 'CHYBA',
				'ponuky' => $s['count'],
				'správa' => $s['msg'],
			);
		}
		if ( $rows ) {
			WP_CLI\Utils\format_items( 'table', $rows, array( 'zdroj', 'stav', 'ponuky', 'správa' ) );
		}
	}
}
