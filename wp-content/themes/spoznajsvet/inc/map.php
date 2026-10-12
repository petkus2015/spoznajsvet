<?php
/**
 * Mapa sveta „Kde som bol“: navštívené štáty a ostrovy (čím viac miest, tým silnejšia farba) a mestá ako body.
 *
 * Obrysy: assets/map/world.svg (prehľad) a world-detail.svg (pri priblížení) – Natural Earth 1:10m, štáty aj
 * samostatné súostrovia (Azory, Madeira, Kanárske ostrovy, Sicília…), projekcia Equal Earth, nástroj tools/map.
 * Vyznačenie: automaticky podľa Destinácií (časť, v ktorej leží mesto) + ručne v Články → Mapa ciest.
 *
 * @package spoznajsvet
 */

defined( 'ABSPATH' ) || exit;

/** Projekcia a zoznam častí mapy (assets/map/world.json). */
function sst_map_geo() {
	static $geo = null;
	if ( null === $geo ) {
		$geo = json_decode( (string) file_get_contents( SST_DIR . '/assets/map/world.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$geo = is_array( $geo ) ? $geo : array( 'w' => 1000, 'h' => 451, 'scale' => 187.5, 'tx' => 497.3, 'ty' => 244.8, 'units' => array() );
	}
	return $geo;
}

function sst_map_meta() {
	$g = sst_map_geo();
	return array(
		'w'     => (int) $g['w'],
		'h'     => (int) $g['h'],
		'scale' => (float) $g['scale'],
		'tx'    => (float) $g['tx'],
		'ty'    => (float) $g['ty'],
	);
}

/** @return array<string, array{i:string,c:string,n:string,m?:int}> časti mapy podľa kódu */
function sst_map_units() {
	return sst_map_geo()['units'];
}

/** Slovenský názov štátu podľa ISO kódu. */
function sst_map_country_name( $iso ) {
	static $cache = array();
	if ( isset( $cache[ $iso ] ) ) {
		return $cache[ $iso ];
	}
	$name = '';
	if ( class_exists( 'Locale' ) ) {
		$name = Locale::getDisplayRegion( '-' . $iso, 'sk' );
		if ( $name === $iso ) {
			$name = '';
		}
	}
	if ( ! $name ) {
		foreach ( sst_map_units() as $u ) {
			if ( $u['i'] === $iso && ! empty( $u['m'] ) ) {
				$name = $u['n'];
				break;
			}
		}
	}
	$cache[ $iso ] = $name ? $name : $iso;
	return $cache[ $iso ];
}

/** Slovenský názov časti (ostrova, súostrovia); pre hlavnú časť názov štátu. */
function sst_map_unit_name( $su ) {
	$units = sst_map_units();
	if ( ! isset( $units[ $su ] ) ) {
		return $su;
	}
	$u = $units[ $su ];
	if ( ! empty( $u['m'] ) && $u['i'] ) {
		return sst_map_country_name( $u['i'] );
	}
	$sk = array(
		'PAZ' => 'Azory',
		'PMD' => 'Madeira',
		'ESC' => 'Kanárske ostrovy',
		'ESI' => 'Baleárske ostrovy',
		'ITY' => 'Sicília',
		'ITD' => 'Sardínia',
		'ITP' => 'Pantelleria',
		'ITI' => 'Pelagické ostrovy',
		'FXC' => 'Korzika',
		'DNB' => 'Bornholm',
		'NJM' => 'Jan Mayen',
		'USH' => 'Havaj',
		'USK' => 'Aljaška',
		'ECG' => 'Galapágy',
		'CHP' => 'Veľkonočný ostrov',
		'NIR' => 'Severné Írsko',
		'SCT' => 'Škótsko',
		'WLS' => 'Wales',
		'TZZ' => 'Zanzibar',
		'CHH' => 'Chajnan',
		'INA' => 'Andamany',
		'INN' => 'Nikobary',
		'INL' => 'Lakadivy',
		'YES' => 'Sokotra',
		'AUA' => 'Tasmánia',
		'JPO' => 'Okinawa a ostrovy Rjúkjú',
		'JPI' => 'Izu',
		'JPB' => 'Boninské ostrovy',
		'JPV' => 'Sopečné ostrovy',
		'KOJ' => 'Čedžu',
		'TTG' => 'Tobago',
		'ACB' => 'Barbuda',
		'STA' => 'Princov ostrov',
		'PNB' => 'Bougainville',
		'NZC' => 'Chathamove ostrovy',
		'NZK' => 'Kermadecove ostrovy',
		'GNK' => 'Bioko',
		'GNA' => 'Annobón',
		'RUK' => 'Kaliningradská oblasť',
		'SHS' => 'Svätá Helena',
		'BAC' => 'Ascension',
		'SHT' => 'Tristan da Cunha',
		'GGS' => 'Sark',
		'GGA' => 'Alderney',
	);
	return $sk[ $su ] ?? $u['n'];
}

/** Ručné vyznačenia z administrácie: [kód časti => 1 (navštívené) | 0 (skryť, aj keď je v Destináciách)]. */
function sst_map_marks() {
	$m = get_option( 'sst_map_marks', array() );
	return is_array( $m ) ? array_map( 'intval', $m ) : array();
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
 * Obrysy vybraných častí z podrobnej mapy: [kód => [[x,y], ...] krúžky].
 *
 * @param string[] $wanted
 */
function sst_map_rings( array $wanted ) {
	$svg = (string) file_get_contents( SST_DIR . '/assets/map/world-detail.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$out = array();
	foreach ( $wanted as $su ) {
		if ( ! preg_match( '/data-unit="' . preg_quote( $su, '/' ) . '"[^>]*\sd="([^"]+)"/', $svg, $m ) ) {
			continue;
		}
		foreach ( explode( 'M', $m[1] ) as $part ) {
			preg_match_all( '/(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $part, $pts, PREG_SET_ORDER );
			if ( count( $pts ) >= 3 ) {
				$out[ $su ][] = array_map(
					static function ( $p ) {
						return array( (float) $p[1], (float) $p[2] );
					},
					$pts
				);
			}
		}
	}
	return $out;
}

/** Leží bod v časti? (párno-nepárne pravidlo cez všetky krúžky) */
function sst_map_inside( array $rings, $x, $y ) {
	$in = false;
	foreach ( $rings as $r ) {
		$n = count( $r );
		for ( $i = 0, $j = $n - 1; $i < $n; $j = $i++ ) {
			if ( ( $r[ $i ][1] > $y ) !== ( $r[ $j ][1] > $y ) && $x < ( $r[ $j ][0] - $r[ $i ][0] ) * ( $y - $r[ $i ][1] ) / ( ( $r[ $j ][1] - $r[ $i ][1] ) ?: 1e-9 ) + $r[ $i ][0] ) {
				$in = ! $in;
			}
		}
	}
	return $in;
}

/** Najmenšia vzdialenosť bodu od vrcholov časti (mesto na pobreží tesne mimo zjednodušeného obrysu). */
function sst_map_distance( array $rings, $x, $y ) {
	$best = INF;
	foreach ( $rings as $r ) {
		foreach ( $r as $p ) {
			$best = min( $best, ( $p[0] - $x ) ** 2 + ( $p[1] - $y ) ** 2 );
		}
	}
	return sqrt( $best );
}

function sst_map_level( $cities ) {
	// 1 = štát / 1 miesto, 2 = 2 – 3 miesta, 3 = 4 – 6 miest, 4 = 7 a viac.
	return $cities >= 7 ? 4 : ( $cities >= 4 ? 3 : ( $cities >= 2 ? 2 : 1 ) );
}

/**
 * Dáta pre mapu (vo vyrovnávacej pamäti, obnovia sa pri zmene destinácií alebo vyznačení).
 *
 * @return array{units:array,countries:array,names:array,cities:int,states:int,continents:int,missing:int}
 */
function sst_map_data() {
	$cached = get_transient( 'sst_map_data' );
	if ( is_array( $cached ) && isset( $cached['v'] ) && SST_VERSION === $cached['v'] ) {
		return $cached;
	}
	$units     = sst_map_units();
	$by_iso    = array();
	foreach ( $units as $su => $u ) {
		if ( $u['i'] ) {
			$by_iso[ $u['i'] ][] = $su;
		}
	}
	$countries = array();
	$auto      = array();
	$n_cities  = 0;
	$missing   = 0;
	$need_poly = array();

	// 1) Destinácie: krajiny a mestá.
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
				'id' => $city->term_id,
				'n'  => $city->name,
				'u'  => get_term_link( $city ),
				'p'  => (int) $city->count,
				'x'  => $xy ? $xy[0] : null,
				'y'  => $xy ? $xy[1] : null,
			);
		}
		if ( isset( $countries[ $iso ] ) ) { // Napr. Anglicko a Škótsko ako dve položky s kódom GB.
			$countries[ $iso ]['c']  = array_merge( $countries[ $iso ]['c'], $cities );
			$countries[ $iso ]['p'] += (int) $c->count;
		} else {
			$countries[ $iso ] = array(
				'id' => $c->term_id,
				'n'  => $c->name,
				'f'  => sst_flag_emoji( $iso ),
				'u'  => get_term_link( $c ),
				'p'  => (int) $c->count,
				'c'  => $cities,
			);
		}
		if ( count( $by_iso[ $iso ] ?? array() ) > 1 ) {
			$need_poly = array_merge( $need_poly, $by_iso[ $iso ] );
		}
	}

	// 2) Automaticky vyznačené časti: tá, v ktorej leží mesto; bez miest hlavná časť štátu.
	$rings = $need_poly ? sst_map_rings( $need_poly ) : array();
	foreach ( $countries as $iso => &$country ) {
		$parts = $by_iso[ $iso ] ?? array();
		if ( ! $parts ) {
			continue; // Štát nie je na mape (veľmi malý) – zobrazí sa len bod mesta.
		}
		$hit = array();
		if ( count( $parts ) === 1 ) {
			$hit[] = $parts[0];
		} else {
			foreach ( $country['c'] as &$city ) {
				if ( null === $city['x'] ) {
					continue;
				}
				$found = null;
				$near  = INF;
				foreach ( $parts as $su ) {
					if ( empty( $rings[ $su ] ) ) {
						continue;
					}
					if ( sst_map_inside( $rings[ $su ], $city['x'], $city['y'] ) ) {
						$found = $su;
						break;
					}
					$d = sst_map_distance( $rings[ $su ], $city['x'], $city['y'] );
					if ( $d < $near && $d < 6 ) {
						$near  = $d;
						$found = $su;
					}
				}
				if ( $found ) {
					$city['su'] = $found;
					$hit[]      = $found;
				}
			}
			unset( $city );
		}
		if ( ! $hit ) {
			foreach ( $parts as $su ) {
				if ( ! empty( $units[ $su ]['m'] ) ) {
					$hit[] = $su;
				}
			}
		}
		foreach ( array_unique( $hit ) as $su ) {
			$auto[ $su ] = true;
		}
	}
	unset( $country );

	// 3) Ručné vyznačenia z administrácie.
	$visited = $auto;
	foreach ( sst_map_marks() as $su => $on ) {
		if ( ! isset( $units[ $su ] ) ) {
			continue;
		}
		if ( $on ) {
			$visited[ $su ] = isset( $auto[ $su ] );
		} else {
			unset( $visited[ $su ] );
		}
	}

	// 4) Štáty, kontinenty, farby.
	$out_units  = array();
	$names      = array();
	$continents = array();
	foreach ( $visited as $su => $is_auto ) {
		$iso = $units[ $su ]['i'];
		if ( $iso && ! isset( $countries[ $iso ] ) ) {
			$countries[ $iso ] = array(
				'id' => 0,
				'n'  => sst_map_country_name( $iso ),
				'f'  => sst_flag_emoji( $iso ),
				'u'  => '',
				'p'  => 0,
				'c'  => array(),
			);
		}
		$out_units[ $su ] = array(
			'i' => $iso,
			'a' => $is_auto ? 1 : 0,
		);
		if ( empty( $units[ $su ]['m'] ) ) {
			$names[ $su ] = sst_map_unit_name( $su );
		}
		if ( $units[ $su ]['c'] ) {
			$continents[ $units[ $su ]['c'] ] = true;
		}
	}
	foreach ( $countries as $iso => &$country ) {
		$country['l']     = sst_map_level( count( $country['c'] ) );
		$country['parts'] = array_keys(
			array_filter(
				$out_units,
				static function ( $u ) use ( $iso ) {
					return $u['i'] === $iso;
				}
			)
		);
		$n_cities += count( $country['c'] );
		if ( ! $country['parts'] && $country['id'] ) {
			// Štát z Destinácií, ktorý na mape nie je (napr. Vatikán) – kontinent podľa nastavenia krajiny.
			$k = get_term_meta( $country['id'], 'sst_continent', true );
			if ( $k ) {
				$continents[ $k ] = true;
			}
		}
	}
	unset( $country );
	// Štát, ktorého všetky časti boli ručne skryté, nepočítame (ak nemá žiadne mesto).
	$countries = array_filter(
		$countries,
		static function ( $c ) {
			return $c['parts'] || $c['c'] || ! $c['id'];
		}
	);

	$data = array(
		'v'          => SST_VERSION,
		'units'      => $out_units,
		'countries'  => $countries,
		'names'      => $names,
		'cities'     => $n_cities,
		'states'     => count( $countries ),
		'continents' => count( $continents ),
		'missing'    => $missing,
	);
	set_transient( 'sst_map_data', $data, DAY_IN_SECONDS );
	return $data;
}

/** Zmena destinácií alebo vyznačení → nové dáta mapy. */
function sst_map_flush() {
	delete_transient( 'sst_map_data' );
}
foreach ( array( 'created_destinacia', 'edited_destinacia', 'delete_destinacia', 'set_object_terms', 'update_option_sst_map_marks', 'add_option_sst_map_marks' ) as $sst_hook ) {
	add_action( $sst_hook, 'sst_map_flush' );
}
add_action( 'transition_post_status', 'sst_map_flush' );
add_action(
	'updated_term_meta',
	static function ( $mid, $term_id, $key ) {
		if ( in_array( $key, array( 'sst_iso', 'sst_lat', 'sst_lng' ), true ) ) {
			sst_map_flush();
		}
	},
	10,
	3
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

/** Údaje pre JavaScript mapy (front aj administrácia). */
function sst_map_payload( $admin = false ) {
	$data    = sst_map_data();
	$meta    = sst_map_meta();
	$payload = array(
		'units'     => $data['units'],
		'countries' => $data['countries'],
		'names'     => $data['names'],
		'regions'   => wp_list_pluck( sst_map_regions(), 'box' ),
		'fit'       => sst_map_fit_box( $data['countries'] ),
		'w'         => $meta['w'],
		'h'         => $meta['h'],
		'scale'     => $meta['scale'],
		'tx'        => $meta['tx'],
		'ty'        => $meta['ty'],
		'stats'     => array(
			'states'     => $data['states'],
			'cities'     => $data['cities'],
			'continents' => $data['continents'],
		),
		'detail'    => SST_URI . '/assets/map/world-detail.svg?ver=' . SST_VERSION,
	);
	if ( $admin ) {
		// Všetky časti s názvami – na vyhľadávanie a vyznačovanie.
		$all = array();
		foreach ( sst_map_units() as $su => $u ) {
			if ( ! $u['i'] ) {
				continue;
			}
			$all[ $su ] = array(
				'i' => $u['i'],
				'n' => sst_map_unit_name( $su ),
				'm' => empty( $u['m'] ) ? 0 : 1,
				'c' => $u['c'],
			);
		}
		$payload['all']   = $all;
		$payload['marks'] = (object) sst_map_marks();
		foreach ( $payload['countries'] as $iso => &$c ) {
			$c['e'] = $c['id'] ? get_edit_term_link( $c['id'], 'destinacia' ) : '';
			foreach ( $c['c'] as &$city ) {
				$city['e'] = get_edit_term_link( $city['id'], 'destinacia' );
			}
			unset( $city );
		}
		unset( $c );
	}
	return $payload;
}

/** Štatistiky pod nadpisom mapy. */
function sst_map_stats_html( array $stats ) {
	$world = max( 1, round( $stats['states'] / 195 * 100 ) );
	$items = array(
		array( $stats['states'], sst_plural( $stats['states'], 'štát', 'štáty', 'štátov' ), 'globe' ),
		array( $stats['cities'], sst_plural( $stats['cities'], 'miesto', 'miesta', 'miest' ), 'pin' ),
		array( $stats['continents'], sst_plural( $stats['continents'], 'kontinent', 'kontinenty', 'kontinentov' ), 'compass' ),
		array( $world . ' %', 'sveta', 'map' ),
	);
	$html = '<ul class="world-map__stats">';
	foreach ( $items as $it ) {
		$html .= '<li><strong>' . esc_html( $it[0] ) . '</strong><span>' . esc_html( $it[1] ) . '</span></li>';
	}
	return $html . '</ul>';
}

/**
 * Vykreslí mapu.
 *
 * @param array $args title (bool) – nadpis sekcie, link (bool) – odkaz na Destinácie.
 */
function sst_world_map( $args = array() ) {
	$args    = wp_parse_args(
		$args,
		array(
			'title' => true,
			'link'  => false,
		)
	);
	$payload = sst_map_payload();
	if ( ! $payload['units'] && ! $payload['countries'] ) {
		return;
	}
	if ( sst_map_data()['missing'] && ! wp_next_scheduled( 'sst_geocode_missing' ) ) {
		wp_schedule_single_event( time() + 5, 'sst_geocode_missing' );
	}
	sst_map_enqueue();
	$regions = sst_map_regions();
	$fit     = $payload['fit'];
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

		<?php echo sst_map_stats_html( $payload['stats'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

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

function sst_map_enqueue() {
	wp_enqueue_style( 'sst-map', SST_URI . '/assets/css/map.css', array(), SST_VERSION );
	wp_enqueue_script( 'sst-map', SST_URI . '/assets/js/map.js', array(), SST_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
}

/** Štýly mapy načítame v hlavičke tam, kde mapa bude (inak by sa načítali až v pätičke). */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( is_front_page() || is_page_template( 'page-templates/template-destinacie.php' ) || ( is_singular() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'spoznajsvet_mapa' ) ) ) {
			wp_enqueue_style( 'sst-map', SST_URI . '/assets/css/map.css', array( 'sst-theme' ), SST_VERSION );
		}
	},
	20
);

add_shortcode(
	'spoznajsvet_mapa',
	static function ( $atts ) {
		$atts = shortcode_atts( array( 'nadpis' => '1' ), (array) $atts, 'spoznajsvet_mapa' );
		ob_start();
		sst_world_map( array( 'title' => '0' !== (string) $atts['nadpis'] ) );
		return ob_get_clean();
	}
);
