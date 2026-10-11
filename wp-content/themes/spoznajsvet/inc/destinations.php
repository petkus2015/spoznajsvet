<?php
/**
 * Destinácie: hierarchická taxonómia Krajina → Mesto (napr. Taliansko → Florencia).
 * URL: /destinacie/taliansko/ a /destinacie/taliansko/florencia/
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		register_taxonomy(
			'destinacia',
			array( 'post' ),
			array(
				'labels'            => array(
					'name'              => 'Destinácie',
					'singular_name'     => 'Destinácia',
					'menu_name'         => 'Destinácie',
					'all_items'         => 'Všetky destinácie',
					'parent_item'       => 'Krajina (nadradená)',
					'parent_item_colon' => 'Krajina:',
					'edit_item'         => 'Upraviť destináciu',
					'view_item'         => 'Zobraziť destináciu',
					'update_item'       => 'Aktualizovať destináciu',
					'add_new_item'      => 'Pridať krajinu alebo mesto',
					'new_item_name'     => 'Názov krajiny / mesta',
					'search_items'      => 'Hľadať destinácie',
					'not_found'         => 'Žiadne destinácie',
					'back_to_items'     => '← Späť na destinácie',
				),
				'description'       => 'Krajiny (najvyššia úroveň) a mestá (podradené krajine).',
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'         => 'destinacie',
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);

		foreach ( array( 'sst_iso', 'sst_continent' ) as $key ) {
			register_term_meta(
				'destinacia',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
				)
			);
		}
		register_term_meta(
			'destinacia',
			'sst_cover',
			array(
				'type'         => 'integer',
				'single'       => true,
				'show_in_rest' => true,
			)
		);
	}
);

function sst_continents() {
	return array(
		'europa'  => 'Európa',
		'azia'    => 'Ázia',
		'afrika'  => 'Afrika',
		'amerika' => 'Amerika',
		'oceania' => 'Austrália a Oceánia',
	);
}

/* ---------- Administrácia: vlajka, kontinent, titulná fotka ---------- */

