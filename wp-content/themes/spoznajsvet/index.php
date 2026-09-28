<?php
/**
 * Zoznam článkov, archívy a vyhľadávanie.
 *
 * @package spoznajsvet
 */

get_header();

if ( is_search() ) {
	$title = 'Výsledky pre „' . get_search_query() . '“';
	$kick  = 'Hľadanie';
} elseif ( is_archive() ) {
	$title = get_the_archive_title();
	$kick  = 'Archív';
} else {
	$title = is_home() && get_option( 'page_for_posts' ) ? get_the_title( get_option( 'page_for_posts' ) ) : 'Články';
	$kick  = 'Denník';
}
$countries = is_search() ? array() : sst_countries( array( 'orderby' => 'count', 'order' => 'DESC' ) );
?>

<section class="page-hero page-hero--plain">
	<div class="container page-hero__inner">
		<p class="eyebrow"><?php echo esc_html( $kick ); ?></p>
		<h1 class="page-hero__title"><?php echo wp_kses_post( $title ); ?></h1>
		<?php if ( is_search() ) : ?>
			<div class="page-hero__search"><?php get_search_form(); ?></div>
		<?php elseif ( get_the_archive_description() ) : ?>
			<div class="page-hero__text"><?php the_archive_description(); ?></div>
		<?php endif; ?>
		<?php if ( $countries ) : ?>
			<div class="chips chips--scroll">
				<a class="chip is-active" href="<?php echo esc_url( sst_blog_url() ); ?>">Všetko</a>
				<?php foreach ( $countries as $c ) : ?>
					<a class="chip" href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( trim( sst_term_flag( $c ) . ' ' . $c->name ) ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<section class="section section--tight">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<div class="post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/card-post' );
				endwhile;
				?>
			</div>
			<?php sst_pagination(); ?>
		<?php else : ?>
			<div class="empty">
				<p>Nič sme nenašli. Skúste iné slovo alebo si vyberte krajinu.</p>
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
