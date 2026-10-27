<?php
/**
 * Základné nastavenie témy, menu, obrázky, štýly a skripty.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		load_theme_textdomain( 'spoznajsvet', SST_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 80,
				'width'       => 240,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_editor_style( array( 'assets/css/fonts.css', 'assets/css/editor.css' ) );

		add_image_size( 'sst-card', 720, 900, true );
		add_image_size( 'sst-wide', 1200, 750, true );
		add_image_size( 'sst-hero', 2000, 1200, true );

		register_nav_menus(
			array(
				'primary' => __( 'Hlavné menu (počítač)', 'spoznajsvet' ),
				'bottom'  => __( 'Spodná navigácia (mobil, max. 5 položiek)', 'spoznajsvet' ),
				'footer'  => __( 'Pätička', 'spoznajsvet' ),
			)
		);
	}
);

/** Šírka obsahu pre vložené médiá. */
if ( ! isset( $content_width ) ) {
	$content_width = 760;
}

/**
 * Kombinácie písiem (Vzhľad → Prispôsobiť → Spoznaj Svet → Písmo).
 * Všetky sú pribalené v téme – stránka nevolá Google Fonts (rýchlejšie, bez GDPR starostí).
 */
function sst_font_presets() {
	return array(
		'moderny'    => array(
			'label'        => 'Moderné – Manrope + Instrument Serif (odporúčané)',
			'sans'         => 'Manrope',
			'serif'        => 'Instrument Serif',
			'serif_weight' => 400,
			'serif_scale'  => 1.12,
			'preload'      => array( 'manrope-latin-wght-normal.woff2', 'manrope-latin-ext-wght-normal.woff2' ),
		),
		'priatelsky' => array(
			'label'        => 'Priateľské – Figtree + Fraunces',
			'sans'         => 'Figtree',
			'serif'        => 'Fraunces',
			'serif_weight' => 500,
			'serif_scale'  => 1,
			'preload'      => array( 'figtree-latin-wght-normal.woff2', 'figtree-latin-ext-wght-normal.woff2' ),
		),
		'klasicky'   => array(
			'label'        => 'Pôvodné – Plus Jakarta Sans + Playfair Display',
			'sans'         => 'Plus Jakarta Sans',
			'serif'        => 'Playfair Display',
			'serif_weight' => 500,
			'serif_scale'  => 1,
			'preload'      => array( 'plus-jakarta-sans-latin-wght-normal.woff2', 'plus-jakarta-sans-latin-ext-wght-normal.woff2' ),
		),
	);
}

function sst_font_preset() {
	$presets = sst_font_presets();
	$key     = get_theme_mod( 'sst_font', 'moderny' );
	return $presets[ $key ] ?? $presets['moderny'];
}

function sst_font_stacks() {
	$f = sst_font_preset();
	return array(
		'sans'  => '"' . $f['sans'] . '", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
		'serif' => '"' . $f['serif'] . '", Georgia, "Times New Roman", serif',
	);
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		$stacks = sst_font_stacks();
		wp_enqueue_style( 'sst-fonts', SST_URI . '/assets/css/fonts.css', array(), SST_VERSION );
		wp_enqueue_style( 'sst-theme', SST_URI . '/assets/css/theme.css', array( 'sst-fonts' ), SST_VERSION );
		wp_add_inline_style(
			'sst-theme',
			sprintf(
				':root{--font-sans:%s;--font-serif:%s;--serif-weight:%d;--serif-scale:%s}',
				$stacks['sans'],
				$stacks['serif'],
				(int) sst_font_preset()['serif_weight'],
				(float) sst_font_preset()['serif_scale']
			)
		);
		wp_enqueue_script( 'sst-theme', SST_URI . '/assets/js/theme.js', array(), SST_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
);

/** Prednačítanie hlavného písma – text sa vykreslí hneď, bez preblikávania. */
add_action(
	'wp_head',
	static function () {
		foreach ( sst_font_preset()['preload'] as $file ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( SST_URI . '/assets/fonts/' . $file ) );
		}
	},
	2
);

/** Písma v blokovom editore podľa zvolenej kombinácie (theme.json predvoľby). */
add_filter(
	'wp_theme_json_data_theme',
	static function ( $theme_json ) {
		$stacks = sst_font_stacks();
		$f      = sst_font_preset();
		return $theme_json->update_with(
			array(
				'version'  => 3,
				'settings' => array(
					'typography' => array(
						'fontFamilies' => array(
							array(
								'slug'       => 'sans',
								'name'       => $f['sans'],
								'fontFamily' => $stacks['sans'],
							),
							array(
								'slug'       => 'serif',
								'name'       => $f['serif'],
								'fontFamily' => $stacks['serif'],
							),
						),
					),
				),
			)
		);
	}
);

add_action(
	'wp_head',
	static function () {
		echo '<meta name="theme-color" content="#0e2a30">' . "\n";
	},
	1
);

add_filter(
	'excerpt_length',
	static function () {
		return 22;
	}
);

add_filter(
	'excerpt_more',
	static function () {
		return '…';
	}
);

add_filter(
	'body_class',
	static function ( $classes ) {
		if ( sst_has_hero() ) {
			$classes[] = 'has-hero';
		}
		return $classes;
	}
);

/**
 * Stránky s tmavým hero – hlavička je nad obrázkom priehľadná.
 */
function sst_has_hero() {
	return is_front_page() || is_singular( 'post' ) || is_tax( 'destinacia' ) || is_page_template( 'page-templates/template-letenky.php' ) || is_page_template( 'page-templates/template-destinacie.php' );
}

add_action(
	'pre_get_posts',
	static function ( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( $q->is_tax( 'destinacia' ) || $q->is_search() || $q->is_archive() ) {
			$q->set( 'posts_per_page', 12 );
		}
		if ( $q->is_home() && ! $q->is_search() ) {
			$q->set( 'posts_per_page', 13 ); // 1 zvýraznený + 4 rady po 3.
			$q->set( 'category__not_in', sst_klook_exclude() ); // Automatické tipy majú vlastnú kategóriu.
		}
		if ( $q->is_search() ) {
			$q->set( 'post_type', array( 'post', 'page' ) );
		}
	}
);

/** Nadpis archívu bez prefixu „Kategória:“. */
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

/** Vlajky a emoji vykreslí prehliadač natívne – netreba skript ani obrázky z s.w.org. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