function sst_term_fields( $term = null ) {
	$term      = $term instanceof WP_Term ? $term : null; // Pri pridávaní príde názov taxonómie.
	$iso       = $term ? get_term_meta( $term->term_id, 'sst_iso', true ) : '';
	$continent = $term ? get_term_meta( $term->term_id, 'sst_continent', true ) : '';
	$cover     = $term ? (int) get_term_meta( $term->term_id, 'sst_cover', true ) : 0;
	$img       = $cover ? wp_get_attachment_image_url( $cover, 'medium' ) : '';
	wp_nonce_field( 'sst_term_meta', 'sst_term_nonce' );

	$rows = array(
		'iso'       => array(
			'label' => 'Kód krajiny (ISO)',
			'html'  => '<input type="text" name="sst_iso" id="sst_iso" value="' . esc_attr( $iso ) . '" maxlength="2" style="width:5em;text-transform:uppercase" placeholder="IT">',
			'help'  => 'Dve písmená, napr. IT, TH, AE – zobrazí sa vlajka. Pri meste nechajte prázdne.',
		),
		'continent' => array(
			'label' => 'Kontinent',
			'html'  => '',
			'help'  => 'Len pre krajiny – slúži na zoskupenie na stránke Destinácie.',
		),
		'cover'     => array(
			'label' => 'Titulná fotka',
			'html'  => '<div class="sst-cover-field"><img src="' . esc_url( $img ) . '" alt="" style="max-width:240px;border-radius:8px;display:' . ( $img ? 'block' : 'none' ) . ';margin-bottom:8px"><input type="hidden" name="sst_cover" value="' . esc_attr( $cover ) . '"><button type="button" class="button sst-cover-pick">Vybrať fotku</button> <button type="button" class="button-link sst-cover-remove">Odstrániť</button></div>',
			'help'  => 'Ak ju nevyberiete, použije sa náhľadový obrázok najnovšieho článku.',
		),
	);
	$select = '<select name="sst_continent" id="sst_continent"><option value="">—</option>';
	foreach ( sst_continents() as $k => $label ) {
		$select .= '<option value="' . esc_attr( $k ) . '"' . selected( $continent, $k, false ) . '>' . esc_html( $label ) . '</option>';
	}
	$rows['continent']['html'] = $select . '</select>';

	foreach ( $rows as $id => $row ) {
		if ( $term ) {
			echo '<tr class="form-field"><th scope="row"><label for="sst_' . esc_attr( $id ) . '">' . esc_html( $row['label'] ) . '</label></th><td>' . $row['html'] . '<p class="description">' . esc_html( $row['help'] ) . '</p></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			echo '<div class="form-field"><label for="sst_' . esc_attr( $id ) . '">' . esc_html( $row['label'] ) . '</label>' . $row['html'] . '<p>' . esc_html( $row['help'] ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
}
add_action( 'destinacia_add_form_fields', 'sst_term_fields' );
add_action( 'destinacia_edit_form_fields', 'sst_term_fields' );

function sst_save_term_fields( $term_id ) {
	if ( ! isset( $_POST['sst_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sst_term_nonce'] ), 'sst_term_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$iso = strtoupper( preg_replace( '/[^A-Za-z]/', '', wp_unslash( $_POST['sst_iso'] ?? '' ) ) );
	update_term_meta( $term_id, 'sst_iso', substr( $iso, 0, 2 ) );

	$continent = sanitize_key( wp_unslash( $_POST['sst_continent'] ?? '' ) );
	update_term_meta( $term_id, 'sst_continent', array_key_exists( $continent, sst_continents() ) ? $continent : '' );

	update_term_meta( $term_id, 'sst_cover', absint( $_POST['sst_cover'] ?? 0 ) );
}
add_action( 'created_destinacia', 'sst_save_term_fields' );
add_action( 'edited_destinacia', 'sst_save_term_fields' );

add_action(
	'admin_enqueue_scripts',
	static function ( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || 'destinacia' !== $screen->taxonomy || ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		$js = <<<'JS'
jQuery(function ($) {
	$(document).on('click', '.sst-cover-pick', function (e) {
		e.preventDefault();
		var box = $(this).closest('.sst-cover-field');
		var frame = wp.media({ title: 'Titulná fotka', library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			box.find('input').val(a.id);
			box.find('img').attr('src', a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url).show();
		});
		frame.open();
	});
	$(document).on('click', '.sst-cover-remove', function (e) {
		e.preventDefault();
		var box = $(this).closest('.sst-cover-field');
		box.find('input').val('');
		box.find('img').hide();
	});
	$(document).ajaxSuccess(function (ev, xhr, s) {
		if (s.data && s.data.indexOf('action=add-tag') !== -1) {
			$('.sst-cover-field input').val('');
			$('.sst-cover-field img').hide();
		}
	});
});
JS;
		wp_add_inline_script( 'media-editor', $js );
	}
);

/**
 * Pri priradení mesta automaticky priradíme aj krajinu (aby sedeli počty a archívy).
 */
add_action(
	'set_object_terms',
	static function ( $object_id, $terms, $tt_ids, $taxonomy ) {
		if ( 'destinacia' !== $taxonomy || ! $tt_ids ) {
			return;
		}
		$have    = wp_get_object_terms( $object_id, 'destinacia', array( 'fields' => 'ids' ) );
		$missing = array();
		foreach ( $have as $term_id ) {
			foreach ( get_ancestors( $term_id, 'destinacia', 'taxonomy' ) as $ancestor ) {
				if ( ! in_array( $ancestor, $have, true ) ) {
					$missing[] = (int) $ancestor;
				}
			}
		}
		if ( $missing ) {
			wp_set_object_terms( $object_id, array_unique( $missing ), 'destinacia', true );
		}
	},
	10,
	4
);

/* ---------- Pomocné funkcie ---------- */

/**
 * Krajiny (najvyššia úroveň).
 *
 * @return WP_Term[]
 */
function sst_countries( $args = array() ) {
	$terms = get_terms(
		wp_parse_args(
			$args,
			array(
				'taxonomy'   => 'destinacia',
				'parent'     => 0,
				'hide_empty' => true,
				'orderby'    => 'name',
			)
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	if ( class_exists( 'Collator' ) && ( empty( $args['orderby'] ) || 'name' === $args['orderby'] ) ) {
		$c = new Collator( 'sk_SK' );
		usort(
			$terms,
			static function ( $a, $b ) use ( $c ) {
				return $c->compare( $a->name, $b->name );
			}
		);
	}
	return $terms;
}

/** Mestá krajiny. */
function sst_cities( $country, $hide_empty = true ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'destinacia',
			'parent'     => is_object( $country ) ? $country->term_id : (int) $country,
			'hide_empty' => $hide_empty,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}

/** Krajina (koreň) pre ľubovoľnú destináciu. */
function sst_term_country( $term ) {
	$ancestors = get_ancestors( $term->term_id, 'destinacia', 'taxonomy' );
	if ( ! $ancestors ) {
		return $term;
	}
	$root = get_term( end( $ancestors ), 'destinacia' );
	return ( $root && ! is_wp_error( $root ) ) ? $root : $term;
}

function sst_flag_emoji( $iso ) {
	$iso = strtoupper( (string) $iso );
	if ( ! preg_match( '/^[A-Z]{2}$/', $iso ) ) {
		return '';
	}
	return mb_chr( 0x1F1E6 + ord( $iso[0] ) - 65 ) . mb_chr( 0x1F1E6 + ord( $iso[1] ) - 65 );
}

function sst_term_flag( $term ) {
	$iso = get_term_meta( $term->term_id, 'sst_iso', true );
	if ( ! $iso && $term->parent ) {
		$iso = get_term_meta( sst_term_country( $term )->term_id, 'sst_iso', true );
	}
	return sst_flag_emoji( $iso );
}

/**
 * Predvolené ilustrácie destinácií pribalené v téme (assets/img/destinacie).
 * Kľúč = slug destinácie (alebo alias), hodnota = názov súboru bez prípony.
 */
function sst_default_covers() {
	return apply_filters(
		'sst_default_covers',
		array(
			'taliansko'               => 'taliansko',
			'rim'                     => 'rim',
			'florencia'               => 'florencia',
			'benatky'                 => 'benatky',
			'amalfi'                  => 'amalfi',
			'pobrezie-amalfi'         => 'amalfi',
			'neapol'                  => 'amalfi',
			'toskansko'               => 'toskansko',
			'sicilia'                 => 'sicilia',
			'palermo'                 => 'sicilia',
			'catania'                 => 'sicilia',
			'thajsko'                 => 'thajsko',
			'bangkok'                 => 'bangkok',
			'phuket'                  => 'phuket',
			'krabi'                   => 'phuket',
			'spojene-arabske-emiraty' => 'sae',
			'sae'                     => 'sae',
			'abu-dhabi'               => 'sae',
			'dubaj'                   => 'dubaj',
			'chorvatsko'              => 'chorvatsko',
			'island'                  => 'island',
			'portugalsko'             => 'portugalsko',
		)
	);
}

/**
 * Predvolená ilustrácia pre destináciu: podľa názvu, potom podľa krajiny,
 * inak stále rovnaká (podľa slugu) z ponuky ilustrácií – nikdy prázdne miesto.
 */
function sst_default_cover_url( $term, $size = 'sst-card' ) {
	$map  = sst_default_covers();
	$file = $map[ $term->slug ] ?? '';
	if ( ! $file && $term->parent ) {
		$file = $map[ sst_term_country( $term )->slug ] ?? '';
	}
	if ( ! $file ) {
		$pool = array_values( array_unique( $map ) );
		$file = $pool[ abs( crc32( $term->slug ) ) % count( $pool ) ];
	}
	$suffix = in_array( $size, array( 'sst-card', 'medium', 'thumbnail' ), true ) ? '-karta' : '';
	return SST_URI . '/assets/img/destinacie/' . $file . $suffix . '.jpg';
}

/**
 * URL titulnej fotky destinácie (vlastná → najnovší článok s obrázkom → ilustrácia z témy).
 */
function sst_term_cover_url( $term, $size = 'sst-card' ) {
	$cover = (int) get_term_meta( $term->term_id, 'sst_cover', true );
	if ( $cover ) {
		$url = wp_get_attachment_image_url( $cover, $size );
		if ( $url ) {
			return $url;
		}
	}
	$cache_key = 'sst_cover_' . $term->term_id . '_' . $size;
	$url       = wp_cache_get( $cache_key, 'sst' );
	if ( false !== $url ) {
		return $url;
	}
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'destinacia',
					'terms'    => $term->term_id,
				),
			),
		)
	);
	$url = $posts ? (string) get_the_post_thumbnail_url( $posts[0], $size ) : '';
	if ( ! $url && apply_filters( 'sst_use_default_covers', true ) ) {
		$url = sst_default_cover_url( $term, $size );
	}
	wp_cache_set( $cache_key, $url, 'sst', HOUR_IN_SECONDS );
	return $url;
}

/**
 * Krajina a mesto článku.
 *
 * @return array{country:?WP_Term,city:?WP_Term,cities:WP_Term[]}
 */
function sst_post_places( $post_id = null ) {
	$terms  = get_the_terms( $post_id ? $post_id : get_the_ID(), 'destinacia' );
	$out    = array(
		'country' => null,
		'city'    => null,
		'cities'  => array(),
	);
	if ( ! $terms || is_wp_error( $terms ) ) {
		return $out;
	}
	foreach ( $terms as $t ) {
		if ( 0 === (int) $t->parent ) {
			$out['country'] = $out['country'] ? $out['country'] : $t;
		} else {
			$out['cities'][] = $t;
		}
	}
	if ( $out['cities'] ) {
		$out['city'] = $out['cities'][0];
		if ( ! $out['country'] ) {
			$out['country'] = sst_term_country( $out['city'] );
		}
	}
	return $out;
}

/** „Taliansko · Florencia“ s vlajkou. */
function sst_place_label( $post_id = null ) {
	$p = sst_post_places( $post_id );
	if ( ! $p['country'] ) {
		return '';
	}
	$parts = array( $p['country']->name );
	if ( $p['city'] ) {
		$parts[] = $p['city']->name;
		if ( count( $p['cities'] ) > 1 ) {
			$parts[ count( $parts ) - 1 ] .= ' +' . ( count( $p['cities'] ) - 1 );
		}
	}
	return trim( sst_term_flag( $p['country'] ) . ' ' . implode( ' · ', $parts ) );
}

/** Počet článkov v krajine/meste (vrátane podradených). */
function sst_term_count( $term ) {
	return (int) $term->count;
}

function sst_plural( $n, $one, $few, $many ) {
	$n = abs( (int) $n );
	if ( 1 === $n ) {
		return $one;
	}
	return ( $n >= 2 && $n <= 4 ) ? $few : $many;
}
