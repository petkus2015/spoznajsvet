<?php
/**
 * 404.
 *
 * @package spoznajsvet
 */

get_header();
?>
<section class="page-hero page-hero--plain page-hero--center">
	<div class="container container--narrow page-hero__inner">
		<p class="eyebrow">404</p>
		<h1 class="page-hero__title">Tu sme sa <em>stratili</em></h1>
		<p class="page-hero__text">Táto stránka neexistuje. Skúste vyhľadávanie alebo sa vráťte na začiatok cesty.</p>
		<div class="page-hero__search"><?php get_search_form(); ?></div>
		<p><a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo sst_icon( 'home', 18 ); // phpcs:ignore ?> Domov</a></p>
	</div>
</section>
<?php
get_footer();
