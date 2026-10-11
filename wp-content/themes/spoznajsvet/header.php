<?php
/**
 * Hlavička.
 *
 * @package spoznajsvet
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#obsah">Preskočiť na obsah</a>

<header class="site-header" data-header>
	<div class="container site-header__inner">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php
			if ( has_custom_logo() ) {
				$logo_id = get_theme_mod( 'custom_logo' );
				echo wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'brand__img', 'alt' => get_bloginfo( 'name' ) ) );
			} else {
				echo sst_logo_mark(); // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<span class="brand__text">Spoznaj <em>Svet</em></span>';
			}
			?>
		</a>

		<nav class="primary-nav" aria-label="Hlavné menu">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'depth'          => 2,
					'fallback_cb'    => 'sst_primary_fallback',
					'sst_context'    => 'desktop',
				)
			);
			?>
		</nav>

		<div class="site-header__actions">
			<button type="button" class="icon-btn" data-search-open aria-label="Hľadať" aria-controls="search-overlay" aria-expanded="false">
				<?php echo sst_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
			<?php if ( sst_flights_url() ) : ?>
				<a class="btn btn--sm header-cta" href="<?php echo esc_url( sst_flights_url() ); ?>">
					<?php echo sst_icon( 'plane', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Lacné letenky
				</a>
			<?php endif; ?>
			<button type="button" class="icon-btn menu-toggle" data-menu-toggle aria-label="Otvoriť menu" aria-controls="mobile-menu" aria-expanded="false">
				<span class="menu-toggle__open"><?php echo sst_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="menu-toggle__close"><?php echo sst_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</button>
		</div>
	</div>
</header>

<div class="mobile-menu" id="mobile-menu" data-mobile-menu hidden>
	<div class="mobile-menu__panel">
		<nav class="mobile-menu__nav" aria-label="Menu">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'depth'          => 2,
					'fallback_cb'    => 'sst_primary_fallback',
					'sst_context'    => 'mobile',
				)
			);
			?>
		</nav>
		<?php $sst_menu_countries = array_slice( sst_countries( array( 'orderby' => 'count', 'order' => 'DESC' ) ), 0, 12 ); ?>
		<?php if ( $sst_menu_countries ) : ?>
			<p class="mobile-menu__label">Krajiny</p>
			<div class="chips chips--wrap">
				<?php foreach ( $sst_menu_countries as $c ) : ?>
					<a class="chip chip--sm" href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( trim( sst_term_flag( $c ) . ' ' . $c->name ) ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( sst_flights_url() ) : ?>
			<a class="btn btn--primary mobile-menu__cta" href="<?php echo esc_url( sst_flights_url() ); ?>"><?php echo sst_icon( 'plane', 18 ); // phpcs:ignore ?> Lacné letenky</a>
		<?php endif; ?>
		<?php sst_social_links(); ?>
	</div>
</div>

<main id="obsah" class="site-main">
