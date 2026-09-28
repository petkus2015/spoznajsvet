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
		add_editor_style( 'assets/css/editor.css' );

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

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( apply_filters( 'sst_google_fonts', true ) ) {
			wp_enqueue_style(
				'sst-fonts',
				'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;1,500&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap',
				array(),
				null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			);
		}
		wp_enqueue_style( 'sst-theme', SST_URI . '/assets/css/theme.css', array(), SST_VERSION );
		wp_enqueue_script( 'sst-theme', SST_URI . '/assets/js/theme.js', array(), SST_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
);

add_filter(
	'wp_resource_hints',
	static function ( $urls, $relation ) {
		if ( 'preconnect' === $relation && apply_filters( 'sst_google_fonts', true ) ) {
			$urls[] = 'https://fonts.googleapis.com';
			$urls[] = array(
				'href'        => 'https://fonts.gstatic.com',
				'crossorigin' => 'anonymous',
			);
		}
		return $urls;
	},
	10,
	2
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
		if ( $q->is_tax( 'destinacia' ) || $q->is_home() || $q->is_search() || $q->is_archive() ) {
			$q->set( 'posts_per_page', 12 );
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
