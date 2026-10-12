<?php
defined( 'ABSPATH' ) || exit;

/**
 * Výstup: shortcode [spoznajsvet_letenky], blok „spoznajsvet/letenky“.
 *
 * [spoznajsvet_letenky skupina="exotika|europa|vsetko" limit="12" nadpis="1" kompaktne="0"]
 */
class SVL_Render {

	public static function init() {
		add_shortcode( 'spoznajsvet_letenky', array( __CLASS__, 'shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		wp_register_style( 'svl', SVL_URL . 'assets/letenky.css', array(), SVL_VERSION );
		wp_register_script( 'svl', SVL_URL . 'assets/letenky.js', array(), SVL_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_register_script(
			'svl-block',
			SVL_URL . 'assets/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
			SVL_VERSION,
			true
		);
		register_block_type(
			'spoznajsvet/letenky',
			array(
				'api_version'     => 3,
				'title'           => 'Letenky',
				'editor_script'   => 'svl-block',
				'style'           => 'svl',
				'render_callback' => array( __CLASS__, 'block' ),
				'attributes'      => array(
					'skupina'   => array(
						'type'    => 'string',
						'default' => 'vsetko',
					),
					'limit'     => array(
						'type'    => 'number',
						'default' => 12,
					),
					'nadpis'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'kompaktne' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	public static function block( $attrs ) {
		$attrs['nadpis']    = ! empty( $attrs['nadpis'] ) ? '1' : '0';
		$attrs['kompaktne'] = ! empty( $attrs['kompaktne'] ) ? '1' : '0';
		return self::shortcode( $attrs );
	}

	public static function shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'skupina'   => 'vsetko',
				'limit'     => 12,
				'nadpis'    => '1',
				'kompaktne' => '0',
				'stav'      => '1',
			),
			(array) $atts,
			'spoznajsvet_letenky'
		);
		$group   = in_array( $atts['skupina'], array( 'exotika', 'europa' ), true ) ? $atts['skupina'] : 'vsetko';
		$limit   = max( 1, (int) $atts['limit'] );
		$heading = '1' === (string) $atts['nadpis'] || 'true' === (string) $atts['nadpis'];
		$compact = '1' === (string) $atts['kompaktne'] || 'true' === (string) $atts['kompaktne'];

		wp_enqueue_style( 'svl' );
		wp_enqueue_script( 'svl' );

		$status  = ! in_array( (string) $atts['stav'], array( '0', 'false' ), true );

		return self::html( $group, $limit, $heading, $compact, $status );
	}

	public static function html( $group, $limit, $heading, $compact, $status = true ) {
		$data    = SVL_Store::data();
		$updated = (int) $data['updated'];
		$next    = SVL_Cron::next_run();
		// Na vyžiadanie: ceny staršie ako 15 min sa začnú obnovovať na pozadí.
		$refreshing = SVL_Cron::refresh_if_stale();
		$rest    = add_query_arg(
			array(
				'skupina'   => $group,
				'limit'     => $limit,
				'nadpis'    => $heading ? 1 : 0,
				'kompaktne' => $compact ? 1 : 0,
				'stav'      => $status ? 1 : 0,
			),
			rest_url( 'spoznajsvet/v1/letenky' )
		);

		ob_start();
		?>
		<section class="svl<?php echo $compact ? ' svl--compact' : ''; ?>" data-svl data-updated="<?php echo esc_attr( $updated ); ?>" data-next="<?php echo esc_attr( $next ); ?>" data-rest="<?php echo esc_url( $rest ); ?>" data-refresh="<?php echo esc_url( rest_url( 'spoznajsvet/v1/letenky/obnovit' ) ); ?>" data-max-age="<?php echo (int) SVL_Cron::MAX_AGE; ?>"<?php echo $refreshing ? ' data-refreshing="1"' : ''; ?>>
			<div class="svl-status"<?php echo $status ? '' : ' hidden'; ?>>
				<span class="svl-dot" aria-hidden="true"></span>
				<span class="svl-status__text" role="status" data-svl-status>
					<?php if ( $refreshing ) : ?>
						Hľadám najnovšie ceny…
					<?php elseif ( $updated ) : ?>
						Aktualizované <time data-svl-ago datetime="<?php echo esc_attr( gmdate( 'c', $updated ) ); ?>"><?php echo esc_html( SVL_Format::ago( $updated ) ); ?></time>
					<?php else : ?>
						Ceny sa práve načítavajú…
					<?php endif; ?>
				</span>
				<button type="button" class="svl-status__btn" data-svl-refresh aria-label="Obnoviť ceny leteniek">
					<span class="svl-status__icon" aria-hidden="true"></span><span class="svl-status__label">Obnoviť</span>
				</button>
			</div>
			<?php
			if ( 'europa' !== $group ) {
				self::exotic( $heading, $compact, 9 );
			}
			if ( 'exotika' !== $group ) {
				self::europe( $heading, $limit, $compact );
			}
			?>
			<p class="svl-note">
				Všetky ceny sú celkové ceny spiatočnej letenky (tam aj späť) za 1 osobu v EUR. Menia sa každú chvíľu – pred kúpou ich vždy overte.
				Zdroje: Ryanair, Wizz Air, Aviasales · porovnanie: Kayak, momondo.
			</p>
		</section>
		<?php
		return trim( ob_get_clean() );
	}

	/** „z Bratislavy“ */
	public static function from_label( $iata, $city = '' ) {
		$map = apply_filters(
			'svl_from_labels',
			array(
				'BTS' => 'z Bratislavy',
				'VIE' => 'z Viedne',
				'BUD' => 'z Budapešti',
				'KSC' => 'z Košíc',
				'PRG' => 'z Prahy',
				'KRK' => 'z Krakova',
			)
		);
		if ( isset( $map[ $iata ] ) ) {
			return $map[ $iata ];
		}
		$info = svl_airport( $iata, $city );
		return 'z ' . $info['city'];
	}

	/**
	 * Tlačidlo, ktoré otvorí ponuku v novom okne. Zámerne nie je to <a href>, aby prehliadač
	 * pri prejdení myšou neukazoval dlhú adresu v rohu obrazovky.
	 */
	private static function go( $url, $label, $class = 'svl-btn' ) {
		return sprintf(
			'<button type="button" class="%1$s" data-svl-go="%2$s">%3$s</button>',
			esc_attr( $class ),
			esc_url( $url ),
			esc_html( $label )
		);
	}

	private static function admin_token_note() {
		if ( '' !== (string) SVL_Settings::get( 'tp_token' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="svl-admin-note" role="note">
			<strong>Len pre teba (admin):</strong> lety do Ázie a exotiky s cenou a aerolinkou vyhľadáva Aviasales (Travelpayouts). Potrebuje bezplatný token:
			<ol>
				<li>Zaregistruj sa zadarmo na <a href="https://www.travelpayouts.com/" target="_blank" rel="noopener">travelpayouts.com</a>.</li>
				<li>V ich administrácii otvor <em>Tools → API</em> (Nástroje → API) a skopíruj <em>API token</em>.</li>
				<li>Vlož ho do <a href="<?php echo esc_url( admin_url( 'options-general.php?page=svl-letenky' ) ); ?>">Nastavenia → Letenky</a> a klikni „Aktualizovať teraz“.</li>
			</ol>
		</div>
		<?php
	}

	/** Najlacnejšia ponuka zo zoznamu (len spiatočné). */
	private static function cheapest( array $list ) {
		$best = null;
		foreach ( $list as $d ) {
			if ( $d['return'] && ( null === $best || $d['price'] < $best['price'] ) ) {
				$best = $d;
			}
		}
		return $best;
	}

	private static function exotic( $heading, $compact, $limit ) {
		$origins = SVL_Settings::codes( 'exotic_origins' );
		?>
		<div class="svl-block svl-block--exotic">
			<?php if ( $heading ) : ?>
				<header class="svl-head">
					<p class="svl-eyebrow">Ázia a exotika</p>
					<h2 class="svl-title">Najlacnejšie spiatočné letenky za teplom</h2>
					<p class="svl-lead">Spiatočné letenky <?php echo esc_html( self::origin_sentence( $origins ) ); ?> od Wizz Air, Ryanairu aj klasických aerolínií. Do každého mesta len tá najlacnejšia.</p>
				</header>
			<?php endif; ?>
			<?php self::admin_token_note(); ?>
			<?php self::asia_ranking( $compact ? 4 : $limit ); ?>
			<?php
			$by_dest = array();
			foreach ( SVL_Store::deals( 'exotika' ) as $d ) {
				$by_dest[ $d['destination'] ][] = $d;
			}
			?>
			<div class="svl-exo-grid">
				<?php
				foreach ( SVL_Settings::codes( 'exotic_destinations' ) as $dest ) {
					self::exotic_card( $dest, self::cheapest( $by_dest[ $dest ] ?? array() ), $origins );
				}
				?>
			</div>
		</div>
		<?php
	}

	/** Rebríček: najlacnejšia spiatočná letenka do každého mesta v Ázii a exotike. */
	private static function asia_ranking( $limit ) {
		$best = array();
		foreach ( array_merge( SVL_Store::deals( 'azia' ), SVL_Store::deals( 'exotika' ) ) as $d ) {
			$key = $d['dest_city'] ? $d['dest_city'] : $d['destination'];
			if ( $d['return'] && ( ! isset( $best[ $key ] ) || $d['price'] < $best[ $key ]['price'] ) ) {
				$best[ $key ] = $d;
			}
		}
		if ( ! $best ) {
			return;
		}
		usort(
			$best,
			static function ( $a, $b ) {
				return $a['price'] <=> $b['price'];
			}
		);
		?>
		<ol class="svl-rank svl-tix-grid">
			<?php
			foreach ( array_slice( $best, 0, max( 1, (int) $limit ) ) as $i => $d ) {
				self::ticket( $d, array( 'rank' => $i + 1 ) );
			}
			?>
		</ol>
		<?php
	}

	private static function origin_sentence( array $origins ) {
		// „z Viedne, Bratislavy a Budapešti“.
		$names = array();
		foreach ( $origins as $o ) {
			$names[] = preg_replace( '/^z\s+/u', '', self::from_label( $o ) );
		}
		if ( ! $names ) {
			return '';
		}
		$last = array_pop( $names );
		return 'z ' . ( $names ? implode( ', ', $names ) . ' a ' : '' ) . $last;
	}

	/** Karta destinácie s jedinou – najlacnejšou spiatočnou – letenkou. */
	private static function exotic_card( $dest, $min, array $origins ) {
		$info = svl_airport( $dest );
		$img  = apply_filters( 'svl_destination_image', '', $dest, $info['city'] );
		?>
		<article class="svl-exo" data-dest="<?php echo esc_attr( $dest ); ?>">
			<div class="svl-exo__hero<?php echo $img ? ' has-img' : ''; ?>"<?php echo $img ? ' style="--svl-img:url(' . esc_url( $img ) . ')"' : ''; ?>>
				<div class="svl-exo__top">
					<span class="svl-glass"><?php echo esc_html( SVL_Format::flag( $info['iso'] ) . ' ' . $info['country'] ); ?></span>
					<?php if ( $min && $min['low'] ) : ?>
						<span class="svl-badge">Najnižšia cena za 30 dní</span>
					<?php endif; ?>
				</div>
				<div class="svl-exo__bottom">
					<h3 class="svl-exo__city"><?php echo esc_html( $info['city'] ); ?></h3>
					<?php if ( $min ) : ?>
						<p class="svl-exo__from">spiatočná letenka od <strong><?php echo esc_html( SVL_Format::price( $min['price'] ) ); ?></strong>
						</p>
					<?php else : ?>
						<p class="svl-exo__from">porovnajte aktuálne ceny</p>
					<?php endif; ?>
				</div>
			</div>
			<ul class="svl-exo__list">
				<?php
				if ( $min ) {
					self::ticket( $min, array( 'head' => false ) );
				} else {
					$o     = $origins ? $origins[0] : 'VIE';
					$links = SVL_Format::compare_links( $o, $dest, gmdate( 'Y-m-d', strtotime( '+30 days' ) ), gmdate( 'Y-m-d', strtotime( '+44 days' ) ) );
					?>
					<li class="svl-flight svl-flight--empty">
						<div class="svl-flight__info"><strong>Spiatočné letenky</strong><span>Hľadať na porovnávačoch</span></div>
						<div class="svl-flight__cta">
							<?php echo self::go( $links['kayak'], 'Kayak', 'svl-link' ) . self::go( $links['momondo'], 'momondo', 'svl-link' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					</li>
					<?php
				}
				?>
			</ul>
		</article>
		<?php
	}

	/**
	 * Letenka ako „palubný lístok“: trasa, termín tam a späť, aerolinka a celková cena.
	 *
	 * @param array $args head (bool) – hlavička s mestom, rank (int), class, attrs (data-*).
	 */
	private static function ticket( array $d, array $args = array() ) {
		$args    = wp_parse_args(
			$args,
			array(
				'head'  => true,
				'rank'  => 0,
				'class' => '',
				'attrs' => array(),
			)
		);
		$links   = SVL_Format::compare_links( $d['origin'], $d['destination'], $d['depart'], $d['return'] );
		$url     = $d['url'] ? $d['url'] : $links['kayak'];
		$nights  = SVL_Format::nights( $d['depart'], $d['return'] );
		$stops   = SVL_Format::stops( $d['stops'] );
		$from    = svl_airport( $d['origin'], $d['origin_city'] ?? '' );
		$airline = $d['airline'] ? $d['airline'] : SVL_Format::source_label( $d['source'] );
		$attrs   = '';
		foreach ( $args['attrs'] as $k => $v ) {
			$attrs .= ' ' . $k . '="' . esc_attr( $v ) . '"';
		}
		$classes = trim( 'svl-tix ' . $args['class'] . ( 1 === $args['rank'] ? ' is-top' : '' ) );
		?>
		<li class="<?php echo esc_attr( $classes ); ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php if ( $args['head'] ) : ?>
				<div class="svl-tix__head">
					<span class="svl-tix__flag" aria-hidden="true"><?php echo esc_html( SVL_Format::flag( $d['dest_iso'] ) ); ?></span>
					<span class="svl-tix__dest">
						<strong class="svl-tix__city"><?php echo esc_html( $d['dest_city'] ); ?></strong>
						<span class="svl-tix__country"><?php echo esc_html( $d['dest_country'] ); ?></span>
					</span>
					<?php if ( 1 === $args['rank'] ) : ?>
						<span class="svl-tix__tag svl-tix__tag--gold">Najlacnejšie</span>
					<?php elseif ( $args['rank'] ) : ?>
						<span class="svl-tix__tag">#<?php echo (int) $args['rank']; ?></span>
					<?php elseif ( ! empty( $d['low'] ) ) : ?>
						<span class="svl-tix__tag svl-tix__tag--gold">Top cena</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="svl-tix__route">
				<span class="svl-tix__pt">
					<b><?php echo esc_html( $d['origin'] ); ?></b>
					<small><?php echo esc_html( $from['city'] ); ?></small>
				</span>
				<span class="svl-tix__line">
					<span class="svl-tix__plane" aria-hidden="true"></span>
					<?php if ( $stops ) : ?>
						<span class="svl-tix__stops<?php echo 0 === $d['stops'] ? ' is-direct' : ''; ?>"><?php echo esc_html( $stops ); ?></span>
					<?php endif; ?>
				</span>
				<span class="svl-tix__pt svl-tix__pt--end">
					<b><?php echo esc_html( $d['destination'] ); ?></b>
					<small><?php echo esc_html( $d['dest_city'] ); ?></small>
				</span>
			</div>
			<div class="svl-tix__dates">
				<span class="svl-tix__date"><small>Tam</small><b><?php echo esc_html( SVL_Format::date( $d['depart'] ) ); ?></b></span>
				<?php if ( $nights ) : ?>
					<span class="svl-tix__nights"><?php echo esc_html( $nights . ' ' . SVL_Format::plural( $nights, 'noc', 'noci', 'nocí' ) ); ?></span>
				<?php endif; ?>
				<span class="svl-tix__date svl-tix__date--end"><small>Späť</small><b><?php echo esc_html( SVL_Format::date( $d['return'] ) ); ?></b></span>
			</div>
			<div class="svl-tix__foot">
				<span class="svl-tix__airline">
					<?php echo SVL_Format::airline_logo( $d['airline_code'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><b><?php echo esc_html( $airline ); ?></b><?php if ( SVL_Format::source_label( $d['source'] ) !== $airline ) : ?><small>cez <?php echo esc_html( SVL_Format::source_label( $d['source'] ) ); ?></small><?php endif; ?></span>
				</span>
				<span class="svl-tix__price">
					<small>tam aj späť</small>
					<strong><?php echo esc_html( SVL_Format::price( $d['price'] ) ); ?></strong>
				</span>
				<button type="button" class="svl-tix__go" data-svl-go="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( 'Zobraziť letenku ' . $d['origin'] . ' – ' . $d['dest_city'] . ' za ' . SVL_Format::price( $d['price'] ) ); ?>">
					<span class="svl-tix__go-label">Zobraziť</span>
				</button>
			</div>
			<div class="svl-tix__compare">
				<span>Porovnať</span>
				<?php echo self::go( $links['kayak'], 'Kayak', 'svl-compare__btn' ) . self::go( $links['momondo'], 'momondo', 'svl-compare__btn' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</li>
		<?php
	}

	private static function europe( $heading, $limit, $compact ) {
		$deals   = SVL_Store::deals( 'europa' );
		$origins = SVL_Settings::codes( 'europe_origins' );

		// Najlacnejšia ponuka do každej destinácie spomedzi všetkých letísk.
		$best = array();
		foreach ( $deals as $i => $d ) {
			$key = $d['dest_city'] ? $d['dest_city'] : $d['destination'];
			if ( ! isset( $best[ $key ] ) || $d['price'] < $deals[ $best[ $key ] ]['price'] ) {
				$best[ $key ] = $i;
			}
		}
		$best = array_flip( $best );
		?>
		<div class="svl-block svl-block--europe" data-svl-eu data-limit="<?php echo esc_attr( $limit ); ?>">
			<?php if ( $heading ) : ?>
				<header class="svl-head">
					<p class="svl-eyebrow">Európa</p>
					<h2 class="svl-title">Najlacnejšie mestá na víkend</h2>
					<p class="svl-lead">Spiatočné letenky <?php echo esc_html( self::origin_sentence( $origins ) ); ?> na <?php echo esc_html( SVL_Settings::get( 'europe_min_nights' ) . ' – ' . SVL_Settings::get( 'europe_max_nights' ) ); ?> nocí – zoradené od najlacnejšej.</p>
				</header>
			<?php endif; ?>
			<div class="svl-toolbar">
				<div class="svl-chips" role="group" aria-label="Odletové letisko">
					<button type="button" class="svl-chip is-active" data-origin="all" aria-pressed="true">Všetky</button>
					<?php foreach ( $origins as $o ) : ?>
						<button type="button" class="svl-chip" data-origin="<?php echo esc_attr( $o ); ?>" aria-pressed="false"><?php echo esc_html( self::from_label( $o ) ); ?></button>
					<?php endforeach; ?>
				</div>
				<?php if ( ! $compact ) : ?>
					<div class="svl-chips svl-chips--sort" role="group" aria-label="Zoradiť">
						<button type="button" class="svl-chip is-active" data-sort="price" aria-pressed="true">Najlacnejšie</button>
						<button type="button" class="svl-chip" data-sort="date" aria-pressed="false">Najskôr</button>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( ! $deals ) : ?>
				<p class="svl-empty">Ponuky sa načítavajú alebo sú momentálne nedostupné. Skúste
					<?php echo self::go( 'https://' . SVL_Settings::get( 'kayak_domain' ) . '/explore/' . ( $origins ? $origins[0] : 'BTS' ) . '-anywhere', 'Kayak Explore', 'svl-compare__btn' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>.
				</p>
			<?php else : ?>
				<ol class="svl-eu-list svl-tix-grid">
					<?php
					foreach ( $deals as $i => $d ) {
						self::ticket(
							$d,
							array(
								'class' => 'svl-eu',
								'attrs' => array(
									'data-origin' => $d['origin'],
									'data-best'   => isset( $best[ $i ] ) ? '1' : '0',
									'data-price'  => $d['price'],
									'data-date'   => $d['depart'],
								),
							)
						);
					}
					?>
				</ol>
				<button type="button" class="svl-more" data-svl-more hidden>Zobraziť ďalšie destinácie</button>
			<?php endif; ?>
		</div>
		<?php
	}
}
