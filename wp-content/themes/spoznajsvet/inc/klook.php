<?php
/**
 * Tipy na zážitky (Klook): kategória článkov, do ktorej sa pre každé mesto z Destinácií
 * automaticky pridá článok s tipmi na výlety, vstupenky, transfer z letiska a internet.
 *
 * Text Klooku nekopírujeme (autorské práva, podmienky Klooku) – článok skladá vlastný úvod,
 * aktuálnu najlacnejšiu letenku a odkazy na ponuky Klooku s tvojím partnerským ID (aid).
 * Vlastné tipy: pri meste v Destináciách vlož odkazy na konkrétne zážitky z Klooku.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

const SST_KLOOK_CAT  = 'tipy-na-zazitky';
const SST_KLOOK_HOOK = 'sst_klook_cron';

function sst_klook_settings() {
	return wp_parse_args(
		(array) get_option( 'sst_klook', array() ),
		array(
			'enabled' => 1,
			'aid'     => '',
			'status'  => 'publish',
			'limit'   => 3, // Nových článkov za jedno spustenie (raz denne), aby sa web nezahltil naraz.
		)
	);
}

/** Kategória „Tipy na zážitky“ – vytvorí sa, ak chýba. */
function sst_klook_category() {
	$term = get_term_by( 'slug', SST_KLOOK_CAT, 'category' );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$res = wp_insert_term(
		'Tipy na zážitky',
		'category',
		array(
			'slug'        => SST_KLOOK_CAT,
			'description' => 'Čo zažiť v mestách, kde som bol: výlety, vstupenky, transfer z letiska a internet na cesty. Pridáva sa automaticky.',
		)
	);
	return is_wp_error( $res ) ? 0 : (int) $res['term_id'];
}

/** Kategória automatických tipov – na úvode a v zozname Článkov ju vynecháme (má vlastný archív). */
function sst_klook_exclude() {
	$term = get_term_by( 'slug', SST_KLOOK_CAT, 'category' );
	return $term ? array( (int) $term->term_id ) : array();
}

/** Anglický názov mesta pre vyhľadávanie na Klooku. */
function sst_klook_query( WP_Term $term ) {
	$own = trim( (string) get_term_meta( $term->term_id, 'sst_klook_query', true ) );
	if ( '' !== $own ) {
		return $own;
	}
	$en  = array(
		'rím'         => 'Rome',
		'benátky'     => 'Venice',
		'florencia'   => 'Florence',
		'neapol'      => 'Naples',
		'miláno'      => 'Milan',
		'turín'       => 'Turin',
		'janov'       => 'Genoa',
		'londýn'      => 'London',
		'paríž'       => 'Paris',
		'nica'        => 'Nice',
		'atény'       => 'Athens',
		'lisabon'     => 'Lisbon',
		'kodaň'       => 'Copenhagen',
		'mníchov'     => 'Munich',
		'berlín'      => 'Berlin',
		'kolín'       => 'Cologne',
		'viedeň'      => 'Vienna',
		'praha'       => 'Prague',
		'varšava'     => 'Warsaw',
		'krakov'      => 'Krakow',
		'budapešť'    => 'Budapest',
		'brusel'      => 'Brussels',
		'bruggy'      => 'Bruges',
		'ženeva'      => 'Geneva',
		'zürich'      => 'Zurich',
		'sevilla'     => 'Seville',
		'štokholm'    => 'Stockholm',
		'reykjavík'   => 'Reykjavik',
		'dubaj'       => 'Dubai',
		'abú zabí'    => 'Abu Dhabi',
		'abu dhabi'   => 'Abu Dhabi',
		'tokio'       => 'Tokyo',
		'kjóto'       => 'Kyoto',
		'peking'      => 'Beijing',
		'šanghaj'     => 'Shanghai',
		'soul'        => 'Seoul',
		'singapur'    => 'Singapore',
		'hongkong'    => 'Hong Kong',
		'káhira'      => 'Cairo',
		'marakéš'     => 'Marrakech',
		'marrákeš'    => 'Marrakech',
		'kapské mesto' => 'Cape Town',
		'new york'    => 'New York',
		'jeruzalem'   => 'Jerusalem',
		'malé'        => 'Male',
		'hanoj'       => 'Hanoi',
		'čiang mai'   => 'Chiang Mai',
	);
	$key = mb_strtolower( $term->name );
	return $en[ $key ] ?? remove_accents( $term->name );
}

