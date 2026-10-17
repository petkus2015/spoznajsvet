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
		<p class="page-hero__text">Najlacnejšie európske mestá na víkend a letenky do Bangkoku, Dubaja a Abu Dhabi z Viedne, Bratislavy a Budapešti. Ceny z Ryanair, Wizz Air a vyhľadávačov, s porovnaním na Kayak a momondo.</p>
		<nav class="chips chips--scroll chips--glass" aria-label="Sekcie">
			<a class="chip" href="#europa">🏛️ Európa</a>
			<a class="chip" href="#oblubene">⭐ Obľúbené mestá</a>
			<a class="chip" href="#exotika">🌴 Exotika</a>
		</nav>
	</div>
</section>

<section class="section section--tight" id="europa">
	<div class="container">
		<?php
		sst_flights(
			array(
				'skupina' => 'europa',
				'limit'   => 18,
			)
		);
		?>
	</div>
</section>

<section class="section section--tight" id="oblubene">
	<div class="container">
		<?php
		sst_flights(
			array(
				'skupina' => 'top',
				'limit'   => 24,
				'stav'    => '0',
			)
		);
		?>
	</div>
</section>

<section class="section section--tight section--soft" id="exotika">
	<div class="container">
		<?php sst_flights( array( 'skupina' => 'exotika', 'stav' => '0' ) ); ?>
	</div>
</section>

<?php
while ( have_posts() ) :
	the_post();
	if ( sst_has_page_content() ) :
		?>
		<section class="section section--tight">
			<div class="container container--narrow entry-content"><?php the_content(); ?></div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
