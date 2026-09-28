<?php
/**
 * Prepojenie s pluginom „Spoznaj Svet – Letenky“.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

function sst_flights_active() {
	return function_exists( 'svl_render_deals' );
}

/**
 * Fotky k exotickým destináciám berieme z vašich destinácií (napr. mesto „Bangkok“).
 */
add_filter(
	'svl_destination_image',
	static function ( $url, $iata, $city ) {
		if ( $url ) {
			return $url;
		}
		$term = get_term_by( 'name', $city, 'destinacia' );
		if ( ! $term ) {
			$term = get_term_by( 'slug', sanitize_title( $city ), 'destinacia' );
		}
		if ( ! $term ) {
			$map  = array(
				'BKK' => 'thajsko',
				'DXB' => 'spojene-arabske-emiraty',
				'AUH' => 'spojene-arabske-emiraty',
			);
			$term = isset( $map[ $iata ] ) ? get_term_by( 'slug', $map[ $iata ], 'destinacia' ) : null;
		}
		return $term ? sst_term_cover_url( $term, 'sst-wide' ) : $url;
	},
	10,
	3
);

/** Sekcia s letenkami (alebo výzva na inštaláciu pluginu pre admina). */
function sst_flights( $atts = array() ) {
	if ( sst_flights_active() ) {
		echo svl_render_deals( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}
	if ( current_user_can( 'activate_plugins' ) ) {
		echo '<p class="notice-box">Aktivujte plugin <strong>Spoznaj Svet – Letenky</strong> (Pluginy), aby sa tu zobrazovali aktuálne letenky.</p>';
	}
}

/**
 * Najlacnejšia letenka do krajiny/mesta (pre štítok na stránke destinácie).
 *
 * @return array{price:string,url:string}|null
 */
function sst_cheapest_flight_to( $term ) {
	if ( ! sst_flights_active() || ! class_exists( 'SVL_Store' ) ) {
		return null;
	}
	$iso  = strtoupper( (string) get_term_meta( sst_term_country( $term )->term_id, 'sst_iso', true ) );
	$name = remove_accents( mb_strtolower( $term->name ) );
	$best = null;
	foreach ( array_merge( SVL_Store::deals( 'europa' ), SVL_Store::deals( 'exotika' ) ) as $d ) {
		$city  = remove_accents( mb_strtolower( $d['dest_city'] ) );
		$match = $term->parent ? ( 0 === strpos( $city, $name ) ) : ( $iso && $iso === $d['dest_iso'] );
		if ( $match && ( ! $best || $d['price'] < $best['price'] ) ) {
			$best = $d;
		}
	}
	if ( ! $best ) {
		return null;
	}
	return array(
		'price' => SVL_Format::price( $best['price'] ),
		'url'   => sst_flights_url() ? sst_flights_url() : $best['url'],
	);
}