/** Odkaz na Klook s partnerským ID. */
function sst_klook_url( $url ) {
	$aid = trim( (string) sst_klook_settings()['aid'] );
	return $aid ? add_query_arg( 'aid', rawurlencode( $aid ), $url ) : $url;
}

function sst_klook_search( $query ) {
	return sst_klook_url( 'https://www.klook.com/search/result/?query=' . rawurlencode( $query ) );
}

/**
 * Vlastné tipy pri meste: odkazy na zážitky z Klooku (jeden na riadok, voliteľne „odkaz | názov“).
 *
 * @return array<int, array{url:string,title:string}>
 */
function sst_klook_picks( WP_Term $term ) {
	$out = array();
	foreach ( preg_split( '/\R/', (string) get_term_meta( $term->term_id, 'sst_klook_picks', true ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$url   = esc_url_raw( $parts[0] );
		if ( ! $url || ! preg_match( '#^https?://([a-z0-9-]+\.)*klook\.com/#i', $url ) ) {
			continue;
		}
		$title = $parts[1] ?? '';
		if ( '' === $title && preg_match( '#/activity/\d+-([a-z0-9-]+)#i', $url, $m ) ) {
			// Názov z adresy: /activity/1234-colosseum-guided-tour/ → „Colosseum guided tour“.
			$title = ucfirst( str_replace( '-', ' ', $m[1] ) );
		}
		$out[] = array(
			'url'   => $url,
			'title' => '' !== $title ? $title : 'Zážitok na Klooku',
		);
	}
	return $out;
}

/** Kategórie tipov pre mesto. */
function sst_klook_sections( $q ) {
	return array(
		array( 'compass', 'Výlety a túry', 'Poldenné aj celodenné výlety so sprievodcom.', $q . ' tours' ),
		array( 'ticket', 'Vstupenky', 'Múzeá, vyhliadky a atrakcie, často s rezervovaným časom vstupu.', $q . ' tickets' ),
		array( 'car', 'Transfer z letiska', 'Odvoz z letiska do centra s pevnou cenou.', $q . ' airport transfer' ),
		array( 'food', 'Jedlo', 'Food tour alebo kurz varenia s miestnymi.', $q . ' food tour' ),
		array( 'wifi', 'eSIM a internet', 'Mobilné dáta, ktoré si aktivuješ ešte pred odletom.', $q . ' eSIM' ),
		array( 'train', 'Doprava', 'Mestské karty, vlaky a MHD.', $q . ' pass' ),
	);
}

/* ---------- Shortcode: živé tipy (ceny letenky a odkazy sa menia, text článku nie) ---------- */

add_shortcode(
	'spoznajsvet_klook',
	static function ( $atts ) {
		$atts = shortcode_atts( array( 'mesto' => 0 ), $atts, 'spoznajsvet_klook' );
		$term = get_term( (int) $atts['mesto'], 'destinacia' );
		if ( ! $term instanceof WP_Term ) {
			return '';
		}
		wp_enqueue_style( 'sst-klook', SST_URI . '/assets/css/klook.css', array( 'sst-theme' ), SST_VERSION );
		$q      = sst_klook_query( $term );
		$picks  = sst_klook_picks( $term );
		$flight = function_exists( 'sst_cheapest_flight_to' ) ? sst_cheapest_flight_to( $term ) : null;
		$rel    = 'sponsored nofollow noopener';
		ob_start();
		?>
		<div class="klook">
			<?php if ( $flight ) : ?>
				<a class="klook-flight" href="<?php echo esc_url( $flight['url'] ); ?>">
					<span class="klook-flight__icon" aria-hidden="true"><?php echo sst_icon( 'plane', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="klook-flight__text"><small>Letenka tam aj späť</small><b><?php echo esc_html( $term->name ); ?> od <?php echo esc_html( $flight['price'] ); ?></b></span>
					<span class="klook-flight__go" aria-hidden="true">→</span>
				</a>
			<?php endif; ?>

			<?php if ( $picks ) : ?>
				<h2>Moje tipy</h2>
				<ol class="klook-picks">
					<?php foreach ( $picks as $i => $p ) : ?>
						<li><a href="<?php echo esc_url( sst_klook_url( $p['url'] ) ); ?>" target="_blank" rel="<?php echo esc_attr( $rel ); ?>"><span class="klook-picks__n"><?php echo (int) $i + 1; ?></span><span><?php echo esc_html( $p['title'] ); ?></span><span class="klook-picks__go" aria-hidden="true"><?php echo sst_icon( 'arrow-up-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></a></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<h2>Čo zažiť: <?php echo esc_html( $term->name ); ?></h2>
			<ul class="klook-grid">
				<?php foreach ( sst_klook_sections( $q ) as $s ) : ?>
					<li>
						<a class="klook-card" href="<?php echo esc_url( sst_klook_search( $s[3] ) ); ?>" target="_blank" rel="<?php echo esc_attr( $rel ); ?>">
							<span class="klook-card__icon" aria-hidden="true"><?php echo sst_icon( $s[0], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<b><?php echo esc_html( $s[1] ); ?></b>
							<small><?php echo esc_html( $s[2] ); ?></small>
							<span class="klook-card__go">Pozrieť na Klooku</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="klook-note">Odkazy vedú na Klook a niektoré sú partnerské. Ak cez ne niečo kúpiš, dostanem malú províziu a cena sa pre teba nemení.</p>
		</div>
		<?php
		return ob_get_clean();
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( is_singular() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'spoznajsvet_klook' ) ) {
			wp_enqueue_style( 'sst-klook', SST_URI . '/assets/css/klook.css', array( 'sst-theme' ), SST_VERSION );
		}
	},
	20
);

/* ---------- Automatické články ---------- */

/** Mestá, o ktorých už mám články (prázdne mestá vynecháme, aby sa mapa nemenila). */
function sst_klook_cities() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'destinacia',
			'hide_empty' => true,
			'childless'  => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	return array_values(
		array_filter(
			$terms,
			static function ( $t ) {
				return $t->parent > 0;
			}
		)
	);
}

function sst_klook_intro( WP_Term $term, WP_Term $country ) {
	$name  = $term->name;
	$intro = array(
		'Čo sa oplatí v meste %1$s rezervovať vopred: výlety, vstupenky, odvoz z letiska a internet na cestu.',
		'Ak ideš do mesta %1$s (%2$s), tu sú výlety, vstupenky a doprava, ktoré si môžeš zariadiť ešte z domu.',
		'%1$s: výlety v okolí, vstupenky, cesta z letiska do centra a dáta do mobilu.',
	);
	$tpl = $intro[ $term->term_id % count( $intro ) ];
	return sprintf( $tpl, $name, $country->name );
}

/** Vytvorí článok pre mesto. Vráti ID alebo 0. */
function sst_klook_create_post( WP_Term $term ) {
	if ( get_term_meta( $term->term_id, 'sst_klook_post', true ) ) {
		return 0; // Už bol vytvorený (aj keď si ho potom zmazal – znova ho nevytvárame).
	}
	$cat = sst_klook_category();
	if ( ! $cat ) {
		return 0;
	}
	$country = sst_term_country( $term );
	$author  = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ID',
		)
	);
	$content = '<!-- wp:paragraph --><p>' . esc_html( sst_klook_intro( $term, $country ) ) . '</p><!-- /wp:paragraph -->'
		. "\n\n" . '<!-- wp:shortcode -->[spoznajsvet_klook mesto="' . (int) $term->term_id . '"]<!-- /wp:shortcode -->';
	$id = wp_insert_post(
		array(
			'post_type'     => 'post',
			'post_status'   => 'draft' === sst_klook_settings()['status'] ? 'draft' : 'publish',
			'post_title'    => sprintf( '%s: tipy na výlety, vstupenky a zážitky', $term->name ),
			'post_excerpt'  => sprintf( '%s: výlety, vstupenky, transfer z letiska a internet na cestu.', $term->name ),
			'post_content'  => $content,
			'post_author'   => $author ? (int) $author[0] : 0,
			'post_category' => array( $cat ),
		),
		true
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	wp_set_object_terms( $id, array( (int) $term->term_id ), 'destinacia' );
	$cover = (int) get_term_meta( $term->term_id, 'sst_cover', true );
	if ( $cover ) {
		set_post_thumbnail( $id, $cover );
	}
	update_post_meta( $id, '_sst_klook_term', (int) $term->term_id );
	update_term_meta( $term->term_id, 'sst_klook_post', (int) $id );
	return (int) $id;
}

/** Pridá chýbajúce články (max. $limit). Vráti počet nových. */
function sst_klook_run( $limit = null ) {
	$s = sst_klook_settings();
	if ( ! $s['enabled'] && null === $limit ) {
		return 0;
	}
	$limit = null === $limit ? max( 1, (int) $s['limit'] ) : (int) $limit;
	$made  = 0;
	foreach ( sst_klook_cities() as $term ) {
		if ( $made >= $limit ) {
			break;
		}
		if ( sst_klook_create_post( $term ) ) {
			++$made;
		}
	}
	return $made;
}

add_action( SST_KLOOK_HOOK, 'sst_klook_run' );

add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( SST_KLOOK_HOOK ) ) {
			wp_schedule_event( time() + 10 * MINUTE_IN_SECONDS, 'daily', SST_KLOOK_HOOK );
		}
	}
);

