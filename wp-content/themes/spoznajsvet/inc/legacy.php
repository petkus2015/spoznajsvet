<?php
/**
 * Prechod zo starej témy: kód stránkových editorov (tagDiv Composer z témy Newspaper, WPBakery)
 * bez pôvodnej témy nefunguje a WordPress by ho vypísal ako text, napr. [tdc_zone type="tdc_content"].
 *
 * - na webe sa takýto kód nikdy nezobrazí (text vnútri ostane),
 * - v Stránkach admin uvidí, ktoré stránky ho obsahujú, a jedným klikom ho vyčistí (pôvodný obsah ostane v revíziách),
 * - stránky Destinácie a Letenky dostanú šablónu témy, aj keď mali šablónu zo starej témy.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

/** Odstráni značky stránkových editorov, ktoré nemajú zaregistrovaný shortcode. */
function sst_strip_builder( $content ) {
	if ( false === strpos( (string) $content, '[' ) ) {
		return $content;
	}
	$content = preg_replace_callback(
		'/\[\/?((?:tdc|td|tdm|tds|vc)_[a-z0-9_]*)(?:\s[^\]]*)?\]/i',
		static function ( $m ) {
			return shortcode_exists( $m[1] ) ? $m[0] : '';
		},
		(string) $content
	);
	return $content;
}
add_filter( 'the_content', 'sst_strip_builder', 5 );

/** Má stránka po odstránení starého kódu nejaký skutočný obsah? */
function sst_has_page_content() {
	$c = sst_strip_builder( get_the_content() );
	return '' !== trim( wp_strip_all_tags( $c ) ) || false !== strpos( $c, '<img' ) || false !== strpos( $c, '<!-- wp:' );
}

function sst_has_builder_code( $content ) {
	return (bool) preg_match( '/\[\/?(?:tdc|td|tdm|tds|vc)_[a-z0-9_]*/i', (string) $content );
}

/** Po aktualizácii témy (nahratí nového ZIP-u) skontrolujeme šablóny stránok – aktivácia sa vtedy nespúšťa. */
add_action(
	'admin_init',
	static function () {
		if ( get_option( 'sst_setup' ) === SST_VERSION || ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		sst_ensure_page( 'Destinácie', 'destinacie', 'page-templates/template-destinacie.php' );
		sst_ensure_page( 'Letenky', 'letenky', 'page-templates/template-letenky.php' );
		update_option( 'sst_setup', SST_VERSION );
	}
);

/** Stránky so starým kódom tagDiv / WPBakery. */
function sst_builder_pages() {
	global $wpdb;
	return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type IN ('page','post') AND post_status IN ('publish','draft','private','future') AND (post_content LIKE '%[tdc\\_zone%' OR post_content LIKE '%[vc\\_row%' OR post_content LIKE '%[td\\_block%' OR post_content LIKE '%[tdm\\_%') ORDER BY post_type, post_title LIMIT 50"
	);
}

add_action(
	'admin_notices',
	static function () {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'edit-page', 'dashboard', 'page', 'edit-post' ), true ) || ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		if ( isset( $_GET['sst_cleaned'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>Starý kód bol odstránený. Pôvodný obsah nájdeš v revíziách stránky.</p></div>';
		}
		if ( 'page' === $screen->id ) {
			$post = get_post();
			if ( ! $post || ! sst_has_builder_code( $post->post_content ) ) {
				return;
			}
			$pages = array( $post );
		} else {
			$pages = sst_builder_pages();
		}
		if ( ! $pages ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>Starý obsah z témy Newspaper (tagDiv Composer).</strong> Kód ako <code>[tdc_zone type="tdc_content"…]</code> funguje len s pôvodnou témou, s témou Spoznaj Svet sa rozloženie nezobrazí – na webe ho téma skrýva. Rozloženie stránok Domov, Destinácie, Letenky a Články robí téma sama, obsah týchto stránok netreba. Starý kód môžeš odstrániť (text v ňom ostane, pôvodný obsah sa uloží do revízií):</p><ul style="list-style:disc;margin-left:2em">';
		foreach ( $pages as $p ) {
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=sst_clean_builder&post=' . (int) $p->ID ), 'sst_clean_' . (int) $p->ID );
			printf(
				'<li><a href="%1$s">%2$s</a> – <a class="button button-small" href="%3$s">Vyčistiť starý kód</a></li>',
				esc_url( get_edit_post_link( $p->ID ) ),
				esc_html( $p->post_title ? $p->post_title : '(bez názvu)' ),
				esc_url( $url )
			);
		}
		echo '</ul></div>';
	}
);

add_action(
	'admin_post_sst_clean_builder',
	static function () {
		$id = absint( $_GET['post'] ?? 0 );
		check_admin_referer( 'sst_clean_' . $id );
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_die( 'Nemáš oprávnenie upraviť túto stránku.' );
		}
		$post = get_post( $id );
		// Vrátime čistý text: bez značiek editora, bez prázdnych riadkov.
		$clean = trim( preg_replace( "/\n{3,}/", "\n\n", sst_strip_builder( $post->post_content ) ) );
		wp_save_post_revision( $id ); // poistka – pôvodný obsah do revízií
		wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => $clean,
			)
		);
		wp_safe_redirect( add_query_arg( 'sst_cleaned', 1, wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=page' ) ) );
		exit;
	}
);
