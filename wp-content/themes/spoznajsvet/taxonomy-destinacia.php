<?php
/**
 * Krajina alebo mesto: /destinacie/taliansko/ a /destinacie/taliansko/florencia/
 *
 * @package spoznajsvet
 */

get_header();

$term    = get_queried_object();
$country = sst_term_country( $term );
$is_city = (bool) $term->parent;
$cover   = sst_term_cover_url( $term, 'sst-hero' );
$cities  = sst_cities( $is_city ? $term->parent : $term );
$flag    = sst_term_flag( $term );
$deal    = sst_cheapest_flight_to( $term );
?>

<?php
$coords  = sst_term_coords( $term );
$weather = sst_term_weather( $term );
$n_city  = $is_city ? 0 : count( $cities );
$desc    = trim( wp_strip_all_tags( $term->description ) );
?>
<section class="place-hero"<?php echo $cover ? ' style="--hero-img:url(' . esc_url( $cover ) . ')"' : ''; ?>>
	<div class="container place-hero__inner">
		<?php if ( $weather || $coords ) : ?>
			<div class="place-hero__wx">
				<?php if ( $weather ) : ?>
					<span class="place-hero__temp" title="Teraz v destinácii"><?php echo (int) $weather['t']; ?>°</span>
				<?php endif; ?>
				<span class="place-hero__wind">
					<?php if ( $weather ) : ?>
						<b><?php echo esc_html( $weather['dir'] . ' ' . $weather['wind'] . ' km/h' ); ?></b>
					<?php endif; ?>
					<?php if ( $coords ) : ?>
						<small><?php echo esc_html( sst_format_coords( $coords ) ); ?></small>
					<?php endif; ?>
				</span>
			</div>
		<?php endif; ?>

		<div class="place-hero__body">
			<?php sst_breadcrumbs( $term ); ?>
			<h1 class="place-hero__title">
				<?php echo esc_html( $term->name ); ?>
				<?php if ( $flag ) : ?>
					<span class="place-hero__flag" aria-hidden="true"><?php echo esc_html( $flag ); ?></span>
				<?php endif; ?>
			</h1>
			<dl class="place-hero__stats">
				<div><dt>Články</dt><dd><?php echo (int) $term->count; ?></dd></div>
				<?php if ( $is_city ) : ?>
					<div><dt>Krajina</dt><dd><?php echo esc_html( $country->name ); ?></dd></div>
				<?php elseif ( $n_city ) : ?>
					<div><dt>Miesta</dt><dd><?php echo (int) $n_city; ?></dd></div>
				<?php endif; ?>
				<?php if ( $deal ) : ?>
					<div><dt>Letenka od</dt><dd><?php echo esc_html( $deal['price'] ); ?></dd></div>
				<?php endif; ?>
			</dl>
			<?php if ( $desc ) : ?>
				<div class="place-hero__text<?php echo mb_strlen( $desc ) > 220 ? ' is-clamped' : ''; ?>" data-clamp><?php echo wp_kses_post( wpautop( $term->description ) ); ?></div>
				<?php if ( mb_strlen( $desc ) > 220 ) : ?>
					<button type="button" class="place-hero__more" data-clamp-toggle aria-expanded="false">Celý popis <?php echo sst_icon( 'chevron-down', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<?php endif; ?>
			<?php endif; ?>
			<div class="place-hero__actions">
				<?php if ( $deal ) : ?>
					<a class="btn btn--primary" href="<?php echo esc_url( $deal['url'] ); ?>"><?php echo sst_icon( 'plane', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Letenky od <?php echo esc_html( $deal['price'] ); ?></a>
				<?php endif; ?>
				<a class="btn <?php echo $deal ? 'btn--glass' : 'btn--primary'; ?>" href="#clanky"><?php echo sst_icon( 'book', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Čítať články</a>
			</div>
		</div>
	</div>
</section>

<?php if ( $cities ) : ?>
	<section class="section section--tight">
		<div class="container">
			<header class="section-head">
				<div>
					<h2 class="section-title"><?php echo esc_html( $is_city ? 'Ďalšie miesta v krajine ' . $country->name : 'Miesta, kde som bol' ); ?></h2>
				</div>
			</header>
			<div class="chips chips--scroll">
				<a class="chip<?php echo $is_city ? '' : ' is-active'; ?>" href="<?php echo esc_url( get_term_link( $country ) ); ?>">Všetko <span class="chip__count"><?php echo (int) $country->count; ?></span></a>
				<?php foreach ( $cities as $c ) : ?>
					<a class="chip<?php echo $c->term_id === $term->term_id ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $c ) ); ?>"<?php echo $c->term_id === $term->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?> <span class="chip__count"><?php echo (int) $c->count; ?></span></a>
				<?php endforeach; ?>
			</div>
			<?php if ( ! $is_city && count( $cities ) > 1 ) : ?>
				<div class="rail" data-carousel>
					<div class="rail__track" data-carousel-track>
						<?php foreach ( $cities as $c ) : ?>
							<?php get_template_part( 'template-parts/card-destination', null, array( 'term' => $c, 'variant' => 'compact' ) ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<section class="section section--tight" id="clanky">
	<div class="container">
		<header class="section-head">
			<div>
				<h2 class="section-title"><?php echo esc_html( $is_city ? 'Články: ' . $term->name : 'Všetko o krajine ' . $term->name ); ?></h2>
			</div>
		</header>
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
			<p class="empty">Zatiaľ tu nie je žiadny článok.</p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
