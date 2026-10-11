<?php
defined( 'ABSPATH' ) || exit;

/**
 * Nastavenia → Letenky.
 */
class SVL_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_svl_refresh', array( __CLASS__, 'refresh' ) );
		if ( ! defined( 'SVL_BUNDLED' ) ) {
			add_filter( 'plugin_action_links_' . plugin_basename( SVL_FILE ), array( __CLASS__, 'action_links' ) );
		}
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=svl-letenky' ) ) . '">Nastavenia</a>' );
		return $links;
	}

	public static function menu() {
		add_options_page( 'Letenky', 'Letenky', 'manage_options', 'svl-letenky', array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting(
			'svl',
			SVL_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'SVL_Settings', 'sanitize' ),
				'default'           => SVL_Settings::defaults(),
			)
		);
	}

	public static function refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nedostatočné oprávnenia.' );
		}
		check_admin_referer( 'svl_refresh' );
		SVL_Store::refresh( true );
		wp_safe_redirect( admin_url( 'options-general.php?page=svl-letenky&svl_done=1' ) );
		exit;
	}

	private static function field( $key, $label, $help = '', $type = 'text' ) {
		$name  = SVL_Settings::OPTION . '[' . $key . ']';
		$value = SVL_Settings::get( $key );
		echo '<tr><th scope="row"><label for="svl-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" id="svl-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( 1, (int) $value, false ) . '> ' . esc_html( $help ) . '</label>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea class="large-text code" rows="6" id="svl-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
			echo '<p class="description">' . wp_kses_post( $help ) . '</p>';
		} else {
			echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="svl-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
			if ( $help ) {
				echo '<p class="description">' . wp_kses_post( $help ) . '</p>';
			}
		}
		echo '</td></tr>';
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$data = SVL_Store::data();
		$next = wp_next_scheduled( SVL_Cron::HOOK );
		?>
		<div class="wrap">
			<h1>Letenky</h1>
			<?php if ( isset( $_GET['svl_done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>Ceny boli aktualizované.</p></div>
			<?php endif; ?>

			<h2>Stav</h2>
			<p>
				Posledná aktualizácia: <strong><?php echo $data['updated'] ? esc_html( wp_date( 'j. n. Y H:i', $data['updated'] ) . ' (' . SVL_Format::ago( $data['updated'] ) . ')' ) : 'zatiaľ nikdy'; ?></strong>
				· Ďalšia: <strong><?php echo $next ? esc_html( wp_date( 'H:i', $next ) ) : '—'; ?></strong>
				· Uložených ponúk: <strong><?php echo count( $data['deals'] ); ?></strong>
			</p>
			<?php if ( $data['status'] ) : ?>
				<table class="widefat striped" style="max-width:900px">
					<thead><tr><th>Zdroj : skupina</th><th>Stav</th><th>Ponuky</th><th>Správa</th></tr></thead>
					<tbody>
					<?php foreach ( $data['status'] as $key => $s ) : ?>
						<tr>
							<td><?php echo esc_html( $key ); ?></td>
							<td><?php echo $s['ok'] ? '✅ OK' : '⚠️ Chyba (zobrazujú sa posledné platné dáta)'; ?></td>
							<td><?php echo (int) $s['count']; ?></td>
							<td><code><?php echo esc_html( $s['msg'] ); ?></code></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:1em 0 2em">
				<input type="hidden" name="action" value="svl_refresh">
				<?php wp_nonce_field( 'svl_refresh' ); ?>
				<?php submit_button( 'Aktualizovať teraz', 'secondary', 'submit', false ); ?>
			</form>

			<form method="post" action="options.php">
				<?php settings_fields( 'svl' ); ?>
				<h2>Zdroje</h2>
				<table class="form-table" role="presentation">
					<?php
					self::field( 'ryanair', 'Ryanair', 'Najlacnejšie jednosmerné lety do Európy (bez kľúča).', 'checkbox' );
					self::field( 'wizzair', 'Wizz Air', 'Cenový kalendár Wizz Air (bez kľúča). Linky sa obnovujú postupne, max. 14 na beh.', 'checkbox' );
					self::field( 'travelpayouts', 'Aviasales / Travelpayouts', 'Klasické aerolínie (Qatar, Emirates, Etihad, Turkish…) – najmä pre Bangkok, Dubaj, Abu Dhabi.', 'checkbox' );
					self::field( 'tp_token', 'Travelpayouts API token', 'Zadarmo po registrácii na <a href="https://www.travelpayouts.com/" target="_blank" rel="noopener">travelpayouts.com</a> → Tools → API.', 'password' );
					self::field( 'tp_marker', 'Travelpayouts marker', 'Voliteľné – vaše partnerské ID (provízia z predaných leteniek).' );
					self::field( 'kayak_domain', 'Doména Kayak', 'Napr. www.kayak.com, www.kayak.de' );
					self::field( 'momondo_domain', 'Doména momondo', 'Napr. www.momondo.com, www.momondo.de' );
					?>
				</table>

				<h2>Exotika (spiatočné)</h2>
				<table class="form-table" role="presentation">
					<?php
					self::field( 'exotic_origins', 'Odletové letiská', 'IATA kódy oddelené čiarkou, napr. VIE,BTS,BUD' );
					self::field( 'exotic_destinations', 'Destinácie', 'Napr. BKK,DXB,AUH' );
					self::field( 'exotic_months', 'Horizont (mesiace)', '', 'number' );
					?>
				</table>

				<h2>Európske mestá (jednosmerné)</h2>
				<table class="form-table" role="presentation">
					<?php
					self::field( 'europe_origins', 'Odletové letiská', 'Napr. BTS,VIE' );
					self::field( 'europe_days', 'Horizont (dni)', '', 'number' );
					self::field( 'europe_max_price', 'Max. cena (EUR)', '0 = bez limitu', 'number' );
					self::field( 'europe_only', 'Len Európa', 'Skryť Maroko, Egypt, Jordánsko a pod.', 'checkbox' );
					?>
				</table>

				<h2>Ručné tipy (Kayak, momondo…)</h2>
				<table class="form-table" role="presentation">
					<?php
					self::field(
						'manual',
						'Ponuky',
						'Jeden riadok = jedna ponuka: <code>skupina;odkiaľ;kam;odlet;návrat;cena;zdroj;odkaz</code><br>'
						. 'Napr. <code>exotika;VIE;BKK;2026-11-10;2026-11-24;389;momondo;https://www.momondo.com/…</code><br>'
						. 'Kayak ani momondo neposkytujú verejné API – akciu, ktorú tam nájdete, sem vložte ručne. Po dátume odletu zmizne sama.',
						'textarea'
					);
					?>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2>Použitie</h2>
			<p><code>[spoznajsvet_letenky]</code> · <code>[spoznajsvet_letenky skupina="exotika"]</code> · <code>[spoznajsvet_letenky skupina="europa" limit="20"]</code> · alebo blok <strong>Letenky</strong> v editore.</p>
		</div>
		<?php
	}
}
