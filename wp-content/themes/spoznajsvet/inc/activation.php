<?php
/**
 * Po aktivácii témy vytvoríme stránky Destinácie a Letenky (ak ešte neexistujú)
 * a nastavíme statickú úvodnú stránku + stránku s článkami.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

function sst_ensure_page( $title, $slug, $template = '', $content = '' ) {
	$existing = get_page_by_path( $slug );
	if ( $existing ) {
		if ( $template && ! get_page_template_slug( $existing ) ) {
			update_post_meta( $existing->ID, '_wp_page_template', $template );
		}
		return $existing->ID;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		)
	);
	if ( $id && ! is_wp_error( $id ) && $template ) {
		update_post_meta( $id, '_wp_page_template', $template );
	}
	return $id;
}

add_action(
	'after_switch_theme',
	static function () {
		sst_ensure_page( 'Destinácie', 'destinacie', 'page-templates/template-destinacie.php' );
		sst_ensure_page( 'Letenky', 'letenky', 'page-templates/template-letenky.php' );

		if ( 'posts' === get_option( 'show_on_front' ) ) {
			$home = sst_ensure_page( 'Domov', 'domov' );
			$blog = sst_ensure_page( 'Články', 'clanky' );
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $home );
			update_option( 'page_for_posts', $blog );
		}

		// Registrácia taxonómie prebehla na „init“ – obnovíme permalinky.
		flush_rewrite_rules();
	}
);