add_action(
	'admin_init',
	static function () {
		if ( current_user_can( 'manage_categories' ) && get_option( 'sst_klook_cat' ) !== SST_VERSION ) {
			sst_klook_category();
			update_option( 'sst_klook_cat', SST_VERSION );
		}
	}
);

/* ---------- Pole pri meste (Destinácie) ---------- */

add_action(
	'destinacia_edit_form_fields',
	static function ( $term ) {
		if ( ! $term->parent ) {
			return;
		}
		$picks = (string) get_term_meta( $term->term_id, 'sst_klook_picks', true );
		$q     = (string) get_term_meta( $term->term_id, 'sst_klook_query', true );
		?>
		<tr class="form-field"><th scope="row"><label for="sst_klook_picks">Moje tipy z Klooku</label></th><td>
			<textarea name="sst_klook_picks" id="sst_klook_picks" rows="4" placeholder="https://www.klook.com/activity/1234-colosseum-tour/ | Koloseum bez čakania"><?php echo esc_textarea( $picks ); ?></textarea>
			<p class="description">Voliteľné. Jeden odkaz na zážitok z Klooku na riadok, za „|“ môžeš dať vlastný názov. Zobrazia sa navrchu článku „Tipy na zážitky“ pre toto mesto.</p>
		</td></tr>
		<tr class="form-field"><th scope="row"><label for="sst_klook_query">Názov pre Klook</label></th><td>
			<input type="text" name="sst_klook_query" id="sst_klook_query" value="<?php echo esc_attr( $q ); ?>" style="width:16em" placeholder="<?php echo esc_attr( sst_klook_query( $term ) ); ?>">
			<p class="description">Anglický názov mesta na vyhľadávanie na Klooku. Nechaj prázdne – doplní sa sám.</p>
		</td></tr>
		<?php
	},
	20
);

