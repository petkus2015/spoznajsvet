<?php
/**
 * Úvodná stránka.
 *
 * @package spoznajsvet
 */

get_header();

$hero      = sst_hero_image_url();
$countries = sst_countries( array( 'orderby' => 'count', 'order' => 'DESC' ) );
$latest    = new WP_Query(
	array(
		'posts_per_page'      => 7,
		'ignore_sticky_posts' => true,
		'category__not_in'    => sst_klook_exclude(),
		'no_found_rows'       => true,
	)
);
?>

<section class="hero"<?php echo $hero ? ' style="--hero-img:url(' . esc_url( $hero ) . ')"' : ''; ?>>
	<div class="container hero__grid">
		<div class="hero__copy">
			<h1 class="hero__title"><?php echo esc_html( trim( sst_mod( 'sst_hero_title' ) . ' ' . sst_mod( 'sst_hero_accent' ) ) ); ?></h1>
			<p class="hero__text"><?php echo esc_html( sst_mod( 'sst_hero_text' ) ); ?></p>
			<div class="hero__actions">
				<a class="btn btn--primary" href="<?php echo esc_url( sst_destinations_url() ); ?>"><?php echo sst_icon( 'globe', 18 ); // phpcs:ignore ?> Kam ďalej?</a>
				<?php if ( sst_flights_url() ) : ?>
					<a class="btn btn--glass" href="<?php echo esc_url( sst_flights_url() ); ?>"><?php echo sst_icon( 'plane', 18 ); // phpcs:ignore ?> Lacné letenky</a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $countries ) : ?>
			<div class="hero__carousel" data-carousel>
				<div class="hero__carousel-track" data-carousel-track>
					<?php foreach ( array_slice( $countries, 0, 8 ) as $c ) : ?>
						<div class="hero__slide">
							<?php get_template_part( 'template-parts/card-destination', null, array( 'term' => $c ) ); ?>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="hero__controls">
					<button type="button" class="round-btn" data-carousel-prev aria-label="Predchádzajúca krajina"><?php echo sst_icon( 'arrow-left', 18 ); // phpcs:ignore ?></button>
					<button type="button" class="round-btn" data-carousel-next aria-label="Ďalšia krajina"><?php echo sst_icon( 'arrow-right', 18 ); // phpcs:ignore ?></button>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if ( $countries || sst_map_data()['units'] || current_user_can( 'manage_categories' ) ) : ?>
	<section class="section">
		<div class="container">
			<header class="section-head">
				<div>
					<h2 class="section-title">Kde som bol</h2>
				</div>
				<a class="link-arrow" href="<?php echo esc_url( sst_destinations_url() ); ?>">Všetky <?php echo sst_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
			</header>
			<?php sst_world_map( array( 'title' => false ) ); ?>
			<div class="chips chips--scroll world-map__chips" role="list">
				<?php foreach ( $countries as $c ) : ?>
					<a role="listitem" class="chip" href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( trim( sst_term_flag( $c ) . ' ' . $c->name ) ); ?> <span class="chip__count"><?php echo (int) $c->count; ?></span></a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $latest->have_posts() ) : ?>
	<section class="section section--tight">
		<div class="container">
			<header class="section-head">
				<div>
					<h2 class="section-title">Najnovšie články</h2>
				</div>
				<a class="link-arrow" href="<?php echo esc_url( sst_blog_url() ); ?>">Všetky články <?php echo sst_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
			</header>
			<div class="post-grid post-grid--featured">
				<?php
				$i = 0;
				while ( $latest->have_posts() ) :
					$latest->the_post();
					get_template_part( 'template-parts/card-post', null, array( 'variant' => 0 === $i ? 'wide' : 'default' ) );
					++$i;
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( sst_mod( 'sst_home_flights' ) ) : ?>
	<section class="section section--flights">
		<div class="container">
			<header class="section-head">
				<div>
					<h2 class="section-title">Najlacnejšie letenky</h2>
					<p class="section-lead">Kontrolujem ceny Ryanair, Wizz Air a vyhľadávačov každých 30 minút. Keď si stránku otvoríš, nie sú staršie ako 15 minút.</p>
				</div>
				<?php if ( sst_flights_url() ) : ?>
					<a class="link-arrow" href="<?php echo esc_url( sst_flights_url() ); ?>">Všetky ponuky <?php echo sst_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
				<?php endif; ?>
			</header>
			<?php
			sst_flights(
				array(
					'skupina'   => 'vsetko',
					'limit'     => 6,
					'nadpis'    => '1',
					'kompaktne' => '1',
				)
			);
			?>
		</div>
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/about' ); ?>

<?php
get_footer();
