<?php
/**
 * Karta článku pre stránku Články (redakčný štýl – text pod fotkou).
 *
 * Argumenty: variant (featured|grid).
 *
 * @package spoznajsvet
 */

$variant  = $args['variant'] ?? 'grid';
$featured = 'featured' === $variant;
$places   = sst_post_places();
$when     = get_post_meta( get_the_ID(), 'sst_when', true );
$days     = (int) get_post_meta( get_the_ID(), 'sst_days', true );
$link     = $places['city'] ? $places['city'] : $places['country'];
?>
<article <?php post_class( 'article-card article-card--' . $variant ); ?>>
	<div class="article-card__media">
		<?php
		sst_post_image(
			$featured ? 'sst-wide' : 'medium_large',
			$featured ? array(
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			) : array()
		);
		?>
		<?php if ( $featured && ! is_paged() ) : ?>
			<span class="article-card__badge">Najnovší príbeh</span>
		<?php endif; ?>
	</div>
	<div class="article-card__body">
		<div class="article-card__top">
			<?php if ( $link ) : ?>
				<a class="article-card__place" href="<?php echo esc_url( get_term_link( $link ) ); ?>">
					<?php echo esc_html( trim( sst_term_flag( $places['country'] ) . ' ' . ( $places['city'] ? $places['city']->name . ', ' . $places['country']->name : $places['country']->name ) ) ); ?>
				</a>
			<?php endif; ?>
			<time class="article-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j. n. Y' ) ); ?></time>
		</div>
		<?php if ( $featured ) : ?>
			<h2 class="article-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<?php else : ?>
			<h3 class="article-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php endif; ?>
		<p class="article-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), $featured ? 34 : 20, '…' ) ); ?></p>
		<ul class="article-card__meta">
			<?php if ( $when ) : ?>
				<li><?php echo sst_icon( 'sun', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $when ); ?></li>
			<?php endif; ?>
			<?php if ( $days ) : ?>
				<li><?php echo sst_icon( 'calendar', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $days . ' ' . sst_plural( $days, 'deň', 'dni', 'dní' ) ); ?></li>
			<?php endif; ?>
			<li><?php echo sst_icon( 'clock', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( sst_reading_time() . ' min' ); ?></li>
		</ul>
		<?php if ( $featured ) : ?>
			<span class="article-card__more" aria-hidden="true">Čítať príbeh <?php echo sst_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<?php endif; ?>
	</div>
</article>
