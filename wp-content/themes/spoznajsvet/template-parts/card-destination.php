<?php
/**
 * Karta krajiny/mesta (štýl „Patagonia“ z inšpirácie).
 *
 * Argumenty: term (WP_Term), variant (tall|compact).
 *
 * @package spoznajsvet
 */

$term = $args['term'] ?? null;
if ( ! $term instanceof WP_Term ) {
	return;
}
$variant = $args['variant'] ?? 'tall';
$img     = sst_term_cover_url( $term, 'sst-card' );
$flag    = sst_term_flag( $term );
$count   = sst_term_count( $term );

if ( $term->parent ) {
	$kicker = sst_term_country( $term )->name;
} else {
	$continent = get_term_meta( $term->term_id, 'sst_continent', true );
	$labels    = sst_continents();
	$kicker    = $labels[ $continent ] ?? '';
}
$cities = $term->parent ? array() : sst_cities( $term );
?>
<article class="dest-card dest-card--<?php echo esc_attr( $variant ); ?>" data-name="<?php echo esc_attr( remove_accents( mb_strtolower( $term->name . ' ' . implode( ' ', wp_list_pluck( $cities, 'name' ) ) ) ) ); ?>">
	<a class="dest-card__link" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
		<?php if ( $img ) : ?>
			<img class="dest-card__img" src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" decoding="async">
		<?php else : ?>
			<span class="dest-card__img img-placeholder" aria-hidden="true"><?php echo sst_icon( 'compass', 48 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<?php endif; ?>
		<span class="dest-card__body">
			<?php if ( $kicker ) : ?>
				<span class="dest-card__kicker"><?php echo esc_html( $kicker ); ?></span>
			<?php endif; ?>
			<span class="dest-card__name"><?php echo esc_html( trim( $term->name . ' ' . $flag ) ); ?></span>
			<span class="dest-card__meta">
				<?php
				$bits = array( $count . ' ' . sst_plural( $count, 'článok', 'články', 'článkov' ) );
				if ( $cities ) {
					$bits[] = count( $cities ) . ' ' . sst_plural( count( $cities ), 'miesto', 'miesta', 'miest' );
				}
				echo esc_html( implode( ' · ', $bits ) );
				?>
			</span>
		</span>
	</a>
</article>
