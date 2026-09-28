<?php
/**
 * Komentáre.
 *
 * @package spoznajsvet
 */

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments__title">
			<?php
			$n = get_comments_number();
			echo esc_html( $n . ' ' . sst_plural( $n, 'komentár', 'komentáre', 'komentárov' ) );
			?>
		</h2>
		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 44,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php
	comment_form(
		array(
			'title_reply'  => 'Napíšte komentár',
			'label_submit' => 'Odoslať',
			'class_submit' => 'btn btn--primary',
		)
	);
	?>
</div>
