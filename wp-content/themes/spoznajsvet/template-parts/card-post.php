<?php
/**
 * Karta článku (štýl „My trips“) – fotka s popisom cez ňu.
 *
 * Argumenty: variant (default|wide|row).
 *
 * @package spoznajsvet
 */

$variant = $args['variant'] ?? 'default';
$place   = sst_place_label();
$meta    = sst_trip_meta( null, 'row' !== $variant );
?>
<article <?php post_class( 'trip-card trip-card--' . $variant ); ?>>
	<a class="trip-card__link" href="<?php the_permalink(); ?>">
		<span class="trip-card__media">
			<?php sst_post_image( 'wide' === $variant ? 'sst-wide' : 'sst-card' ); ?>
		</span>
		<span class="trip-card__body">
			<?php if ( $place ) : ?>
				<span class="trip-card__place"><?php echo esc_html( $place ); ?></span>
			<?php endif; ?>
			<span class="trip-card__title"><?php the_title(); ?></span>
			<?php if ( $meta ) : ?>
				<span class="trip-card__meta"><?php echo esc_html( $meta ); ?></span>
			<?php endif; ?>
		</span>
	</a>
</article>
