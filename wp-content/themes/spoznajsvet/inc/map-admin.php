<?php
/**
 * Administrácia mapy: Články → Mapa ciest.
 * Vyznačenie štátov a ostrovov kliknutím do mapy, pridanie miesta kliknutím, zoznam navštívených.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'edit.php', 'Mapa ciest', 'Mapa ciest', 'manage_categories', 'sst-mapa', 'sst_map_admin_page' );
	}
);

add_action(
	'admin_enqueue_scripts',
	static function ( $hook ) {
		if ( 'posts_page_sst-mapa' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'sst-map', SST_URI . '/assets/css/map.css', array(), SST_VERSION );
		wp_enqueue_style( 'sst-map-admin', SST_URI . '/assets/css/map-admin.css', array( 'sst-map' ), SST_VERSION );
		wp_enqueue_script( 'sst-map', SST_URI . '/assets/js/map.js', array(), SST_VERSION, true );
		wp_enqueue_script( 'sst-map-admin', SST_URI . '/assets/js/map-admin.js', array( 'sst-map' ), SST_VERSION, true );
		wp_localize_script(
			'sst-map-admin',
			'sstMapAdmin',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'sst_map' ),
			)
		);
	}
);

/** Odkaz na mapu aj v zozname Destinácií. */
add_action(
	'destinacia_pre_add_form',
	static function () {
		echo '<p class="description" style="margin:1em 0">Navštívené štáty, ostrovy a miesta môžete vyznačiť aj priamo na mape: <a class="button button-secondary" href="' . esc_url( admin_url( 'edit.php?page=sst-mapa' ) ) . '">Otvoriť mapu ciest</a></p>';
	}
);

