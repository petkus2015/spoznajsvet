<?php
/**
 * Pomocné funkcie pre šablóny.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

/** URL prvej stránky s danou šablónou. */
function sst_template_url( $template ) {
	static $cache = array();
	if ( ! array_key_exists( $template, $cache ) ) {
		$pages              = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $template, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$cache[ $template ] = $pages ? get_permalink( $pages[0] ) : '';
	}
	return $cache[ $template ];
}

function sst_destinations_url() {
	$url = sst_template_url( 'page-templates/template-destinacie.php' );
	return $url ? $url : home_url( '/destinacie/' );
}

function sst_flights_url() {
	return sst_template_url( 'page-templates/template-letenky.php' );
}

function sst_blog_url() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : home_url( '/' );
}

/**
 * Položky spodnej navigácie. Ak je priradené menu „Spodná navigácia“, použije sa to
 * (ikonu zvolíte CSS triedou položky: ikona-home, ikona-globe, ikona-plane, ikona-book, ikona-search, ikona-user…).
 *
 * @return array[] { label, url, icon, active, search }
 */
function sst_bottom_nav_items() {
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['bottom'] ) ) {
		$items = wp_get_nav_menu_items( $locations['bottom'] );
		if ( $items ) {
			$out = array();
			foreach ( array_slice( $items, 0, 5 ) as $item ) {
				$icon = 'globe';
				foreach ( (array) $item->classes as $class ) {
					if ( 0 === strpos( $class, 'ikona-' ) ) {
						$icon = substr( $class, 6 );
					}
				}
				$out[] = array(
					'label'  => $item->title,
					'url'    => $item->url,
					'icon'   => $icon,
					'active' => untrailingslashit( $item->url ) === untrailingslashit( home_url( add_query_arg( array() ) ) ),
					'search' => 'search' === $icon,
				);
			}
			return $out;
		}
	}

	$flights = sst_flights_url();
	$items   = array(
		array(
			'label'  => 'Domov',
			'url'    => home_url( '/' ),
			'icon'   => 'home',
			'active' => is_front_page(),
		),
		array(
			'label'  => 'Destinácie',
			'url'    => sst_destinations_url(),
			'icon'   => 'globe',
			'active' => is_tax( 'destinacia' ) || is_page_template( 'page-templates/template-destinacie.php' ),
		),
	);
	if ( $flights ) {
		$items[] = array(
			'label'   => 'Letenky',
			'url'     => $flights,
			'icon'    => 'plane',
			'active'  => is_page_template( 'page-templates/template-letenky.php' ),
			'feature' => true,
		);
	}
	$items[] = array(
		'label'  => 'Články',
		'url'    => sst_blog_url(),
		'icon'   => 'book',
		'active' => is_home() || is_singular( 'post' ) || is_category() || is_tag(),
	);
	$items[] = array(
		'label'  => 'Hľadať',
		'url'    => home_url( '/?s=' ),
		'icon'   => 'search',
		'active' => is_search(),
		'search' => true,
	);
	return $items;
}

/** Hlavné menu, ak nie je nastavené vlastné. */
function sst_primary_fallback( $args = array() ) {
	$context = is_array( $args ) && isset( $args['sst_context'] ) ? $args['sst_context'] : 'footer';
	$items   = array(
		array( 'Destinácie', sst_destinations_url(), is_tax( 'destinacia' ) || is_page_template( 'page-templates/template-destinacie.php' ), 'destinations' ),
		array( 'Články', sst_blog_url(), is_home() || is_singular( 'post' ) ),
	);
	// Na počítači je „Lacné letenky“ samostatné tlačidlo – v menu by bolo dvakrát.
	if ( sst_flights_url() && 'desktop' !== $context ) {
		$items[] = array( 'Letenky', sst_flights_url(), is_page_template( 'page-templates/template-letenky.php' ) );
	}
	$about = get_page_by_path( 'o-mne' );
	if ( $about ) {
		$items[] = array( 'O mne', get_permalink( $about ), is_page( $about->ID ) );
	}
	echo '<ul class="menu">';
	foreach ( $items as $i ) {
		$sub = '';
		if ( 'desktop' === $context && isset( $i[3] ) ) {
			$countries = array_slice( sst_countries( array( 'orderby' => 'count', 'order' => 'DESC' ) ), 0, 10 );
			if ( $countries ) {
				$sub = '<ul class="sub-menu sub-menu--grid">';
				foreach ( $countries as $c ) {
					$sub .= sprintf(
						'<li class="menu-item"><a href="%s"><span class="sub-menu__flag" aria-hidden="true">%s</span>%s<span class="sub-menu__count">%d</span></a></li>',
						esc_url( get_term_link( $c ) ),
						esc_html( sst_term_flag( $c ) ),
						esc_html( $c->name ),
						(int) $c->count
					);
				}
				$sub .= sprintf( '<li class="menu-item sub-menu__all"><a href="%s">Všetky destinácie →</a></li></ul>', esc_url( sst_destinations_url() ) );
			}
		}
		printf(
			'<li class="menu-item%3$s%5$s"><a href="%1$s"%4$s>%2$s</a>%6$s</li>',
			esc_url( $i[1] ),
			esc_html( $i[0] ),
			$i[2] ? ' current-menu-item' : '',
			$i[2] ? ' aria-current="page"' : '',
			$sub ? ' menu-item-has-children' : '',
			$sub // Escapované vyššie.
		);
	}
	echo '</ul>';
}

