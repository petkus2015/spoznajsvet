<?php
/**
 * Vzhľad → Prispôsobiť → Spoznaj Svet.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

function sst_mod_defaults() {
	return array(
		'sst_hero_title'    => 'Spoznaj',
		'sst_hero_accent'   => 'svet',
		'sst_hero_text'     => 'Píšem o miestach, kde som bol: čo tam robiť, kde spať, koľko to stálo a ako sa tam dostať lacno. Väčšinou cestujem sám, občas so squadom.',
		'sst_about_title'   => 'Ahoj, som Petrém (Peter Miškus), sólo/squad cestovateľ, videografer a social media manager',
		'sst_about_text'    => 'Natáčam videá, spravujem sociálne siete a keď sa dá, balím batoh. Najčastejšie cestujem sám. Tak najlepšie vypnem, naberiem skúsenosti a zistím, čo zvládnem mimo komfortnej zóny, keď je každá situácia len na mne. A mesto spoznám tak, ako v ňom žijú miestni. Rád však vyrazím aj so squadom. Na blogu píšem, koľko čo stálo, kde sa oplatilo spať a ako som letel lacno.',
		'sst_about_tags'    => 'Sólo, Squad, Lacné letenky, Video',
		'sst_footer_text'   => 'Cestovateľský blog Petra Miškusa. Články, itineráre a lacné letenky zo Slovenska.',
		'sst_home_flights'  => 1,
	);
}

// Pôvodný nadpis „O mne“ uložený v Prispôsobení nahradíme novým predvoleným.
add_action(
	'after_setup_theme',
	static function () {
		if ( 'Ahoj, som cestovateľ, ktorý sa nebojí vyraziť aj sám' === get_theme_mod( 'sst_about_title' ) ) {
			remove_theme_mod( 'sst_about_title' );
		}
		// Starší predvolený text „O mne“ uložený v Prispôsobení nahradíme novým.
		$about = (string) get_theme_mod( 'sst_about_text', '' );
		if ( 0 === strpos( $about, 'Najčastejšie cestujem sólo.' ) || 0 === strpos( $about, 'Natáčam videá a spravujem sociálne siete, a keď sa dá, cestujem.' ) ) {
			remove_theme_mod( 'sst_about_text' );
		}
	}
);

function sst_mod( $key ) {
	$d = sst_mod_defaults();
	return get_theme_mod( $key, $d[ $key ] ?? '' );
}

add_action(
	'customize_register',
	static function ( WP_Customize_Manager $wp ) {
		$wp->add_panel(
			'sst',
			array(
				'title'    => 'Spoznaj Svet',
				'priority' => 30,
			)
		);

		$sections = array(
			'sst_typo'   => 'Písmo',
			'sst_hero'   => 'Úvodný banner',
			'sst_about'  => 'O mne',
			'sst_social' => 'Sociálne siete',
			'sst_footer' => 'Pätička a letenky',
		);
		foreach ( $sections as $id => $title ) {
			$wp->add_section(
				$id,
				array(
					'title' => $title,
					'panel' => 'sst',
				)
			);
		}

		$text = array(
			'sst_hero_title'   => array( 'sst_hero', 'Nadpis', 'text' ),
			'sst_hero_accent'  => array( 'sst_hero', 'Nadpis – druhé slovo', 'text' ),
			'sst_hero_text'    => array( 'sst_hero', 'Text', 'textarea' ),
			'sst_about_title'  => array( 'sst_about', 'Nadpis', 'text' ),
			'sst_about_text'   => array( 'sst_about', 'Text', 'textarea' ),
			'sst_about_tags'   => array( 'sst_about', 'Štítky (oddelené čiarkou)', 'text' ),
			'sst_instagram'    => array( 'sst_social', 'Instagram URL', 'url' ),
			'sst_facebook'     => array( 'sst_social', 'Facebook URL', 'url' ),
			'sst_youtube'      => array( 'sst_social', 'YouTube URL', 'url' ),
			'sst_tiktok'       => array( 'sst_social', 'TikTok URL', 'url' ),
			'sst_email'        => array( 'sst_social', 'E-mail', 'email' ),
			'sst_footer_text'  => array( 'sst_footer', 'Text v pätičke', 'textarea' ),
		);
		$defaults = sst_mod_defaults();
		foreach ( $text as $key => $c ) {
			$sanitize = 'url' === $c[2] ? 'esc_url_raw' : ( 'email' === $c[2] ? 'sanitize_email' : ( 'textarea' === $c[2] ? 'sanitize_textarea_field' : 'sanitize_text_field' ) );
			$wp->add_setting(
				$key,
				array(
					'default'           => $defaults[ $key ] ?? '',
					'sanitize_callback' => $sanitize,
				)
			);
			$wp->add_control(
				$key,
				array(
					'section' => $c[0],
					'label'   => $c[1],
					'type'    => $c[2],
				)
			);
		}

		foreach ( array(
			'sst_hero_image'  => array( 'sst_hero', 'Obrázok (inak sa použije pripnutý/najnovší článok)' ),
			'sst_about_photo' => array( 'sst_about', 'Fotka' ),
		) as $key => $c ) {
			$wp->add_setting( $key, array( 'sanitize_callback' => 'absint' ) );
			$wp->add_control(
				new WP_Customize_Media_Control(
					$wp,
					$key,
					array(
						'section'   => $c[0],
						'label'     => $c[1],
						'mime_type' => 'image',
					)
				)
			);
		}

		$wp->add_setting(
			'sst_font',
			array(
				'default'           => 'moderny',
				'sanitize_callback' => static function ( $v ) {
					return array_key_exists( $v, sst_font_presets() ) ? $v : 'moderny';
				},
			)
		);
		$wp->add_control(
			'sst_font',
			array(
				'section'     => 'sst_typo',
				'label'       => 'Kombinácia písiem',
				'description' => 'Písma sú uložené priamo v téme, takže sa nenačítavajú z Google.',
				'type'        => 'radio',
				'choices'     => wp_list_pluck( sst_font_presets(), 'label' ),
			)
		);

		$wp->add_setting(
			'sst_home_flights',
			array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			)
		);
		$wp->add_control(
			'sst_home_flights',
			array(
				'section' => 'sst_footer',
				'label'   => 'Zobraziť letenky na úvodnej stránke',
				'type'    => 'checkbox',
			)
		);
	}
);
