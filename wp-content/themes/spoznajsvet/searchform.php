<?php
/**
 * Vyhľadávací formulár.
 *
 * @package spoznajsvet
 */

$sst_id = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $sst_id ); ?>">Hľadať</label>
	<span class="search-form__icon"><?php echo sst_icon( 'search', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
	<input type="search" id="<?php echo esc_attr( $sst_id ); ?>" class="search-form__input" placeholder="Krajina, mesto alebo téma…" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" autocomplete="off">
	<button type="submit" class="btn btn--primary btn--sm">Hľadať</button>
</form>
