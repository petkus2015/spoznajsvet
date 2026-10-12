<?php
/**
 * Mapa sveta „Kde som bol“: navštívené krajiny (čím viac miest, tým silnejšia farba) a mestá ako body.
 *
 * Obrysy krajín: assets/map/world.svg (Natural Earth 1:50m, projekcia Equal Earth,
 * vygenerované nástrojom tools/map). Súradnice miest: pole „Poloha“ pri meste,
 * inak vstavaný slovník, inak sa dohľadajú cez OpenStreetMap (Nominatim).
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

/** Parametre projekcie, s ktorými bol vygenerovaný world.svg (tools/map/build.mjs). */
function sst_map_meta() {
	return array(
		'w'     => 1000,
		'h'     => 447,
		'scale' => 188.29630980270795,
		'tx'    => 500.00929308913993,
		'ty'    => 245.7753643438548,
	);
}

/**
 * Equal Earth – rovnaký výpočet ako d3.geoEqualEarth.
 *
 * @return array{0:float,1:float} x, y v súradniciach world.svg
 */
function sst_map_project( $lat, $lng ) {
	$m   = sst_map_meta();
	$a1  = 1.340264;
	$a2  = -0.081106;
	$a3  = 0.000893;
	$a4  = 0.003796;
	$mm  = sqrt( 3 ) / 2;
	$lam = deg2rad( (float) $lng );
	$phi = deg2rad( (float) $lat );
	$l   = asin( $mm * sin( $phi ) );
	$l2  = $l * $l;
	$l6  = $l2 * $l2 * $l2;
	$x   = $lam * cos( $l ) / ( $mm * ( $a1 + 3 * $a2 * $l2 + $l6 * ( 7 * $a3 + 9 * $a4 * $l2 ) ) );
	$y   = $l * ( $a1 + $a2 * $l2 + $l6 * ( $a3 + $a4 * $l2 ) );
	return array( round( $x * $m['scale'] + $m['tx'], 2 ), round( -$y * $m['scale'] + $m['ty'], 2 ) );
}

/** Oblasti na priblíženie: [západ, juh, východ, sever] v stupňoch → výrez SVG. */
function sst_map_regions() {
	$regions = array(
		'svet'    => array( 'Svet', null ),
		'europa'  => array( 'Európa', array( -24, 34, 42, 71 ) ),
		'azia'    => array( 'Ázia', array( 26, -10, 146, 56 ) ),
		'afrika'  => array( 'Afrika', array( -20, -36, 53, 38 ) ),
		'amerika' => array( 'Amerika', array( -168, -56, -32, 72 ) ),
		'oceania' => array( 'Oceánia', array( 110, -48, 180, 2 ) ),
	);
	$out = array();
	foreach ( $regions as $key => $r ) {
		$out[ $key ] = array(
			'label' => $r[0],
			'box'   => $r[1] ? sst_map_box( $r[1] ) : array( 0, 0, sst_map_meta()['w'], sst_map_meta()['h'] ),
		);
	}
	return $out;
}

/** Výrez [x, y, šírka, výška] pre oblasť zadanú v stupňoch. */
function sst_map_box( array $deg ) {
	list( $w, $s, $e, $n ) = $deg;
	$xs = array();
	$ys = array();
	foreach ( array( $s, ( $s + $n ) / 2, $n ) as $lat ) {
		foreach ( array( $w, ( $w + $e ) / 2, $e ) as $lng ) {
			list( $x, $y ) = sst_map_project( $lat, $lng );
			$xs[]          = $x;
			$ys[]          = $y;
		}
	}
	return array( round( min( $xs ), 1 ), round( min( $ys ), 1 ), round( max( $xs ) - min( $xs ), 1 ), round( max( $ys ) - min( $ys ), 1 ) );
}

/** Kľúč pre slovník: bez diakritiky, malé písmená. */
function sst_map_key( $name ) {
	return trim( preg_replace( '/[^a-z0-9]+/', ' ', strtolower( remove_accents( (string) $name ) ) ) );
}