function sst_map_admin_page() {
	$payload = sst_map_payload( true );
	$regions = sst_map_regions();
	?>
	<div class="wrap sst-map-admin">
		<h1>Mapa ciest</h1>
		<p class="sst-map-admin__intro">Klikni na štát alebo ostrov (napr. Azory pri Portugalsku) a vyznač ho ako navštívený. Štáty a mestá z <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=destinacia' ) ); ?>">Destinácií</a> sa na mapu dostanú samy – vyfarbí sa časť, v ktorej mesto leží. Zmeny sa ukladajú hneď.</p>

		<div class="sst-map-admin__layout">
			<div class="sst-map-admin__main">
				<div class="sst-map-admin__modes" role="radiogroup" aria-label="Režim">
					<button type="button" class="is-active" data-mode="units" role="radio" aria-checked="true"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span> Vyznačiť štáty a ostrovy</button>
					<button type="button" data-mode="places" role="radio" aria-checked="false"><span class="dashicons dashicons-location" aria-hidden="true"></span> Pridať miesto</button>
				</div>

				<div class="world-map" data-world-map="<?php echo esc_url( SST_URI . '/assets/map/world.svg?ver=' . SST_VERSION ); ?>" data-map-admin>
					<div class="world-map__frame">
						<div class="world-map__bar">
							<div class="world-map__regions" role="group" aria-label="Priblížiť oblasť">
								<?php if ( $payload['fit'] ) : ?>
									<button type="button" class="chip is-active" data-region="fit" aria-pressed="true">Moje cesty</button>
								<?php endif; ?>
								<?php foreach ( $regions as $key => $r ) : ?>
									<button type="button" class="chip<?php echo ! $payload['fit'] && 'svet' === $key ? ' is-active' : ''; ?>" data-region="<?php echo esc_attr( $key ); ?>" aria-pressed="false"><?php echo esc_html( $r['label'] ); ?></button>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="world-map__canvas" data-map-canvas>
							<div class="world-map__zoom">
								<button type="button" data-zoom="in" aria-label="Priblížiť">+</button>
								<button type="button" data-zoom="out" aria-label="Oddialiť">−</button>
							</div>
							<div class="world-map__tip" data-map-tip hidden></div>
							<div class="sst-map-admin__pop" data-pop hidden></div>
						</div>
						<div class="world-map__legend">
							<span><i class="lvl-1"></i>1 miesto</span>
							<span><i class="lvl-2"></i>2 – 3</span>
							<span><i class="lvl-3"></i>4 – 6</span>
							<span><i class="lvl-4"></i>7 a viac</span>
							<span><i class="manual"></i>vyznačené ručne</span>
							<span><i class="dot"></i>miesto</span>
						</div>
					</div>
				</div>
				<p class="sst-map-admin__hint" data-hint>Kolieskom myši alebo tlačidlami +/− priblížiš, ťahaním posunieš. Malé ostrovy nájdeš aj cez vyhľadávanie vpravo.</p>
			</div>

			<aside class="sst-map-admin__side">
				<div data-stats><?php echo sst_map_stats_html( $payload['stats'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div class="sst-map-admin__search">
					<label for="sst-map-search">Pridať štát alebo ostrov</label>
					<input type="search" id="sst-map-search" placeholder="napr. Azory, Japonsko, Havaj…" autocomplete="off" data-search>
					<ul class="sst-map-admin__results" data-results hidden></ul>
				</div>
				<h2 class="sst-map-admin__title">Navštívené <span data-count></span></h2>
				<ul class="sst-map-admin__list" data-list></ul>
			</aside>
		</div>
		<div class="sst-map-admin__toast" data-toast role="status" hidden></div>
		<script type="application/json" data-map-data><?php echo wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>
	</div>
	<?php
}

/** Spoločná kontrola pre AJAX. */
function sst_map_ajax_check() {
	check_ajax_referer( 'sst_map', 'nonce' );
	if ( ! current_user_can( 'manage_categories' ) ) {
		wp_send_json_error( array( 'msg' => 'Nemáš oprávnenie upravovať destinácie.' ), 403 );
	}
}

function sst_map_ajax_done( $msg ) {
	sst_map_flush();
	wp_send_json_success(
		array(
			'msg'   => $msg,
			'data'  => sst_map_payload( true ),
			'stats' => sst_map_stats_html( sst_map_payload()['stats'] ),
		)
	);
}

/** Vyznačiť / zrušiť štát alebo ostrov. */
add_action(
	'wp_ajax_sst_map_toggle',
	static function () {
		sst_map_ajax_check();
		$su    = strtoupper( sanitize_key( wp_unslash( $_POST['unit'] ?? '' ) ) );
		$units = sst_map_units();
		if ( ! isset( $units[ $su ] ) || ! $units[ $su ]['i'] ) {
			wp_send_json_error( array( 'msg' => 'Neznáma časť mapy.' ) );
		}
		$on          = ! empty( $_POST['on'] );
		$marks       = sst_map_marks();
		$marks[ $su ] = $on ? 1 : 0;
		update_option( 'sst_map_marks', $marks, false );
		sst_map_ajax_done( ( $on ? 'Vyznačené: ' : 'Zrušené: ' ) . sst_map_unit_name( $su ) . ( empty( $units[ $su ]['m'] ) ? ' (' . sst_map_country_name( $units[ $su ]['i'] ) . ')' : '' ) );
	}
);

/** Pridať miesto kliknutím do mapy (vytvorí mesto v Destináciách, prípadne aj krajinu). */
add_action(
	'wp_ajax_sst_map_add_place',
	static function () {
		sst_map_ajax_check();
		$name  = trim( sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ) );
		$lat   = (float) ( $_POST['lat'] ?? 0 );
		$lng   = (float) ( $_POST['lng'] ?? 0 );
		$su    = strtoupper( sanitize_key( wp_unslash( $_POST['unit'] ?? '' ) ) );
		$units = sst_map_units();
		if ( '' === $name || abs( $lat ) > 90 || abs( $lng ) > 180 ) {
			wp_send_json_error( array( 'msg' => 'Zadaj názov miesta.' ) );
		}
		if ( ! isset( $units[ $su ] ) || ! $units[ $su ]['i'] ) {
			wp_send_json_error( array( 'msg' => 'Klikni na pevninu alebo ostrov.' ) );
		}
		$iso = $units[ $su ]['i'];

		$country = get_terms(
			array(
				'taxonomy'   => 'destinacia',
				'parent'     => 0,
				'hide_empty' => false,
				'meta_key'   => 'sst_iso', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value' => $iso, // phpcs:ignore WordPress.DB.SlowDBQuery
				'number'     => 1,
			)
		);
		$created = '';
		if ( $country && ! is_wp_error( $country ) ) {
			$country_id = $country[0]->term_id;
		} else {
			$res = wp_insert_term( sst_map_country_name( $iso ), 'destinacia' );
			if ( is_wp_error( $res ) ) {
				$existing   = term_exists( sst_map_country_name( $iso ), 'destinacia', 0 );
				$country_id = $existing ? (int) $existing['term_id'] : 0;
			} else {
				$country_id = (int) $res['term_id'];
				$created    = ' a krajina ' . sst_map_country_name( $iso );
			}
			if ( ! $country_id ) {
				wp_send_json_error( array( 'msg' => 'Krajinu sa nepodarilo vytvoriť.' ) );
			}
			update_term_meta( $country_id, 'sst_iso', $iso );
			if ( $units[ $su ]['c'] && array_key_exists( $units[ $su ]['c'], sst_continents() ) && ! get_term_meta( $country_id, 'sst_continent', true ) ) {
				update_term_meta( $country_id, 'sst_continent', $units[ $su ]['c'] );
			}
		}

		$exists = term_exists( $name, 'destinacia', $country_id );
		if ( $exists ) {
			$city_id = (int) $exists['term_id'];
		} else {
			$res = wp_insert_term( $name, 'destinacia', array( 'parent' => $country_id ) );
			if ( is_wp_error( $res ) ) {
				wp_send_json_error( array( 'msg' => $res->get_error_message() ) );
			}
			$city_id = (int) $res['term_id'];
		}
		update_term_meta( $city_id, 'sst_lat', round( $lat, 5 ) );
		update_term_meta( $city_id, 'sst_lng', round( $lng, 5 ) );
		sst_map_ajax_done( ( $exists ? 'Poloha upravená: ' : 'Pridané miesto ' ) . $name . $created );
	}
);

/** Odstrániť miesto (len ak nemá články). */
add_action(
	'wp_ajax_sst_map_delete_place',
	static function () {
		sst_map_ajax_check();
		$term = get_term( absint( $_POST['id'] ?? 0 ), 'destinacia' );
		if ( ! $term instanceof WP_Term || ! $term->parent ) {
			wp_send_json_error( array( 'msg' => 'Miesto sa nenašlo.' ) );
		}
		if ( $term->count > 0 ) {
			wp_send_json_error( array( 'msg' => 'Miesto má články – najprv ich presuň, alebo ho uprav v Destináciách.' ) );
		}
		wp_delete_term( $term->term_id, 'destinacia' );
		sst_map_ajax_done( 'Odstránené miesto ' . $term->name );
	}
);
