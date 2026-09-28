<?php
/**
 * Údaje o ceste pri článku: kedy (napr. „Leto 2024“) a počet dní.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		register_post_meta(
			'post',
			'sst_when',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_post_meta(
			'post',
			'sst_days',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
);

add_action(
	'add_meta_boxes_post',
	static function () {
		add_meta_box( 'sst-trip', 'O ceste', 'sst_trip_box', 'post', 'side', 'high' );
	}
);

function sst_trip_box( $post ) {
	wp_nonce_field( 'sst_trip', 'sst_trip_nonce' );
	$when = get_post_meta( $post->ID, 'sst_when', true );
	$days = get_post_meta( $post->ID, 'sst_days', true );
	?>
	<p>
		<label for="sst_when"><strong>Kedy</strong></label><br>
		<input type="text" id="sst_when" name="sst_when" value="<?php echo esc_attr( $when ); ?>" placeholder="Leto 2024" style="width:100%">
	</p>
	<p>
		<label for="sst_days"><strong>Dĺžka (dni)</strong></label><br>
		<input type="number" min="0" id="sst_days" name="sst_days" value="<?php echo esc_attr( $days ); ?>" style="width:100%">
	</p>
	<p class="description">Krajinu a mesto vyberte v paneli <em>Destinácie</em>. Krajina sa k mestu doplní automaticky.</p>
	<?php
}

add_action(
	'save_post_post',
	static function ( $post_id ) {
		if ( ! isset( $_POST['sst_trip_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sst_trip_nonce'] ), 'sst_trip' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, 'sst_when', sanitize_text_field( wp_unslash( $_POST['sst_when'] ?? '' ) ) );
		update_post_meta( $post_id, 'sst_days', absint( $_POST['sst_days'] ?? 0 ) );
	}
);

/** Odhad času čítania v minútach. */
function sst_reading_time( $post_id = null ) {
	$text  = wp_strip_all_tags( get_post_field( 'post_content', $post_id ? $post_id : get_the_ID() ) );
	$words = count( preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) );
	return max( 1, (int) round( $words / 200 ) );
}

/** „Leto 2024 · 5 dní · 6 min čítania“ */
function sst_trip_meta( $post_id = null, $with_reading = true ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$parts   = array();
	$when    = get_post_meta( $post_id, 'sst_when', true );
	$days    = (int) get_post_meta( $post_id, 'sst_days', true );
	if ( $when ) {
		$parts[] = $when;
	}
	if ( $days ) {
		$parts[] = $days . ' ' . sst_plural( $days, 'deň', 'dni', 'dní' );
	}
	if ( $with_reading ) {
		$parts[] = sst_reading_time( $post_id ) . ' min čítania';
	}
	return implode( ' · ', $parts );
}
