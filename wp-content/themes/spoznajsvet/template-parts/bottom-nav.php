<?php
/**
 * Spodná navigácia pre mobily (ako v aplikácii).
 *
 * @package spoznajsvet
 */

$items = sst_bottom_nav_items();
?>
<nav class="bottom-nav" aria-label="Rýchla navigácia" data-bottom-nav>
	<ul>
		<?php foreach ( $items as $item ) : ?>
			<li class="<?php echo ! empty( $item['feature'] ) ? 'is-feature' : ''; ?>">
				<?php if ( ! empty( $item['search'] ) ) : ?>
					<button type="button" class="bottom-nav__item<?php echo $item['active'] ? ' is-active' : ''; ?>" data-search-open aria-controls="search-overlay" aria-expanded="false">
						<span class="bottom-nav__icon"><?php echo sst_icon( $item['icon'], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="bottom-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
					</button>
				<?php else : ?>
					<a class="bottom-nav__item<?php echo $item['active'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $item['active'] ? ' aria-current="page"' : ''; ?>>
						<span class="bottom-nav__icon"><?php echo sst_icon( $item['icon'], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="bottom-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
					</a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