/**
 * Vstavané súradnice častých miest (slovenské aj pôvodné názvy). Doplniť sa dajú filtrom
 * `sst_map_known_places`, inak pole „Poloha“ pri meste alebo automaticky cez OpenStreetMap.
 *
 * @return array<string, array{0:float,1:float}>
 */
function sst_map_known_places() {
	static $places = null;
	if ( null !== $places ) {
		return $places;
	}
	$raw = array(
		// Taliansko.
		'rim|roma|rome'                 => array( 41.9028, 12.4964 ),
		'florencia|firenze|florence'    => array( 43.7696, 11.2558 ),
		'benatky|venezia|venice'        => array( 45.4408, 12.3155 ),
		'milano|milan|miláno'           => array( 45.4642, 9.19 ),
		'neapol|napoli|naples'          => array( 40.8518, 14.2681 ),
		'turin|torino'                  => array( 45.0703, 7.6869 ),
		'bologna'                       => array( 44.4949, 11.3426 ),
		'verona'                        => array( 45.4384, 10.9916 ),
		'pisa'                          => array( 43.7228, 10.4017 ),
		'siena'                         => array( 43.3188, 11.3308 ),
		'lucca'                         => array( 43.8429, 10.5027 ),
		'janov|genova|genoa'            => array( 44.4056, 8.9463 ),
		'palermo'                       => array( 38.1157, 13.3615 ),
		'catania'                       => array( 37.5079, 15.083 ),
		'taormina'                      => array( 37.8516, 15.2853 ),
		'syrakuzy|siracusa|syracuse'    => array( 37.0755, 15.2866 ),
		'bari'                          => array( 41.1171, 16.8719 ),
		'lecce'                         => array( 40.3515, 18.175 ),
		'matera'                        => array( 40.6664, 16.6043 ),
		'amalfi|amalfiske pobrezie|costiera amalfitana' => array( 40.634, 14.6027 ),
		'positano'                      => array( 40.6281, 14.485 ),
		'sorrento'                      => array( 40.6263, 14.3758 ),
		'capri'                         => array( 40.5532, 14.2222 ),
		'pompeje|pompei|pompeii'        => array( 40.7462, 14.4989 ),
		'como|lago di como|comske jazero' => array( 45.8081, 9.0852 ),
		'bergamo'                       => array( 45.6983, 9.6773 ),
		'gardske jazero|lago di garda|garda' => array( 45.6, 10.65 ),
		'cinque terre'                  => array( 44.1461, 9.6439 ),
		'toskansko|toscana|tuscany'     => array( 43.4, 11.2 ),
		'sicilia|sicilia|sicily'        => array( 37.6, 14.0 ),
		'sardinia|sardegna'             => array( 40.12, 9.01 ),
		'cagliari'                      => array( 39.2238, 9.1217 ),
		'trieste|terst'                 => array( 45.6495, 13.7768 ),
		'dolomity|dolomiti|dolomites'   => array( 46.41, 11.84 ),
		'perugia'                       => array( 43.1107, 12.3908 ),
		'assisi'                        => array( 43.0707, 12.6196 ),
		'san gimignano'                 => array( 43.4677, 11.0432 ),
		'rimini'                        => array( 44.0678, 12.5695 ),
		'ravenna'                       => array( 44.4184, 12.2035 ),
		'parma'                         => array( 44.8015, 10.3279 ),
		'mantova|mantua'                => array( 45.1564, 10.7914 ),
		'padova|padua'                  => array( 45.4064, 11.8768 ),
		'vatikan|vatican'               => array( 41.9029, 12.4534 ),
		// Európa.
		'bratislava'                    => array( 48.1486, 17.1077 ),
		'kosice'                        => array( 48.7164, 21.2611 ),
		'viedne|vieden|wien|vienna'     => array( 48.2082, 16.3738 ),
		'budapest|budapešť'             => array( 47.4979, 19.0402 ),
		'praha|prague'                  => array( 50.0755, 14.4378 ),
		'krakov|krakow'                 => array( 50.0647, 19.945 ),
		'varsava|warszawa|warsaw'       => array( 52.2297, 21.0122 ),
		'berlin'                        => array( 52.52, 13.405 ),
		'mnichov|munchen|munich'        => array( 48.1351, 11.582 ),
		'parize|pariz|paris'            => array( 48.8566, 2.3522 ),
		'nizza|nice'                    => array( 43.7102, 7.262 ),
		'londyn|london'                 => array( 51.5074, -0.1278 ),
		'edinburgh'                     => array( 55.9533, -3.1883 ),
		'dublin'                        => array( 53.3498, -6.2603 ),
		'amsterdam'                     => array( 52.3676, 4.9041 ),
		'brusel|bruxelles|brussels'     => array( 50.8503, 4.3517 ),
		'barcelona'                     => array( 41.3874, 2.1686 ),
		'madrid'                        => array( 40.4168, -3.7038 ),
		'sevilla|seville'               => array( 37.3891, -5.9845 ),
		'malaga'                        => array( 36.7213, -4.4214 ),
		'valencia'                      => array( 39.4699, -0.3763 ),
		'mallorca|palma'                => array( 39.5696, 2.6502 ),
		'tenerife'                      => array( 28.2916, -16.6291 ),
		'gran canaria'                  => array( 27.9202, -15.5474 ),
		'lisabon|lisboa|lisbon'         => array( 38.7223, -9.1393 ),
		'porto'                         => array( 41.1579, -8.6291 ),
		'madeira|funchal'               => array( 32.6669, -16.9241 ),
		'atény|ateny|athens'            => array( 37.9838, 23.7275 ),
		'santorini'                     => array( 36.3932, 25.4615 ),
		'kreta|crete'                   => array( 35.2401, 24.8093 ),
		'korfu|corfu'                   => array( 39.6243, 19.9217 ),
		'rodos|rhodes'                  => array( 36.4341, 28.2176 ),
		'split'                         => array( 43.5081, 16.4402 ),
		'dubrovnik'                     => array( 42.6507, 18.0944 ),
		'zadar'                         => array( 44.1194, 15.2314 ),
		'zahreb|zagreb'                 => array( 45.815, 15.9819 ),
		'plitvicke jazera|plitvice'     => array( 44.8654, 15.582 ),
		'ljubljana|lublana'             => array( 46.0569, 14.5058 ),
		'bled'                          => array( 46.3683, 14.1146 ),
		'kotor'                         => array( 42.4247, 18.7712 ),
		'mostar'                        => array( 43.3438, 17.8078 ),
		'sarajevo'                      => array( 43.8563, 18.4131 ),
		'belehrad|beograd|belgrade'     => array( 44.7866, 20.4489 ),
		'tirana'                        => array( 41.3275, 19.8187 ),
		'sofia'                         => array( 42.6977, 23.3219 ),
		'bukurest|bucharest'            => array( 44.4268, 26.1025 ),
		'malta|valletta'                => array( 35.8989, 14.5146 ),
		'kodan|kobenhavn|copenhagen'    => array( 55.6761, 12.5683 ),
		'stockholm|stokholm'            => array( 59.3293, 18.0686 ),
		'oslo'                          => array( 59.9139, 10.7522 ),
		'helsinki'                      => array( 60.1699, 24.9384 ),
		'reykjavik'                     => array( 64.1466, -21.9426 ),
		'tallinn'                       => array( 59.437, 24.7536 ),
		'riga'                          => array( 56.9496, 24.1052 ),
		'vilnius'                       => array( 54.6872, 25.2797 ),
		'istanbul'                      => array( 41.0082, 28.9784 ),
		'antalya'                       => array( 36.8969, 30.7133 ),
		'kapadokia|cappadocia'          => array( 38.6431, 34.8289 ),
		'tbilisi'                       => array( 41.7151, 44.8271 ),
		'kutaisi'                       => array( 42.2679, 42.6946 ),
		'larnaka|larnaca'               => array( 34.9182, 33.6232 ),
		'paphos|pafos'                  => array( 34.7754, 32.4245 ),
		'salzburg'                      => array( 47.8095, 13.055 ),
		'hallstatt'                     => array( 47.5622, 13.6493 ),
		'zurich'                        => array( 47.3769, 8.5417 ),
		'zeneva|geneve|geneva'          => array( 46.2044, 6.1432 ),
		// Ázia a Blízky východ.
		'bangkok'                       => array( 13.7563, 100.5018 ),
		'phuket'                        => array( 7.8804, 98.3923 ),
		'chiang mai|čiang mai'          => array( 18.7883, 98.9853 ),
		'krabi'                         => array( 8.0863, 98.9063 ),
		'koh samui|ko samui'            => array( 9.512, 100.0136 ),
		'koh phangan|ko pha ngan'       => array( 9.7319, 100.0136 ),
		'pattaya'                       => array( 12.9236, 100.8825 ),
		'ayutthaya'                     => array( 14.3532, 100.5689 ),
		'dubaj|dubai'                   => array( 25.2048, 55.2708 ),
		'abu dhabi|abú zabí'            => array( 24.4539, 54.3773 ),
		'sardza|sharjah'                => array( 25.3463, 55.4209 ),
		'ras al khaimah'                => array( 25.8007, 55.9762 ),
		'dauha|doha'                    => array( 25.2854, 51.531 ),
		'maskat|muscat'                 => array( 23.588, 58.3829 ),
		'amman'                         => array( 31.9539, 35.9106 ),
		'petra'                         => array( 30.3285, 35.4444 ),
		'jeruzalem|jerusalem'           => array( 31.7683, 35.2137 ),
		'tel aviv'                      => array( 32.0853, 34.7818 ),
		'singapur|singapore'            => array( 1.3521, 103.8198 ),
		'kuala lumpur'                  => array( 3.139, 101.6869 ),
		'bali|denpasar|ubud'            => array( -8.4095, 115.1889 ),
		'jakarta'                       => array( -6.2088, 106.8456 ),
		'hanoj|hanoi'                   => array( 21.0278, 105.8342 ),
		'ho ci minovo mesto|ho chi minh|saigon' => array( 10.8231, 106.6297 ),
		'halong|ha long'                => array( 20.9101, 107.1839 ),
		'phnom penh'                    => array( 11.5564, 104.9282 ),
		'siem reap|angkor'              => array( 13.3671, 103.8448 ),
		'manila'                        => array( 14.5995, 120.9842 ),
		'tokio|tokyo'                   => array( 35.6762, 139.6503 ),
		'kjoto|kyoto'                   => array( 35.0116, 135.7681 ),
		'osaka'                         => array( 34.6937, 135.5023 ),
		'soul|seoul'                    => array( 37.5665, 126.978 ),
		'peking|beijing'                => array( 39.9042, 116.4074 ),
		'sanghaj|shanghai'              => array( 31.2304, 121.4737 ),
		'hongkong|hong kong'            => array( 22.3193, 114.1694 ),
		'tchaj pej|taipei'              => array( 25.033, 121.5654 ),
		'dillí|dilli|delhi|new delhi'   => array( 28.6139, 77.209 ),
		'agra'                          => array( 27.1767, 78.0081 ),
		'goa'                           => array( 15.2993, 74.124 ),
		'bombaj|mumbai'                 => array( 19.076, 72.8777 ),
		'kathmandu|káthmandu'           => array( 27.7172, 85.324 ),
		'colombo|kolombo'               => array( 6.9271, 79.8612 ),
		'male|maldivy|maldives'         => array( 4.1755, 73.5093 ),
		'taskent|tashkent'              => array( 41.2995, 69.2401 ),
		'samarkand'                     => array( 39.6542, 66.9597 ),
		'almaty'                        => array( 43.222, 76.8512 ),
		// Afrika.
		'marrakes|marrakech'            => array( 31.6295, -7.9811 ),
		'kahira|cairo'                  => array( 30.0444, 31.2357 ),
		'hurghada'                      => array( 27.2579, 33.8116 ),
		'sharm el sheikh|šarm aš šajch' => array( 27.9158, 34.33 ),
		'tunis'                         => array( 36.8065, 10.1815 ),
		'zanzibar'                      => array( -6.1659, 39.2026 ),
		'kapske mesto|cape town'        => array( -33.9249, 18.4241 ),
		'mauricius|mauritius'           => array( -20.3484, 57.5522 ),
		'seychely|seychelles'           => array( -4.6796, 55.492 ),
		// Amerika a Oceánia.
		'new york'                      => array( 40.7128, -74.006 ),
		'los angeles'                   => array( 34.0522, -118.2437 ),
		'san francisco'                 => array( 37.7749, -122.4194 ),
		'las vegas'                     => array( 36.1699, -115.1398 ),
		'miami'                         => array( 25.7617, -80.1918 ),
		'cancun'                        => array( 21.1619, -86.8515 ),
		'mexiko|mexico city'            => array( 19.4326, -99.1332 ),
		'havana'                        => array( 23.1136, -82.3666 ),
		'rio de janeiro'                => array( -22.9068, -43.1729 ),
		'buenos aires'                  => array( -34.6037, -58.3816 ),
		'lima'                          => array( -12.0464, -77.0428 ),
		'cusco|machu picchu'            => array( -13.5319, -71.9675 ),
		'sydney'                        => array( -33.8688, 151.2093 ),
		'melbourne'                     => array( -37.8136, 144.9631 ),
		'auckland'                      => array( -36.8485, 174.7633 ),
	);
	$places = array();
	foreach ( $raw as $names => $ll ) {
		foreach ( explode( '|', $names ) as $n ) {
			$places[ sst_map_key( $n ) ] = $ll;
		}
	}
	$places = apply_filters( 'sst_map_known_places', $places );
	return $places;
}