add_action(
	'edited_destinacia',
	static function ( $term_id ) {
		if ( ! isset( $_POST['sst_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sst_term_nonce'] ), 'sst_term_meta' ) || ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		if ( isset( $_POST['sst_klook_picks'] ) ) {
			update_term_meta( $term_id, 'sst_klook_picks', sanitize_textarea_field( wp_unslash( $_POST['sst_klook_picks'] ) ) );
		}
		if ( isset( $_POST['sst_klook_query'] ) ) {
			update_term_meta( $term_id, 'sst_klook_query', sanitize_text_field( wp_unslash( $_POST['sst_klook_query'] ) ) );
		}
	},
	20
);

/* ---------- Nastavenia: Články → Tipy na zážitky ---------- */

add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'edit.php', 'Tipy na zážitky (Klook)', 'Tipy na zážitky', 'manage_categories', 'sst-klook', 'sst_klook_admin_page' );
	}
);

add_action(
	'admin_post_sst_klook',
	static function () {
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( 'Nemáš oprávnenie.' );
		}
		check_admin_referer( 'sst_klook' );
		$msg = 'saved';
		update_option(
			'sst_klook',
			array(
				'enabled' => empty( $_POST['enabled'] ) ? 0 : 1,
				'aid'     => preg_replace( '/[^A-Za-z0-9_-]/', '', wp_unslash( $_POST['aid'] ?? '' ) ),
				'status'  => 'draft' === ( $_POST['status'] ?? '' ) ? 'draft' : 'publish',
				'limit'   => min( 20, max( 1, absint( $_POST['limit'] ?? 3 ) ) ),
			),
			false
		);
		if ( ! empty( $_POST['run'] ) ) {
			$msg = 'run-' . sst_klook_run( 20 );
		}
		wp_safe_redirect( add_query_arg( 'msg', $msg, admin_url( 'edit.php?page=sst-klook' ) ) );
		exit;
	}
);

