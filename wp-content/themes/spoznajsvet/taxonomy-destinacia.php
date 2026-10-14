<?php
/**
 * Krajina alebo mesto: /destinacie/taliansko/ a /destinacie/taliansko/florencia/
 *
 * @package spoznajsvet
 */

get_header();

$term    = get_queried_object();
$country = sst_term_country( $term );
$is_city = (bool) $term->parent;
$cover   = sst_term_cover_url( $term, 'sst-hero' );
$cities  = sst_cities( $is_city ? $term->parent : $term );
$flag    = sst_term_flag( $term );
$deal    = sst_cheapest_flight_to( $term );
?>

<section class="page-hero page-hero--dest"<?php echo $cover ? ' style="--hero-img:url(' . esc_url( $cover ) . ')"' : ''; ?>>
	<div class="container page-hero__inner">
		<?php sst_breadcrumbs( $term ); ?>
		<h1 class="page-hero__title">
			<?php if ( $flag ) : ?>
				<span class="page-hero__flag" aria-hidden="true"><?php echo esc_html( $flag ); ?></span>
			<?php endif; ?>
			<?php echo esc_html( $term->name ); ?>
		</h1>
		<?php if ( $term->description ) : ?>
			<div class="page-hero__text"><?php echo wp_kses_post( wpautop( $term->description ) ); ?></div>
		<?php endif; ?>
		<ul class="stats">
			<li><strong><?php echo (int) $term->count; ?></strong> <?php echo esc_html( sst_plural( $term->count, 'článok', 'články', 'článkov' ) ); ?></li>
			<?php if ( ! $is_city && $cities ) : ?>
				<li><strong><?php echo count( $cities ); ?></strong> <?php echo esc_html( sst_plural( count( $cities ), 'navštívené miesto', 'navštívené miesta', 'navštívených miest' ) ); ?></li>
			<?php endif; ?>
			<?php if ( $deal ) : ?>
				<li class="stats__deal">
					<a href="<?php echo esc_url( $deal['url'] ); ?>"><?php echo sst_icon( 'plane', 16 ); // phpcs:ignore ?> letenka tam aj späť od <strong><?php echo esc_html( $deal['price'] ); ?></strong></a>
				</li>
			<?php endif; ?>
		</ul>
	</div>
</section>

<?php if ( $cities ) : ?>
	<section class="section section--tight">
		<div class="container">
			<header class="section-head">
				<div>
					<p class="eyebrow"><?php echo esc_html( $country->name ); ?></p>
					<h2 class="section-title"><?php echo $is_city ? 'Ďalšie <em>miesta</em>' : 'Miesta, ktoré som <em>navštívil</em>'; // phpcs:ignore ?></h2>
				</div>
			</header>
			<div class="chips chips--scroll">
				<a class="chip<?php echo $is_city ? '' : ' is-active'; ?>" href="<?php echo esc_url( get_term_link( $country ) ); ?>">Všetko <span class="chip__count"><?php echo (int) $country->count; ?></span></a>
				<?php foreach ( $cities as $c ) : ?>
					<a class="chip<?php echo $c->term_id === $term->term_id ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $c ) ); ?>"<?php echo $c->term_id === $term->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?> <span class="chip__count"><?php echo (int) $c->count; ?></span></a>
				<?php endforeach; ?>
			</div>
			<?php if ( ! $is_city && count( $cities ) > 1 ) : ?>
				<div class="rail" data-carousel>
					<div class="rail__track" data-carousel-track>
						<?php foreach ( $cities as $c ) : ?>
							<?php get_template_part( 'template-parts/card-destination', null, array( 'term' => $c, 'variant' => 'compact' ) ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<section class="section section--tight">
	<div class="container">
		<header class="section-head">
			<div>
				<p class="eyebrow">Články</p>
				<h2 class="section-title"><?php echo esc_html( $is_city ? $term->name : 'Všetko o krajine ' . $term->name ); ?></h2>
			</div>
		</header>
		<?php if ( have_posts() ) : ?>
			<div class="post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/card-post' );
				endwhile;
				?>
			</div>
			<?php sst_pagination(); ?>
		<?php else : ?>
			<p class="empty">Zatiaľ tu nie je žiadny článok.</p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
