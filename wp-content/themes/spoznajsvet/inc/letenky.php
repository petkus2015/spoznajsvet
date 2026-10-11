<?php
/**
 * Letenky pribalené v téme.
 *
 * ZIP témy obsahuje kópiu pluginu „Spoznaj Svet – Letenky“ v inc/letenky/, takže letenky
 * fungujú aj bez samostatnej inštalácie pluginu. Ak je samostatný plugin aktívny, má prednosť
 * (načíta sa skôr ako téma) a pribalená kópia sa nepoužije.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SVL_VERSION' ) && file_exists( SST_DIR . '/inc/letenky/spoznajsvet-letenky.php' ) ) {
	define( 'SVL_BUNDLED', true );
	define( 'SVL_URL', SST_URI . '/inc/letenky/' );
	require SST_DIR . '/inc/letenky/spoznajsvet-letenky.php';
}
