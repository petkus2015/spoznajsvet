<?php
defined( 'ABSPATH' ) || exit;

/**
 * Počasie v destinácii pre termín letenky (Open-Meteo, zadarmo a bez kľúča).
 *
 * - Pobyt do 16 dní: skutočná predpoveď na dni pobytu.
 * - Neskorší odlet: odhad podľa toho, aké bolo počasie v tých mesiacoch posledné 2 roky (zobrazí sa s „~“).
 *
 * Dáta sa sťahujú pri obnove cien (predpoveď max. raz za 3 h, priemery raz za 60 dní)
 * a ukladajú do jednej voľby; pri zobrazení sa nič nesťahuje.
 */
class SVL_Weather {

	const OPTION      = 'svl_weather';
	const FC_TTL      = 3 * HOUR_IN_SECONDS;
	const NORM_TTL    = 60 * DAY_IN_SECONDS;
	const FC_DAYS     = 16;
	const NORM_PER_RUN = 12; // Nových miest s priemermi za jeden beh (archív je väčšia požiadavka).

	/** @var array|null */
	private static $data = null;

	public static function enabled() {
		return (bool) SVL_Settings::get( 'weather' );
	}

	private static function data() {
		if ( null === self::$data ) {
			$d          = get_option( self::OPTION, array() );
			self::$data = is_array( $d ) ? $d + array( 'loc' => array(), 'fc' => array(), 'norm' => array() ) : array( 'loc' => array(), 'fc' => array(), 'norm' => array() );
		}
		return self::$data;
	}

	private static function save( array $d ) {
		self::$data = $d;
		update_option( self::OPTION, $d, false );
	}

	/** Súradnice destinácie: tabuľka letísk, inak vyhľadanie mesta (Open-Meteo geocoding). */
	private static function coords( $iata, $city, $iso, array &$d ) {
		if ( isset( $d['loc'][ $iata ] ) ) {
			return $d['loc'][ $iata ] ? $d['loc'][ $iata ] : null;
		}
		static $table = null, $lookups = 0;
		if ( null === $table ) {
			$table = require SVL_DIR . 'includes/airport-coords.php';
		}
		if ( isset( $table[ $iata ] ) ) {
			$d['loc'][ $iata ] = $table[ $iata ];
			return $table[ $iata ];
		}
		if ( ++$lookups > 8 ) {
			return null; // Ďalšie neznáme miesta dohľadáme pri ďalšej obnove.
		}
		$json = self::get(
			add_query_arg(
				array(
					'name'        => rawurlencode( $city ? $city : $iata ),
					'count'       => 5,
					'language'    => 'en',
					'countryCode' => $iso,
				),
				'https://geocoding-api.open-meteo.com/v1/search'
			)
		);
		$hit = $json['results'][0] ?? null;
		$d['loc'][ $iata ] = $hit ? array( round( (float) $hit['latitude'], 2 ), round( (float) $hit['longitude'], 2 ) ) : false;
		return $d['loc'][ $iata ] ? $d['loc'][ $iata ] : null;
	}

