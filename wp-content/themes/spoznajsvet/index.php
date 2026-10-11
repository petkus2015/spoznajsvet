<?php
/**
 * Stránka Články, archívy a vyhľadávanie.
 *
 * @package spoznajsvet
 */

get_header();

global $wp_query;
$paged     = max( 1, (int) get_query_var( 'paged' ) );
$total     = (int) $wp_query->found_posts;
$is_blog   = is_home() && ! is_search();
$countries = $is_blog ? sst_countries( array( 'orderby' => 'count', 'order' => 'DESC' ) ) : array();

if ( is_search() ) {
	$kick  = 'Hľadanie';
	$title = 'Výsledky pre „' . get_search_query() . '“';
	$lead  = $total ? $total . ' ' . sst_plural( $total, 'výsledok', 'výsledky', 'výsledkov' ) : '';
} elseif ( is_archive() ) {
	$kick  = 'Archív';
	$title = get_the_archive_title();
	$lead  = wp_strip_all_tags( get_the_archive_description() );
} else {
	$kick       = 'Denník';
	$title      = get_option( 'page_for_posts' ) ? get_the_title( get_option( 'page_for_posts' ) ) : 'Články';
	$posts_all  = (int) wp_count_posts()->publish;
	$n_country  = count( sst_countries() );
	$lead       = $posts_all . ' ' . sst_plural( $posts_all, 'príbeh', 'príbehy', 'príbehov' );
	if ( $n_country ) {
		$lead .= ' z ' . $n_country . ' ' . sst_plural( $n_country, 'krajiny', 'krajín', 'krajín' );
	}
	$lead .= ' – itineráre, ceny a tipy z ciest, väčšinou sólo.';
}
// Na každej strane je prvý článok zvýraznený (13 na stranu = 1 veľký + 4 rady po 3).
$featured = $is_blog && have_posts();
?>

<section class="blog-head">
	<div class="container">
		<div class="blog-head__row">
			<div class="blog-head__text">
				<p class="eyebrow"><?php echo esc_html( $kick ); ?></p>
				<h1 class="blog-head__title">
					<?php echo wp_kses_post( $title ); ?>
					<?php if ( $paged > 1 ) : ?>
						<span class="blog-head__page">strana <?php echo (int) $paged; ?></span>
					<?php endif; ?>
				</h1>
				<?php if ( $lead ) : ?>
					<p class="blog-head__lead"><?php echo esc_html( $lead ); ?></p>
				<?php endif; ?>
			</div>
			<div class="blog-head__search"><?php get_search_form(); ?></div>
		</div>

		<?php if ( $countries ) : ?>
			<nav class="blog-filter" aria-label="Články podľa krajiny">
				<div class="chips chips--scroll">
					<a class="chip is-active" href="<?php echo esc_url( sst_blog_url() ); ?>" aria-current="page">Všetko <span class="chip__count"><?php echo (int) wp_count_posts()->publish; ?></span></a>
					<?php foreach ( $countries as $c ) : ?>
						<a class="chip" href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( trim( sst_term_flag( $c ) . ' ' . $c->name ) ); ?> <span class="chip__count"><?php echo (int) $c->count; ?></span></a>
					<?php endforeach; ?>
				</div>
			</nav>
		<?php endif; ?>
	</div>
</section>

<section class="blog-list">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<?php
			if ( $featured ) {
				the_post();
				get_template_part( 'template-parts/card-article', null, array( 'variant' => 'featured' ) );
			}
			?>
			<?php if ( have_posts() ) : ?>
				<?php if ( $featured ) : ?>
					<h2 class="blog-list__title">Ďalšie príbehy</h2>
				<?php endif; ?>
				<div class="article-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/card-article' );
					endwhile;
					?>
				</div>
			<?php endif; ?>

			<?php if ( $wp_query->max_num_pages > 1 ) : ?>
				<div class="blog-pager">
					<p class="blog-pager__info">Strana <?php echo (int) $paged; ?> z <?php echo (int) $wp_query->max_num_pages; ?></p>
					<?php sst_pagination(); ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<div class="empty">
				<p>Nič sme nenašli. Skúste iné slovo alebo si vyberte krajinu.</p>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