/**
 * Súradnice mesta: ručne zadané → slovník → null.
 *
 * @return array{0:float,1:float}|null
 */
function sst_city_coords( WP_Term $term ) {
	$lat = get_term_meta( $term->term_id, 'sst_lat', true );
	$lng = get_term_meta( $term->term_id, 'sst_lng', true );
	if ( '' !== $lat && '' !== $lng && is_numeric( $lat ) && is_numeric( $lng ) ) {
		return array( (float) $lat, (float) $lng );
	}
	$known = sst_map_known_places();
	foreach ( array( $term->name, str_replace( '-', ' ', $term->slug ) ) as $n ) {
		$k = sst_map_key( $n );
		if ( isset( $known[ $k ] ) ) {
			return $known[ $k ];
		}
	}
	return null;
}

/** Dohľadá polohu mesta cez OpenStreetMap (Nominatim) a uloží ju. */
function sst_geocode_city( WP_Term $term ) {
	$country = sst_term_country( $term );
	$iso     = strtolower( (string) get_term_meta( $country->term_id, 'sst_iso', true ) );
	$args    = array(
		'q'               => $term->name . ( $country->term_id !== $term->term_id ? ', ' . $country->name : '' ),
		'format'          => 'json',
		'limit'           => 1,
		'accept-language' => 'sk',
	);
	if ( preg_match( '/^[a-z]{2}$/', $iso ) ) {
		$args['countrycodes'] = $iso;
	}
	$res = wp_remote_get(
		add_query_arg( $args, 'https://nominatim.openstreetmap.org/search' ),
		array(
			'timeout' => 8,
			'headers' => array( 'User-Agent' => 'SpoznajSvet-WordPress/' . SST_VERSION . ' (' . home_url( '/' ) . ')' ),
		)
	);
	update_term_meta( $term->term_id, 'sst_geo_try', time() );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return false;
	}
	$json = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( empty( $json[0]['lat'] ) || empty( $json[0]['lon'] ) ) {
		return false;
	}
	update_term_meta( $term->term_id, 'sst_lat', round( (float) $json[0]['lat'], 5 ) );
	update_term_meta( $term->term_id, 'sst_lng', round( (float) $json[0]['lon'], 5 ) );
	return true;
}

