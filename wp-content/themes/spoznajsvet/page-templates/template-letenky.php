<?php
/**
 * Template Name: Letenky (najlacnejšie ponuky)
 *
 * @package spoznajsvet
 */

get_header();
?>

<section class="page-hero page-hero--flights">
	<div class="container page-hero__inner">
		<p class="eyebrow eyebrow--light">Každých 30 min · na vyžiadanie max. 15 min staré</p>
		<h1 class="page-hero__title"><?php the_title(); ?> <em>za pár eur</em></h1>
		<p class="page-hero__text">Najlacnejšie letenky do Bangkoku, Dubaja a Abu Dhabi z Viedne, Bratislavy a Budapešti – a európske mestá na víkend. Ceny z Ryanair, Wizz Air a vyhľadávačov, s porovnaním na Kayak a momondo.</p>
		<nav class="chips chips--scroll chips--glass" aria-label="Sekcie">
			<a class="chip" href="#exotika">🌴 Exotika</a>
			<a class="chip" href="#europa">🏛️ Európa</a>
		</nav>
	</div>
</section>

<section class="section section--tight" id="exotika">
	<div class="container">
		<?php sst_flights( array( 'skupina' => 'exotika' ) ); ?>
	</div>
</section>

<section class="section section--tight section--soft" id="europa">
	<div class="container">
		<?php
		sst_flights(
			array(
				'skupina' => 'europa',
				'limit'   => 18,
				'stav'    => '0',
			)
		);
		?>
	</div>
</section>

<?php
while ( have_posts() ) :
	the_post();
	if ( get_the_content() ) :
		?>
		<section class="section section--tight">
			<div class="container container--narrow entry-content"><?php the_content(); ?></div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
