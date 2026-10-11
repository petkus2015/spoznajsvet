<?php
/**
 * Spodná navigácia pre mobily v štýle Apple „Liquid Glass“:
 * sklenená kapsula s položkami + samostatné okrúhle tlačidlo vyhľadávania.
 *
 * @package spoznajsvet
 */

$items  = sst_bottom_nav_items();
$tabs   = array_values(
	array_filter(
		$items,
		static function ( $i ) {
			return empty( $i['search'] );
		}
	)
);
$search = array_values(
	array_filter(
		$items,
		static function ( $i ) {
			return ! empty( $i['search'] );
		}
	)
);
?>
<nav class="bottom-nav" aria-label="Rýchla navigácia" data-bottom-nav>
	<div class="bottom-nav__bar glass">
		<span class="bottom-nav__lens glass-lens" aria-hidden="true" data-nav-lens></span>
		<ul>
			<?php foreach ( $tabs as $item ) : ?>
				<li>
					<a class="bottom-nav__item<?php echo $item['active'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $item['active'] ? ' aria-current="page"' : ''; ?>>
						<span class="bottom-nav__icon"><?php echo sst_icon( $item['icon'], 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="bottom-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php foreach ( $search as $item ) : ?>
		<button type="button" class="bottom-nav__search glass<?php echo $item['active'] ? ' is-active' : ''; ?>" data-search-open aria-controls="search-overlay" aria-expanded="false" aria-label="<?php echo esc_attr( $item['label'] ); ?>">
			<?php echo sst_icon( $item['icon'], 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>
	<?php endforeach; ?>
</nav>
