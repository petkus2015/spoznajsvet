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
				)
			);
			?>
		</nav>

		<div class="site-header__actions">
			<button type="button" class="icon-btn" data-search-open aria-label="Hľadať" aria-controls="search-overlay" aria-expanded="false">
				<?php echo sst_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
			<?php if ( sst_flights_url() ) : ?>
				<a class="btn btn--glass btn--sm header-cta" href="<?php echo esc_url( sst_flights_url() ); ?>">
					<?php echo sst_icon( 'plane', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Lacné letenky
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<main id="obsah" class="site-main">
