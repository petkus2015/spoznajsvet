<?php
/**
 * Článok.
 *
 * @package spoznajsvet
 */

get_header();

while ( have_posts() ) :
	the_post();
	$places = sst_post_places();
	$cover  = has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'sst-hero' ) : ( $places['country'] ? sst_term_cover_url( $places['country'], 'sst-hero' ) : '' );
	$when   = get_post_meta( get_the_ID(), 'sst_when', true );
	$days   = (int) get_post_meta( get_the_ID(), 'sst_days', true );
	?>
	<div class="read-progress" aria-hidden="true"><span data-read-progress></span></div>

	<article <?php post_class( 'article' ); ?>>
		<header class="page-hero page-hero--post"<?php echo $cover ? ' style="--hero-img:url(' . esc_url( $cover ) . ')"' : ''; ?>>
			<div class="container page-hero__inner">
		<div class="frost page-hero__box">
				<?php
				if ( $places['city'] ) {
					sst_breadcrumbs( $places['city'] );
				} elseif ( $places['country'] ) {
					sst_breadcrumbs( $places['country'] );
				}
				?>
				<h1 class="page-hero__title page-hero__title--post"><?php the_title(); ?></h1>
				<p class="page-hero__meta">
					<span><?php echo sst_icon( 'calendar', 16 ); // phpcs:ignore ?> <?php echo esc_html( get_the_date( 'j. n. Y' ) ); ?></span>
					<span><?php echo sst_icon( 'clock', 16 ); // phpcs:ignore ?> <?php echo (int) sst_reading_time(); ?> min čítania</span>
				</p>
				</div>
			</div>
		</header>

		<div class="container article__layout">
			<?php if ( $places['country'] || $when || $days ) : ?>
				<aside class="trip-facts" aria-label="O ceste">
					<?php if ( $places['country'] ) : ?>
						<a class="trip-facts__item" href="<?php echo esc_url( get_term_link( $places['country'] ) ); ?>">
							<span class="trip-facts__icon"><?php echo esc_html( sst_term_flag( $places['country'] ) ? sst_term_flag( $places['country'] ) : '🌍' ); ?></span>
							<span><small>Krajina</small><strong><?php echo esc_html( $places['country']->name ); ?></strong></span>
						</a>
					<?php endif; ?>
					<?php foreach ( $places['cities'] as $city ) : ?>
						<a class="trip-facts__item" href="<?php echo esc_url( get_term_link( $city ) ); ?>">
							<span class="trip-facts__icon"><?php echo sst_icon( 'pin', 18 ); // phpcs:ignore ?></span>
							<span><small>Miesto</small><strong><?php echo esc_html( $city->name ); ?></strong></span>
						</a>
					<?php endforeach; ?>
					<?php if ( $when ) : ?>
						<div class="trip-facts__item">
							<span class="trip-facts__icon"><?php echo sst_icon( 'sun', 18 ); // phpcs:ignore ?></span>
							<span><small>Kedy</small><strong><?php echo esc_html( $when ); ?></strong></span>
						</div>
					<?php endif; ?>
					<?php if ( $days ) : ?>
						<div class="trip-facts__item">
							<span class="trip-facts__icon"><?php echo sst_icon( 'calendar', 18 ); // phpcs:ignore ?></span>
							<span><small>Dĺžka</small><strong><?php echo esc_html( $days . ' ' . sst_plural( $days, 'deň', 'dni', 'dní' ) ); ?></strong></span>
						</div>
					<?php endif; ?>
				</aside>
			<?php endif; ?>

			<div class="entry-content">
				<?php
				the_content();
				wp_link_pages(
					array(
						'before' => '<nav class="page-links">Strany: ',
						'after'  => '</nav>',
					)
				);
				?>
			</div>

			<footer class="article__footer">
				<?php the_tags( '<div class="chips chips--wrap">', '', '</div>' ); ?>
				<button type="button" class="btn btn--outline" data-share data-title="<?php echo esc_attr( get_the_title() ); ?>" data-url="<?php echo esc_url( get_permalink() ); ?>">
					<?php echo sst_icon( 'share', 18 ); // phpcs:ignore ?> <span data-share-label>Zdieľať</span>
				</button>
			</footer>
		</div>
	</article>

	<?php
	// Súvisiace: najprv rovnaké mesto, potom krajina.
	$related_term = $places['city'] ? $places['city'] : $places['country'];
	if ( $related_term ) :
		$related = new WP_Query(
			array(
				'posts_per_page'      => 3,
				'post__not_in'        => array( get_the_ID() ),
				'category__not_in'    => sst_klook_exclude(),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => 'destinacia',
						'terms'    => $related_term->term_id,
					),
				),
			)
		);
		if ( $related->post_count < 3 && $places['city'] && $places['country'] ) {
			$related = new WP_Query(
				array(
					'posts_per_page'      => 3,
					'post__not_in'        => array( get_the_ID() ),
					'category__not_in'    => sst_klook_exclude(),
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array(
							'taxonomy' => 'destinacia',
							'terms'    => $places['country']->term_id,
						),
					),
				)
			);
		}
		if ( $related->have_posts() ) :
			?>
			<section class="section section--tight">
				<div class="container">
					<header class="section-head">
						<div>
							<h2 class="section-title">Ďalšie z krajiny <?php echo esc_html( $places['country']->name ); ?></h2>
						</div>
						<a class="link-arrow" href="<?php echo esc_url( get_term_link( $places['country'] ) ); ?>">Všetko <?php echo sst_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
					</header>
					<div class="post-grid">
						<?php
						while ( $related->have_posts() ) :
							$related->the_post();
							get_template_part( 'template-parts/card-post' );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
			<?php
		endif;
	endif;

	if ( comments_open() || get_comments_number() ) :
		?>
		<section class="section section--tight">
			<div class="container container--narrow">
				<?php comments_template(); ?>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
