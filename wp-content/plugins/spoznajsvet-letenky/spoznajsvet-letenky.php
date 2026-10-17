<?php
/**
 * Plugin Name:       Spoznaj Svet – Letenky
 * Plugin URI:        https://www.spoznajsvet.sk/letenky/
 * Description:       Najlacnejšie aktuálne letenky (Ryanair, Wizz Air, Aviasales/Travelpayouts + porovnanie na Kayak a momondo). Aktualizácia každých 30 minút. Shortcode [spoznajsvet_letenky] a blok „Letenky“.
 * Version:           1.12.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Spoznaj Svet
 * License:           GPL-2.0-or-later
 * Text Domain:       spoznajsvet-letenky
 */

defined( 'ABSPATH' ) || exit;

// Letenky sú už načítané (napr. kópia pribalená v téme alebo samostatný plugin) – druhýkrát nie.
if ( defined( 'SVL_VERSION' ) ) {
	return;
}

define( 'SVL_VERSION', '1.12.0' );
define( 'SVL_FILE', __FILE__ );
define( 'SVL_DIR', plugin_dir_path( __FILE__ ) );
// Téma, ktorá letenky pribaľuje, si URL nastaví sama (SVL_URL a SVL_BUNDLED).
if ( ! defined( 'SVL_URL' ) ) {
	define( 'SVL_URL', plugin_dir_url( __FILE__ ) );
}

require_once SVL_DIR . 'includes/airports.php';
require_once SVL_DIR . 'includes/class-settings.php';
require_once SVL_DIR . 'includes/class-format.php';
require_once SVL_DIR . 'includes/class-currency.php';
require_once SVL_DIR . 'includes/providers/class-provider.php';
require_once SVL_DIR . 'includes/providers/class-ryanair.php';
require_once SVL_DIR . 'includes/providers/class-wizzair.php';
require_once SVL_DIR . 'includes/providers/class-travelpayouts.php';
require_once SVL_DIR . 'includes/providers/class-manual.php';
require_once SVL_DIR . 'includes/class-weather.php';
require_once SVL_DIR . 'includes/class-store.php';
require_once SVL_DIR . 'includes/class-cron.php';
require_once SVL_DIR . 'includes/class-render.php';
require_once SVL_DIR . 'includes/class-rest.php';
require_once SVL_DIR . 'includes/class-admin.php';

if ( ! defined( 'SVL_BUNDLED' ) ) {
	register_activation_hook( __FILE__, array( 'SVL_Cron', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'SVL_Cron', 'deactivate' ) );
}

// Pozor: žiadne funkcie na najvyššej úrovni – PHP by ich deklaroval ešte pred poistkou vyššie
// a pri súčasnom načítaní z témy aj pluginu by vznikla chyba „Cannot redeclare“.
$svl_boot = static function () {
	SVL_Cron::init();
	SVL_Render::init();
	SVL_Rest::init();
	SVL_Admin::init();
};
// Z témy sa načíta až po „plugins_loaded“ – vtedy spustíme hneď.
if ( did_action( 'plugins_loaded' ) ) {
	$svl_boot();
} else {
	add_action( 'plugins_loaded', $svl_boot );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once SVL_DIR . 'includes/class-cli.php';
	WP_CLI::add_command( 'letenky', 'SVL_CLI' );
}

/**
 * Verejné API pre tému: je plugin aktívny a vráti HTML sekcie.
 *
 * @param array $atts Rovnaké atribúty ako shortcode.
 * @return string
 */
if ( ! function_exists( 'svl_render_deals' ) ) {
	function svl_render_deals( $atts = array() ) {
		return SVL_Render::shortcode( $atts );
	}
}
