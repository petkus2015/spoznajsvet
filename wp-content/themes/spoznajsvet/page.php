<?php
/**
 * Stránka.
 *
 * @package spoznajsvet
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="page-hero page-hero--plain">
		<div class="container container--narrow page-hero__inner">
			<h1 class="page-hero__title"><?php the_title(); ?></h1>
		</div>
	</section>
	<div class="container container--narrow">
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="page-cover"><?php the_post_thumbnail( 'sst-wide' ); ?></figure>
		<?php endif; ?>
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
		<?php
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>
	</div>
	<?php
endwhile;

get_footer();
