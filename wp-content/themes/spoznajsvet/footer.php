<?php
/**
 * Pätička, spodná navigácia a vyhľadávanie.
 *
 * @package spoznajsvet
 */

?>
</main>

<footer class="site-footer">
	<div class="container site-footer__grid">
		<div class="site-footer__brand">
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php echo sst_logo_mark(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="brand__text">Spoznaj <em>Svet</em></span>
			</a>
			<p><?php echo esc_html( sst_mod( 'sst_footer_text' ) ); ?></p>
			<?php sst_social_links( 'social social--footer' ); ?>
		</div>

		<?php $countries = array_slice( sst_countries( array( 'orderby' => 'count', 'order' => 'DESC' ) ), 0, 8 ); ?>
		<?php if ( $countries ) : ?>
			<div>
				<h2 class="site-footer__title">Obľúbené krajiny</h2>
				<ul class="site-footer__list">
					<?php foreach ( $countries as $c ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( trim( sst_term_flag( $c ) . ' ' . $c->name ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div>
			<h2 class="site-footer__title">Blog</h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'site-footer__list',
					'depth'          => 1,
					'fallback_cb'    => 'sst_primary_fallback',
				)
			);
			?>
		</div>
	</div>
	<div class="container site-footer__bottom">
		<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
		<?php
		if ( function_exists( 'the_privacy_policy_link' ) ) {
			the_privacy_policy_link();
		}
		?>
	</div>
</footer>

<?php get_template_part( 'template-parts/bottom-nav' ); ?>
<?php get_template_part( 'template-parts/search-overlay' ); ?>

<?php wp_footer(); ?>
</body>
</html>