/** Omrvinky pre destinácie a články. */
function sst_breadcrumbs( $term = null ) {
	$crumbs = array( array( 'Destinácie', sst_destinations_url() ) );
	if ( $term ) {
		foreach ( array_reverse( get_ancestors( $term->term_id, 'destinacia', 'taxonomy' ) ) as $id ) {
			$t        = get_term( $id, 'destinacia' );
			$crumbs[] = array( $t->name, get_term_link( $t ) );
		}
		if ( ! is_tax( 'destinacia', $term->term_id ) ) {
			$crumbs[] = array( $term->name, get_term_link( $term ) );
		}
	}
	echo '<nav class="crumbs" aria-label="Omrvinky"><ol>';
	foreach ( $crumbs as $c ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $c[1] ), esc_html( $c[0] ) );
	}
	echo '</ol></nav>';
}

/** Sociálne siete z prispôsobenia. */
function sst_social_links( $class = 'social' ) {
	$nets = array(
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'youtube'   => 'YouTube',
		'tiktok'    => 'TikTok',
	);
	$out  = '';
	foreach ( $nets as $key => $label ) {
		$url = get_theme_mod( 'sst_' . $key );
		if ( $url ) {
			$out .= sprintf( '<a href="%s" target="_blank" rel="noopener me" aria-label="%s">%s</a>', esc_url( $url ), esc_attr( $label ), sst_icon( $key, 20 ) );
		}
	}
	$mail = get_theme_mod( 'sst_email' );
	if ( $mail ) {
		$out .= sprintf( '<a href="mailto:%s" aria-label="E-mail">%s</a>', esc_attr( antispambot( $mail ) ), sst_icon( 'mail', 20 ) );
	}
	if ( $out ) {
		echo '<div class="' . esc_attr( $class ) . '">' . $out . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * Obrázok článku s rozumným fallbackom.
 */
function sst_post_image( $size = 'sst-card', $attr = array() ) {
	if ( has_post_thumbnail() ) {
		the_post_thumbnail( $size, array_merge( array( 'loading' => 'lazy', 'decoding' => 'async' ), $attr ) );
		return;
	}
	$places = sst_post_places();
	$url    = $places['country'] ? sst_term_cover_url( $places['country'], $size ) : '';
	if ( $url ) {
		printf( '<img src="%s" alt="" loading="lazy" decoding="async">', esc_url( $url ) );
	} else {
		echo '<span class="img-placeholder" aria-hidden="true">' . sst_icon( 'compass', 48 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/** Hero obrázok úvodnej stránky. */
function sst_hero_image_url() {
	$id = (int) get_theme_mod( 'sst_hero_image' );
	if ( $id ) {
		$url = wp_get_attachment_image_url( $id, 'sst-hero' );
		if ( $url ) {
			return $url;
		}
	}
	$sticky = get_option( 'sticky_posts' );
	$posts  = get_posts(
		array(
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'post__in'            => $sticky ? $sticky : null,
			'ignore_sticky_posts' => true,
			'meta_key'            => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $posts ? (string) get_the_post_thumbnail_url( $posts[0], 'sst-hero' ) : '';
}

/** Stránkovanie. */
function sst_pagination() {
	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => sst_icon( 'arrow-left', 18 ) . '<span class="screen-reader-text">Predchádzajúce</span>',
			'next_text'          => '<span class="screen-reader-text">Ďalšie</span>' . sst_icon( 'arrow-right', 18 ),
			'screen_reader_text' => 'Stránkovanie',
		)
	);
}