function sst_klook_admin_page() {
	$s       = sst_klook_settings();
	$cat     = sst_klook_category();
	$cities  = sst_klook_cities();
	$waiting = array_filter(
		$cities,
		static function ( $t ) {
			return ! get_term_meta( $t->term_id, 'sst_klook_post', true );
		}
	);
	$posts   = get_posts(
		array(
			'post_status'    => array( 'publish', 'draft' ),
			'meta_key'       => '_sst_klook_term', // phpcs:ignore WordPress.DB.SlowDBQuery
			'posts_per_page' => 50,
		)
	);
	$msg     = sanitize_key( wp_unslash( $_GET['msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap">
		<h1>Tipy na zážitky (Klook)</h1>
		<?php if ( 'saved' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p>Uložené.</p></div>
		<?php elseif ( 0 === strpos( $msg, 'run-' ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Pridané články: <?php echo (int) substr( $msg, 4 ); ?>.</p></div>
		<?php endif; ?>
		<p>
			Pre každé mesto z <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=destinacia' ) ); ?>">Destinácií</a>, o ktorom máš článok, sa raz denne automaticky pridá článok do kategórie
			<?php if ( $cat ) : ?>
				<a href="<?php echo esc_url( get_category_link( $cat ) ); ?>" target="_blank"><strong>Tipy na zážitky</strong></a>
			<?php endif; ?>
			– výlety, vstupenky, transfer z letiska, jedlo, eSIM a doprava s odkazmi na Klook a aktuálnou najlacnejšou letenkou.
			Text Klooku sa nekopíruje; vlastné tipy na konkrétne zážitky pridáš pri meste v Destináciách („Moje tipy z Klooku“).
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sst_klook">
			<?php wp_nonce_field( 'sst_klook' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Automaticky pridávať</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?>> Raz denne pridať články pre nové mestá</label></td></tr>
				<tr><th scope="row"><label for="sst-klook-aid">Klook partnerské ID (aid)</label></th><td>
					<input type="text" id="sst-klook-aid" name="aid" value="<?php echo esc_attr( $s['aid'] ); ?>" class="regular-text" placeholder="napr. 12345">
					<p class="description">Z partnerského programu Klook (affiliate.klook.com). Pridá sa ku všetkým odkazom, aby sa ti rátali provízie. Môže ostať prázdne.</p>
				</td></tr>
				<tr><th scope="row">Nové články</th><td>
					<label><input type="radio" name="status" value="publish" <?php checked( $s['status'], 'publish' ); ?>> hneď zverejniť</label><br>
					<label><input type="radio" name="status" value="draft" <?php checked( $s['status'], 'draft' ); ?>> uložiť ako koncept (pred zverejnením ich skontrolujem)</label>
				</td></tr>
				<tr><th scope="row"><label for="sst-klook-limit">Najviac za deň</label></th><td><input type="number" id="sst-klook-limit" name="limit" min="1" max="20" value="<?php echo (int) $s['limit']; ?>" style="width:5em"> článkov</td></tr>
			</table>
			<p>
				<?php submit_button( 'Uložiť', 'primary', 'save', false ); ?>
				<button type="submit" name="run" value="1" class="button"<?php disabled( ! $waiting ); ?>>Uložiť a pridať chýbajúce teraz (<?php echo count( $waiting ); ?>)</button>
			</p>
		</form>
		<h2>Články (<?php echo count( $posts ); ?>)</h2>
		<?php if ( $posts ) : ?>
			<ul>
				<?php foreach ( $posts as $p ) : ?>
					<li><a href="<?php echo esc_url( get_edit_post_link( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a><?php echo 'draft' === $p->post_status ? ' — <em>koncept</em>' : ''; ?></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p>Zatiaľ žiadne.</p>
		<?php endif; ?>
		<?php if ( $waiting ) : ?>
			<p class="description">Čakajú: <?php echo esc_html( implode( ', ', wp_list_pluck( $waiting, 'name' ) ) ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}
