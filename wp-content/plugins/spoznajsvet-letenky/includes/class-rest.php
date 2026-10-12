<?php
defined( 'ABSPATH' ) || exit;

/**
 * GET /wp-json/spoznajsvet/v1/letenky – dáta aj hotové HTML (na obnovu bez reloadu stránky).
 */
class SVL_Rest {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			'spoznajsvet/v1',
			'/letenky/obnovit',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'trigger' ),
			)
		);
		register_rest_route(
			'spoznajsvet/v1',
			'/letenky',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'get' ),
				'args'                => array(
					'skupina'   => array(
						'type'    => 'string',
						'enum'    => array( 'exotika', 'europa', 'vsetko' ),
						'default' => 'vsetko',
					),
					'limit'     => array(
						'type'    => 'integer',
						'default' => 12,
						'minimum' => 1,
						'maximum' => 200,
					),
					'nadpis'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'kompaktne' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'stav'      => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);
	}

	/** POST /letenky/obnovit – obnova na vyžiadanie (len ak sú ceny staršie ako 15 min). */
	public static function trigger() {
		$refreshing = SVL_Cron::refresh_if_stale();
		$res        = rest_ensure_response(
			array(
				'updated'    => (int) SVL_Store::data()['updated'],
				'refreshing' => $refreshing,
				'max_age'    => SVL_Cron::MAX_AGE,
			)
		);
		$res->header( 'Cache-Control', 'no-store' );
		return $res;
	}

	public static function get( WP_REST_Request $req ) {
		$group = $req['skupina'];
		$data  = SVL_Store::data();
		$deals = array_values(
			array_filter(
				$data['deals'],
				static function ( $d ) use ( $group ) {
					return 'vsetko' === $group || $d['group'] === $group;
				}
			)
		);
		$res = rest_ensure_response(
			array(
				'updated' => (int) $data['updated'],
				'next'    => SVL_Cron::next_run(),
				'deals'   => $deals,
				'html'    => SVL_Render::html( $group, (int) $req['limit'], (bool) $req['nadpis'], (bool) $req['kompaktne'], (bool) $req['stav'] ),
			)
		);
		$res->header( 'Cache-Control', SVL_Cron::age() < SVL_Cron::MAX_AGE ? 'public, max-age=60' : 'no-store' );
		return $res;
	}
}