/** Na pozadí dohľadá polohu miest, ktoré ju nemajú (max. 10 naraz, 1 požiadavka za sekundu). */
function sst_geocode_missing() {
	$done = 0;
	foreach ( sst_countries( array( 'hide_empty' => false ) ) as $country ) {
		foreach ( sst_cities( $country, false ) as $city ) {
			if ( $done >= 10 ) {
				return;
			}
			if ( sst_city_coords( $city ) || (int) get_term_meta( $city->term_id, 'sst_geo_try', true ) > time() - WEEK_IN_SECONDS ) {
				continue;
			}
			sst_geocode_city( $city );
			++$done;
			sleep( 1 );
		}
	}
	delete_transient( 'sst_map_data' );
}
add_action( 'sst_geocode_missing', 'sst_geocode_missing' );

/**
 * Dáta pre mapu.
 *
 * @return array{countries:array,cities:int,missing:int}
 */
function sst_map_data() {
	$cached = get_transient( 'sst_map_data' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$countries = array();
	$n_cities  = 0;
	$missing   = 0;
	foreach ( sst_countries( array( 'hide_empty' => false ) ) as $c ) {
		$iso = strtoupper( (string) get_term_meta( $c->term_id, 'sst_iso', true ) );
		if ( ! preg_match( '/^[A-Z]{2}$/', $iso ) ) {
			continue;
		}
		$cities = array();
		foreach ( sst_cities( $c, false ) as $city ) {
			$ll = sst_city_coords( $city );
			if ( ! $ll ) {
				++$missing;
			}
			$xy       = $ll ? sst_map_project( $ll[0], $ll[1] ) : null;
			$cities[] = array(
				'n'   => $city->name,
				'u'   => get_term_link( $city ),
				'p'   => (int) $city->count,
				'x'   => $xy ? $xy[0] : null,
				'y'   => $xy ? $xy[1] : null,
			);
		}
		$n_cities           += count( $cities );
		$k                   = count( $cities );
		$countries[ $iso ][] = array(
			'n'  => $c->name,
			'f'  => sst_flag_emoji( $iso ),
			'u'  => get_term_link( $c ),
			'p'  => (int) $c->count,
			'c'  => $cities,
			// Úroveň farby: 1 = krajina, 2 = 2 – 3 miesta, 3 = 4 – 6 miest, 4 = 7 a viac.
			'l'  => $k >= 7 ? 4 : ( $k >= 4 ? 3 : ( $k >= 2 ? 2 : 1 ) ),
		);
	}
	// Ak by rovnaký kód mala viac položiek (napr. Anglicko + Škótsko), spojíme ich.
	foreach ( $countries as $iso => $list ) {
		$main = array_shift( $list );
		foreach ( $list as $extra ) {
			$main['c'] = array_merge( $main['c'], $extra['c'] );
			$main['p'] += $extra['p'];
		}
		$k                 = count( $main['c'] );
		$main['l']         = $k >= 7 ? 4 : ( $k >= 4 ? 3 : ( $k >= 2 ? 2 : 1 ) );
		$countries[ $iso ] = $main;
	}
	$data = array(
		'countries' => $countries,
		'cities'    => $n_cities,
		'missing'   => $missing,
	);
	set_transient( 'sst_map_data', $data, DAY_IN_SECONDS );
	return $data;
}

/** Zmena destinácií → nové dáta mapy. */
foreach ( array( 'created_destinacia', 'edited_destinacia', 'delete_destinacia', 'set_object_terms' ) as $sst_hook ) {
	add_action(
		$sst_hook,
		static function () {
			delete_transient( 'sst_map_data' );
		}
	);
}
add_action(
	'transition_post_status',
	static function () {
		delete_transient( 'sst_map_data' );
	}
);

/** Výrez, ktorý obsiahne všetky navštívené miesta (s okrajom). */
function sst_map_fit_box( array $countries ) {
	$xs = array();
	$ys = array();
	foreach ( $countries as $c ) {
		foreach ( $c['c'] as $city ) {
			if ( null !== $city['x'] ) {
				$xs[] = $city['x'];
				$ys[] = $city['y'];
			}
		}
	}
	if ( count( $xs ) < 2 ) {
		return null;
	}
	$m   = sst_map_meta();
	$pad = 40;
	$x0  = max( 0, min( $xs ) - $pad );
	$y0  = max( 0, min( $ys ) - $pad );
	$x1  = min( $m['w'], max( $xs ) + $pad );
	$y1  = min( $m['h'], max( $ys ) + $pad );
	return array( round( $x0, 1 ), round( $y0, 1 ), round( $x1 - $x0, 1 ), round( $y1 - $y0, 1 ) );
}

/**
 * Vykreslí mapu.
 *
 * @param array $args title (bool) – nadpis sekcie, link (bool) – odkaz na Destinácie.
 */
function sst_world_map( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'title' => true,
			'link'  => false,
		)
	);
	$data = sst_map_data();
	if ( ! $data['countries'] ) {
		return;
	}
	if ( $data['missing'] && ! wp_next_scheduled( 'sst_geocode_missing' ) ) {
		wp_schedule_single_event( time() + 5, 'sst_geocode_missing' );
	}
	wp_enqueue_script( 'sst-map', SST_URI . '/assets/js/map.js', array(), SST_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );

	$n_countries = count( $data['countries'] );
	$regions     = sst_map_regions();
	$fit         = sst_map_fit_box( $data['countries'] );
	$meta        = sst_map_meta();
	$payload     = array(
		'countries' => $data['countries'],
		'regions'   => wp_list_pluck( $regions, 'box' ),
		'fit'       => $fit,
		'w'         => $meta['w'],
		'h'         => $meta['h'],
	);
	?>
	<div class="world-map" data-world-map="<?php echo esc_url( SST_URI . '/assets/map/world.svg?ver=' . SST_VERSION ); ?>">
		<?php if ( $args['title'] ) : ?>
			<header class="section-head world-map__head">
				<div>
					<p class="eyebrow">Mapa ciest</p>
					<h2 class="section-title">Kde všade som <em>bol</em></h2>
				</div>
				<?php if ( $args['link'] ) : ?>
					<a class="link-arrow" href="<?php echo esc_url( sst_destinations_url() ); ?>">Všetky destinácie <?php echo sst_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<ul class="world-map__stats">
			<li><strong><?php echo (int) $n_countries; ?></strong> <?php echo esc_html( sst_plural( $n_countries, 'krajina', 'krajiny', 'krajín' ) ); ?></li>
			<li><strong><?php echo (int) $data['cities']; ?></strong> <?php echo esc_html( sst_plural( $data['cities'], 'miesto', 'miesta', 'miest' ) ); ?></li>
			<li><strong><?php echo esc_html( max( 1, round( $n_countries / 195 * 100 ) ) ); ?> %</strong> sveta</li>
		</ul>

		<div class="world-map__frame">
			<div class="world-map__bar">
				<div class="world-map__regions" role="group" aria-label="Priblížiť oblasť">
					<?php if ( $fit ) : ?>
						<button type="button" class="chip is-active" data-region="fit" aria-pressed="true">Moje cesty</button>
					<?php endif; ?>
					<?php foreach ( $regions as $key => $r ) : ?>
						<button type="button" class="chip<?php echo ! $fit && 'svet' === $key ? ' is-active' : ''; ?>" data-region="<?php echo esc_attr( $key ); ?>" aria-pressed="<?php echo ! $fit && 'svet' === $key ? 'true' : 'false'; ?>"><?php echo esc_html( $r['label'] ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="world-map__canvas" data-map-canvas>
				<div class="world-map__zoom">
					<button type="button" data-zoom="in" aria-label="Priblížiť">+</button>
					<button type="button" data-zoom="out" aria-label="Oddialiť">−</button>
				</div>
				<div class="world-map__tip" data-map-tip hidden></div>
			</div>
			<div class="world-map__legend" aria-hidden="true">
				<span><i class="lvl-1"></i>1 miesto</span>
				<span><i class="lvl-2"></i>2 – 3</span>
				<span><i class="lvl-3"></i>4 – 6</span>
				<span><i class="lvl-4"></i>7 a viac</span>
				<span><i class="dot"></i>mesto</span>
			</div>
		</div>

		<div class="world-map__card" data-map-card hidden></div>

		<script type="application/json" data-map-data><?php echo wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>
	</div>
	<?php
}

add_shortcode(
	'spoznajsvet_mapa',
	static function ( $atts ) {
		$atts = shortcode_atts( array( 'nadpis' => '1' ), (array) $atts, 'spoznajsvet_mapa' );
		ob_start();
		sst_world_map( array( 'title' => '0' !== (string) $atts['nadpis'] ) );
		return ob_get_clean();
	}
);
