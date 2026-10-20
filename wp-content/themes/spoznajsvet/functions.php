<?php
/**
 * Spoznaj Svet – funkcie témy.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

define( 'SST_VERSION', '1.24.2' );
define( 'SST_DIR', get_template_directory() );
define( 'SST_URI', get_template_directory_uri() );

require SST_DIR . '/inc/letenky.php';
require SST_DIR . '/inc/setup.php';
require SST_DIR . '/inc/icons.php';
require SST_DIR . '/inc/destinations.php';
require SST_DIR . '/inc/post-meta.php';
require SST_DIR . '/inc/template-tags.php';
require SST_DIR . '/inc/customizer.php';
require SST_DIR . '/inc/activation.php';
require SST_DIR . '/inc/legacy.php';
require SST_DIR . '/inc/flights.php';
require SST_DIR . '/inc/map.php';
require SST_DIR . '/inc/map-admin.php';
