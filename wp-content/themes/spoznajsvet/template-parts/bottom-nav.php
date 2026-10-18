<?php
/**
 * Spodná navigácia pre mobily v štýle Apple „Liquid Glass“ (iOS 27):
 * jedna sklenená kapsula so všetkými položkami vrátane vyhľadávania,
 * „tekutá“ šošovka pod aktívnou položkou, ktorú sa dá ťahať prstom.
 *
 * @package spoznajsvet
 */

$items = sst_bottom_nav_items();
?>
<nav class="bottom-nav" aria-label="Rýchla navigácia" data-bottom-nav>
	<div class="bottom-nav__bar glass">
		<span class="bottom-nav__lens glass-lens" aria-hidden="true" data-nav-lens></span>
		<ul>
			<?php foreach ( $items as $item ) : ?>
				<li>
					<?php if ( ! empty( $item['search'] ) ) : ?>
						<button type="button" class="bottom-nav__item<?php echo $item['active'] ? ' is-active' : ''; ?>" data-search-open aria-controls="search-overlay" aria-expanded="false">
					<?php else : ?>
						<a draggable="false" class="bottom-nav__item<?php echo $item['active'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $item['active'] ? ' aria-current="page"' : ''; ?>>
					<?php endif; ?>
						<span class="bottom-nav__icon"><?php echo sst_icon( $item['icon'], 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="bottom-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
					<?php echo ! empty( $item['search'] ) ? '</button>' : '</a>'; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</nav>
