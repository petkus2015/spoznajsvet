<?php
/**
 * Celoobrazovkové vyhľadávanie s rýchlymi destináciami.
 *
 * @package spoznajsvet
 */

$countries = array_slice( sst_countries( array( 'orderby' => 'count', 'order' => 'DESC' ) ), 0, 10 );
?>
<div class="search-overlay" id="search-overlay" role="dialog" aria-modal="true" aria-label="Vyhľadávanie" hidden data-search-overlay>
	<div class="search-overlay__panel">
		<div class="search-overlay__top">
			<p class="search-overlay__title">Kam sa chystáš?</p>
			<button type="button" class="icon-btn" data-search-close aria-label="Zavrieť"><?php echo sst_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>
		<?php get_search_form(); ?>
		<?php if ( $countries ) : ?>
			<p class="search-overlay__label">Krajiny</p>
			<div class="chips chips--wrap">
				<?php foreach ( $countries as $c ) : ?>
					<a class="chip" href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( trim( sst_term_flag( $c ) . ' ' . $c->name ) ); ?> <span class="chip__count"><?php echo (int) $c->count; ?></span></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
