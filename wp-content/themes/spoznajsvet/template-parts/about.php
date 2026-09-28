<?php
/**
 * Blok „O mne“.
 *
 * @package spoznajsvet
 */

$photo = (int) get_theme_mod( 'sst_about_photo' );
$about = get_page_by_path( 'o-mne' );
?>
<section class="section">
	<div class="container">
		<div class="about">
			<div class="about__photo">
				<?php
				if ( $photo ) {
					echo wp_get_attachment_image( $photo, 'sst-card', false, array( 'loading' => 'lazy' ) );
				} else {
					echo '<span class="img-placeholder">' . sst_icon( 'user', 56 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
			<div class="about__text">
				<p class="eyebrow">O mne</p>
				<h2 class="section-title"><?php echo esc_html( sst_mod( 'sst_about_title' ) ); ?></h2>
				<p><?php echo esc_html( sst_mod( 'sst_about_text' ) ); ?></p>
				<div class="about__actions">
					<?php if ( $about ) : ?>
						<a class="btn btn--primary" href="<?php echo esc_url( get_permalink( $about ) ); ?>">Viac o mne</a>
					<?php endif; ?>
					<?php sst_social_links(); ?>
				</div>
			</div>
		</div>
	</div>
</section>
