<?php
/**
 * Template Name: Destinácie (prehľad krajín a miest)
 *
 * @package spoznajsvet
 */

get_header();

$countries  = sst_countries();
$continents = sst_continents();
$grouped    = array();
foreach ( $countries as $c ) {
	$k               = get_term_meta( $c->term_id, 'sst_continent', true );
	$k               = isset( $continents[ $k ] ) ? $k : 'ostatne';
	$grouped[ $k ][] = $c;
}
$order = array_merge( array_keys( $continents ), array( 'ostatne' ) );
$hero  = $countries ? sst_term_cover_url( $countries[0], 'sst-hero' ) : '';
$total_cities = 0;
foreach ( $countries as $c ) {
	$total_cities += count( sst_cities( $c ) );
}
?>

<section class="page-hero page-hero--dest"<?php echo $hero ? ' style="--hero-img:url(' . esc_url( $hero ) . ')"' : ''; ?>>
	<div class="container page-hero__inner">
		<p class="eyebrow eyebrow--light">Destinácie</p>
		<h1 class="page-hero__title"><?php the_title(); ?></h1>
		<ul class="stats">
			<li><strong><?php echo count( $countries ); ?></strong> <?php echo esc_html( sst_plural( count( $countries ), 'krajina', 'krajiny', 'krajín' ) ); ?></li>
			<li><strong><?php echo (int) $total_cities; ?></strong> <?php echo esc_html( sst_plural( $total_cities, 'miesto', 'miesta', 'miest' ) ); ?></li>
		</ul>
		<label class="filter-input">
			<?php echo sst_icon( 'search', 20 ); // phpcs:ignore ?>
			<span class="screen-reader-text">Filtrovať krajiny a mestá</span>
			<input type="search" placeholder="Hľadať krajinu alebo mesto…" data-dest-filter autocomplete="off">
		</label>
	</div>
</section>

<?php
while ( have_posts() ) :
	the_post();
	if ( sst_has_page_content() ) :
		?>
		<div class="container container--narrow section section--tight entry-content"><?php the_content(); ?></div>
		<?php
	endif;
endwhile;
?>

<section class="section section--tight">
	<div class="container">
		<?php sst_world_map( array( 'title' => true ) ); ?>
	</div>
</section>

<section class="section section--tight">
	<div class="container">
		<?php if ( count( $grouped ) > 1 ) : ?>
			<div class="chips chips--scroll" data-continent-tabs>
				<button type="button" class="chip is-active" data-continent="">Všetko</button>
				<?php foreach ( $order as $k ) : ?>
					<?php if ( ! empty( $grouped[ $k ] ) ) : ?>
						<button type="button" class="chip" data-continent="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $continents[ $k ] ?? 'Ostatné' ); ?> <span class="chip__count"><?php echo count( $grouped[ $k ] ); ?></span></button>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! $countries ) : ?>
			<p class="empty">Zatiaľ tu nie sú žiadne destinácie. V administrácii pridajte krajiny a mestá v <em>Články → Destinácie</em>.</p>
		<?php endif; ?>

		<?php foreach ( $order as $k ) : ?>
			<?php
			if ( empty( $grouped[ $k ] ) ) {
				continue;
			}
			?>
			<div class="continent" data-continent-group="<?php echo esc_attr( $k ); ?>">
				<?php if ( count( $grouped ) > 1 ) : ?>
					<h2 class="continent__title"><?php echo esc_html( $continents[ $k ] ?? 'Ostatné' ); ?></h2>
				<?php endif; ?>
				<div class="country-list">
					<?php foreach ( $grouped[ $k ] as $c ) : ?>
						<?php $cities = sst_cities( $c ); ?>
						<div class="country-row" data-name="<?php echo esc_attr( remove_accents( mb_strtolower( $c->name . ' ' . implode( ' ', wp_list_pluck( $cities, 'name' ) ) ) ) ); ?>">
							<?php get_template_part( 'template-parts/card-destination', null, array( 'term' => $c, 'variant' => 'row' ) ); ?>
							<?php if ( $cities ) : ?>
								<div class="chips chips--wrap country-row__cities">
									<?php foreach ( $cities as $city ) : ?>
										<a class="chip chip--sm" href="<?php echo esc_url( get_term_link( $city ) ); ?>"><?php echo esc_html( $city->name ); ?> <span class="chip__count"><?php echo (int) $city->count; ?></span></a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
		<p class="empty" data-dest-empty hidden>Nič sa nenašlo.</p>
	</div>
</section>

<?php
get_footer();
