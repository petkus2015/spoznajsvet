<?php
/**
 * Vzhľad → Prispôsobiť → Spoznaj Svet.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

function sst_mod_defaults() {
	return array(
		'sst_hero_eyebrow'  => 'Najčastejšie sólo · rád aj v partii',
		'sst_hero_title'    => 'Spoznaj',
		'sst_hero_accent'   => 'svet',
		'sst_hero_text'     => 'Batoh, letenka za pár eur a chuť zablúdiť. Väčšinou cestujem sám, no rád vyrazím s kýmkoľvek alebo s malou partiou. Tu nájdeš príbehy, itineráre a tipy z miest, kde som bol, aj to, ako sa tam dostať lacno.',
		'sst_about_title'   => 'Ahoj, som cestovateľ, ktorý sa nebojí vyraziť aj sám',
		'sst_about_text'    => 'Najčastejšie cestujem sólo. Sám si určujem tempo, ľahšie sa dám do reči s miestnymi a zažijem veci, ktoré by som v skupine prehliadol. Nie som však samotár: rád cestujem s kýmkoľvek, kto má chuť objavovať, aj s menšou partiou. Na blogu nájdeš úprimné tipy pre sólo cestovateľov aj skupinky. Koľko čo stojí, kde je bezpečne, kde sa oplatí prespať a ako lacno letieť.',
		'sst_about_tags'    => 'Najčastejšie sólo, Rád aj v partii, Lacné letenky, Overené tipy',
		'sst_footer_text'   => 'Sólo aj v partii. Príbehy, itineráre a lacné letenky zo Slovenska do celého sveta.',
		'sst_home_flights'  => 1,
	);
}

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
			'sst_hero_eyebrow' => array( 'sst_hero', 'Nadtitulok', 'text' ),
			'sst_hero_title'   => array( 'sst_hero', 'Nadpis', 'text' ),
			'sst_hero_accent'  => array( 'sst_hero', 'Nadpis – kurzíva', 'text' ),
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