	private static function get( $url ) {
		$res = wp_remote_get( $url, array( 'timeout' => 20, 'headers' => array( 'Accept' => 'application/json' ) ) );
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			return null;
		}
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		return is_array( $json ) ? $json : null;
	}

	/** Open-Meteo vráti pri jednom mieste objekt, pri viacerých pole. */
	private static function each_location( $json, $count ) {
		if ( ! is_array( $json ) ) {
			return array();
		}
		if ( isset( $json['daily'] ) ) {
			return array( $json );
		}
		return array_slice( array_values( $json ), 0, $count );
	}

	/** Kategória počasia z kódu WMO. */
	public static function category( $code ) {
		$code = (int) $code;
		if ( $code <= 1 ) {
			return 'sun';
		}
		if ( 2 === $code ) {
			return 'partly';
		}
		if ( 3 === $code || 45 === $code || 48 === $code ) {
			return 'cloud';
		}
		if ( $code >= 95 ) {
			return 'storm';
		}
		if ( ( $code >= 71 && $code <= 77 ) || 85 === $code || 86 === $code ) {
			return 'snow';
		}
		return 'rain';
	}

	/**
	 * Stiahne, čo chýba (volá SVL_Store::refresh). Chyby počasia nikdy nezastavia letenky.
	 */
	public static function update( array $deals ) {
		if ( ! self::enabled() || ! $deals ) {
			return;
		}
		$d    = self::data();
		$dest = array();
		foreach ( $deals as $deal ) {
			$dest[ $deal['destination'] ] = $deal;
		}
		$pts = array();
		foreach ( $dest as $iata => $deal ) {
			$ll = self::coords( $iata, $deal['dest_city'], $deal['dest_iso'], $d );
			if ( $ll ) {
				$pts[ $iata ] = $ll;
			}
		}

		// 1) Predpoveď na 16 dní – všetky destinácie naraz (po 50).
		$stale = array_filter(
			array_keys( $pts ),
			static function ( $iata ) use ( $d ) {
				return empty( $d['fc'][ $iata ]['t'] ) || time() - $d['fc'][ $iata ]['t'] > self::FC_TTL;
			}
		);
		foreach ( array_chunk( $stale, 50 ) as $chunk ) {
			$json = self::get(
				add_query_arg(
					array(
						'latitude'      => implode( ',', wp_list_pluck( array_intersect_key( $pts, array_flip( $chunk ) ), 0 ) ),
						'longitude'     => implode( ',', wp_list_pluck( array_intersect_key( $pts, array_flip( $chunk ) ), 1 ) ),
						'daily'         => 'weather_code,temperature_2m_max',
						'forecast_days' => self::FC_DAYS,
						'timezone'      => 'auto',
					),
					'https://api.open-meteo.com/v1/forecast'
				)
			);
			foreach ( self::each_location( $json, count( $chunk ) ) as $i => $loc ) {
				$days = array();
				foreach ( (array) ( $loc['daily']['time'] ?? array() ) as $k => $day ) {
					if ( isset( $loc['daily']['temperature_2m_max'][ $k ], $loc['daily']['weather_code'][ $k ] ) ) {
						$days[ $day ] = array( round( (float) $loc['daily']['temperature_2m_max'][ $k ] ), (int) $loc['daily']['weather_code'][ $k ] );
					}
				}
				if ( $days && isset( $chunk[ $i ] ) ) {
					$d['fc'][ $chunk[ $i ] ] = array(
						't'    => time(),
						'days' => $days,
					);
				}
			}
		}

		// 2) Mesačné priemery z posledných 2 rokov – postupne, max. NORM_PER_RUN miest za beh.
		$missing = array_filter(
			array_keys( $pts ),
			static function ( $iata ) use ( $d ) {
				return empty( $d['norm'][ $iata ]['t'] ) || time() - $d['norm'][ $iata ]['t'] > self::NORM_TTL;
			}
		);
		$year = (int) gmdate( 'Y' );
		foreach ( array_chunk( array_slice( array_values( $missing ), 0, self::NORM_PER_RUN ), 4 ) as $chunk ) {
			$sel  = array_intersect_key( $pts, array_flip( $chunk ) );
			$json = self::get(
				add_query_arg(
					array(
						'latitude'   => implode( ',', wp_list_pluck( $sel, 0 ) ),
						'longitude'  => implode( ',', wp_list_pluck( $sel, 1 ) ),
						'start_date' => ( $year - 2 ) . '-01-01',
						'end_date'   => ( $year - 1 ) . '-12-31',
						'daily'      => 'weather_code,temperature_2m_max',
						'timezone'   => 'auto',
					),
					'https://archive-api.open-meteo.com/v1/archive'
				)
			);
			foreach ( self::each_location( $json, count( $chunk ) ) as $i => $loc ) {
				$months = array();
				foreach ( (array) ( $loc['daily']['time'] ?? array() ) as $k => $day ) {
					$t = $loc['daily']['temperature_2m_max'][ $k ] ?? null;
					$c = $loc['daily']['weather_code'][ $k ] ?? null;
					if ( null === $t || null === $c ) {
						continue;
					}
					$m = (int) substr( $day, 5, 2 );
					if ( ! isset( $months[ $m ] ) ) {
						$months[ $m ] = array( 'sum' => 0, 'n' => 0, 'cat' => array() );
					}
					$months[ $m ]['sum'] += (float) $t;
					++$months[ $m ]['n'];
					$cat                   = self::category( $c );
					$months[ $m ]['cat'][ $cat ] = ( $months[ $m ]['cat'][ $cat ] ?? 0 ) + 1;
				}
				$norm = array();
				foreach ( $months as $m => $v ) {
					$norm[ $m ] = array( round( $v['sum'] / $v['n'], 1 ), $v['n'], $v['cat'] );
				}
				if ( $norm && isset( $chunk[ $i ] ) ) {
					$d['norm'][ $chunk[ $i ] ] = array(
						't' => time(),
						'm' => $norm,
					);
				}
			}
		}

		// Staré záznamy destinácií, ktoré už nie sú v ponukách, zahodíme (okrem súradníc).
		foreach ( array( 'fc', 'norm' ) as $k ) {
			foreach ( array_keys( $d[ $k ] ) as $iata ) {
				if ( ! isset( $dest[ $iata ] ) && time() - $d[ $k ][ $iata ]['t'] > 30 * DAY_IN_SECONDS ) {
					unset( $d[ $k ][ $iata ] );
				}
			}
		}
		self::save( $d );
	}

	/** Prevládajúce počasie z počtov dní podľa kategórie. */
	private static function dominant( array $counts ) {
		$total = array_sum( $counts );
		if ( ! $total ) {
			return 'partly';
		}
		$wet = ( $counts['rain'] ?? 0 ) + ( $counts['storm'] ?? 0 ) + ( $counts['snow'] ?? 0 );
		if ( $wet / $total >= 0.4 ) {
			if ( ( $counts['snow'] ?? 0 ) > ( $counts['rain'] ?? 0 ) + ( $counts['storm'] ?? 0 ) ) {
				return 'snow';
			}
			return ( $counts['storm'] ?? 0 ) > ( $counts['rain'] ?? 0 ) ? 'storm' : 'rain';
		}
		if ( ( $counts['sun'] ?? 0 ) / $total >= 0.5 ) {
			return 'sun';
		}
		if ( ( $counts['cloud'] ?? 0 ) / $total >= 0.5 ) {
			return 'cloud';
		}
		return 'partly';
	}

	/**
	 * Počasie pre ponuku (dni pobytu).
	 *
	 * @return array{t:int,cat:string,exact:bool}|null
	 */
	public static function for_deal( array $deal ) {
		if ( ! self::enabled() || empty( $deal['depart'] ) ) {
			return null;
		}
		$d     = self::data();
		$iata  = $deal['destination'];
		$start = strtotime( $deal['depart'] . ' 12:00 UTC' );
		$end   = $deal['return'] ? strtotime( $deal['return'] . ' 12:00 UTC' ) - DAY_IN_SECONDS : $start;
		$end   = max( $start, min( $end, $start + 30 * DAY_IN_SECONDS ) );

		// Skutočná predpoveď, ak pokrýva deň odletu.
		$fc = $d['fc'][ $iata ]['days'] ?? array();
		if ( isset( $fc[ gmdate( 'Y-m-d', $start ) ] ) ) {
			$temps  = array();
			$counts = array();
			for ( $t = $start; $t <= $end; $t += DAY_IN_SECONDS ) {
				$day = gmdate( 'Y-m-d', $t );
				if ( ! isset( $fc[ $day ] ) ) {
					break;
				}
				$temps[]           = $fc[ $day ][0];
				$cat               = self::category( $fc[ $day ][1] );
				$counts[ $cat ]    = ( $counts[ $cat ] ?? 0 ) + 1;
			}
			return array(
				't'     => (int) round( array_sum( $temps ) / count( $temps ) ),
				'cat'   => self::dominant( $counts ),
				'exact' => true,
			);
		}

		// Odhad podľa mesačných priemerov.
		$norm = $d['norm'][ $iata ]['m'] ?? array();
		if ( ! $norm ) {
			return null;
		}
		$sum    = 0;
		$n      = 0;
		$counts = array();
		for ( $t = $start; $t <= $end; $t += DAY_IN_SECONDS ) {
			$m = (int) gmdate( 'n', $t );
			if ( ! isset( $norm[ $m ] ) ) {
				continue;
			}
			$sum += $norm[ $m ][0];
			++$n;
			foreach ( $norm[ $m ][2] as $cat => $c ) {
				$counts[ $cat ] = ( $counts[ $cat ] ?? 0 ) + $c / max( 1, $norm[ $m ][1] );
			}
		}
		if ( ! $n ) {
			return null;
		}
		return array(
			't'     => (int) round( $sum / $n ),
			'cat'   => self::dominant( $counts ),
			'exact' => false,
		);
	}

	public static function label( $cat ) {
		$labels = array(
			'sun'    => 'slnečno',
			'partly' => 'polooblačno',
			'cloud'  => 'oblačno',
			'rain'   => 'dážď',
			'storm'  => 'búrky',
			'snow'   => 'sneženie',
		);
		return $labels[ $cat ] ?? '';
	}

	/** Kedy: „v októbri“ – mesiac odletu (pre odhad podľa minulých rokov). */
	private static function month_in( $date ) {
		$months = array( 1 => 'v januári', 'vo februári', 'v marci', 'v apríli', 'v máji', 'v júni', 'v júli', 'v auguste', 'v septembri', 'v októbri', 'v novembri', 'v decembri' );
		return $months[ (int) gmdate( 'n', strtotime( $date . ' 12:00 UTC' ) ) ];
	}

	/**
	 * HTML počasia: ikona + teplota, pri odhade „~“.
	 *
	 * @param array  $deal Ponuka.
	 * @param string $mode badge (ikona + °), chip (+ popis) alebo strip (riadok na lístku s vysvetlivkou).
	 */
	public static function badge( array $deal, $mode = 'badge' ) {
		$w = self::for_deal( $deal );
		if ( ! $w ) {
			return '';
		}
		$label = self::label( $w['cat'] );
		$when  = self::month_in( $deal['depart'] );
		$sr    = $w['exact']
			? sprintf( 'Predpoveď počasia v destinácii: %s, %d °C', $label, $w['t'] )
			: sprintf( 'Odhad počasia v destinácii (podľa minulých rokov): väčšinou %s, okolo %d °C', $label, $w['t'] );
		$temp  = sprintf( '<b class="svl-wx__t">%s%d°</b>', $w['exact'] ? '' : '~', (int) $w['t'] );
		$body  = $temp;
		if ( 'chip' === $mode ) {
			$body .= '<span class="svl-wx__label">' . esc_html( $label ) . '</span>';
		} elseif ( 'strip' === $mode ) {
			$body .= '<span class="svl-wx__label">' . esc_html( $w['exact'] ? $label : 'väčšinou ' . $label ) . '</span>'
				. '<span class="svl-wx__hint">' . esc_html( $w['exact'] ? 'predpoveď na tvoj termín' : 'bežne ' . $when ) . '</span>';
		}
		return sprintf(
			'<span class="svl-wx svl-wx--%1$s svl-wx--%2$s%3$s" title="%4$s"><span class="svl-wx__icon" aria-hidden="true"></span><span class="svl-wx__body" aria-hidden="true">%5$s</span><span class="svl-sr">%6$s</span></span>',
			esc_attr( $w['cat'] ),
			esc_attr( $mode ),
			$w['exact'] ? '' : ' is-estimate',
			esc_attr( $sr ),
			$body,
			esc_html( $sr )
		);
	}
}
