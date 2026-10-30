<?php
/**
 * Karta krajiny/mesta: vysoká fotka, názov a pod ním tmavý pás s cenou letenky alebo počtom článkov.
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
$deal   = function_exists( 'sst_cheapest_flight_to' ) ? sst_cheapest_flight_to( $term ) : null;
$sub    = array_filter( array( $kicker, $cities ? count( $cities ) . ' ' . sst_plural( count( $cities ), 'miesto', 'miesta', 'miest' ) : '' ) );
?>
<article class="dest-card dest-card--<?php echo esc_attr( $variant ); ?>" data-name="<?php echo esc_attr( remove_accents( mb_strtolower( $term->name . ' ' . implode( ' ', wp_list_pluck( $cities, 'name' ) ) ) ) ); ?>">
	<a class="dest-card__link" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
		<?php if ( $img ) : ?>
			<img class="dest-card__img" src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" decoding="async">
		<?php else : ?>
			<span class="dest-card__img img-placeholder" aria-hidden="true"><?php echo sst_icon( 'compass', 48 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<?php endif; ?>
		<span class="dest-card__body">
			<span class="dest-card__name"><?php echo esc_html( trim( $term->name . ' ' . $flag ) ); ?></span>
			<?php if ( $sub ) : ?>
				<span class="dest-card__kicker"><?php echo esc_html( implode( ' · ', $sub ) ); ?></span>
			<?php endif; ?>
		</span>
		<span class="dest-card__foot">
			<span><?php echo $deal ? esc_html( 'Letenka od ' . $deal['price'] ) : esc_html( $count . ' ' . sst_plural( $count, 'článok', 'články', 'článkov' ) ); ?></span>
			<?php echo sst_icon( 'arrow-right', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</span>
	</a>
</article>
