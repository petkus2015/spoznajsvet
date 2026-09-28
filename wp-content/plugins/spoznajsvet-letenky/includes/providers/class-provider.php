<?php
defined( 'ABSPATH' ) || exit;

/**
 * Základ poskytovateľa letenkových dát.
 *
 * Skupiny: „exotika“ (Bangkok, Dubaj, Abu Dhabi – spiatočné) a „europa“
 * (najlacnejšie európske mestá z Bratislavy/Viedne – jednosmerné).
 */
abstract class SVL_Provider {

	abstract public function id();

	abstract public function label();

	/** Podporuje poskytovateľ danú skupinu? */
	abstract public function supports( $group );

	/**
	 * @return array Zoznam ponúk (SVL_Format::deal).
	 * @throws RuntimeException Pri zlyhaní – použijú sa posledné platné dáta.
	 */
	abstract public function fetch( $group );

	public function enabled() {
		return (bool) SVL_Settings::get( $this->id() );
	}

	protected function headers( $extra = array() ) {
		return array_merge(
			array(
				'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36',
				'Accept'          => 'application/json, text/plain, */*',
				'Accept-Language' => 'sk-SK,sk;q=0.9,en;q=0.8',
			),
			$extra
		);
	}

	protected function request_json( $method, $url, $body = null, $headers = array() ) {
		$args = array(
			'method'  => $method,
			'timeout' => 20,
			'headers' => $this->headers( $headers ),
		);
		if ( null !== $body ) {
			$args['body']                    = wp_json_encode( $body );
			$args['headers']['Content-Type'] = 'application/json';
		}
		$res = wp_remote_request( $url, $args );
		if ( is_wp_error( $res ) ) {
			throw new RuntimeException( $res->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( $code < 200 || $code >= 300 ) {
			throw new RuntimeException( sprintf( 'HTTP %d z %s', $code, wp_parse_url( $url, PHP_URL_HOST ) ) );
		}
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $json ) ) {
			throw new RuntimeException( 'Neplatná odpoveď (nie JSON) z ' . wp_parse_url( $url, PHP_URL_HOST ) );
		}
		return $json;
	}

	protected function get_json( $url, $headers = array() ) {
		return $this->request_json( 'GET', $url, null, $headers );
	}

	protected function post_json( $url, $body, $headers = array() ) {
		return $this->request_json( 'POST', $url, $body, $headers );
	}

	/**
	 * Spustí $fn pre každý prvok; zlyhania zbiera. Výnimku hodí len ak zlyhali všetky.
	 */
	protected function each_tolerant( array $items, callable $fn ) {
		$out    = array();
		$errors = array();
		foreach ( $items as $item ) {
			try {
				$out = array_merge( $out, (array) $fn( $item ) );
			} catch ( Throwable $e ) {
				$errors[] = ( is_scalar( $item ) ? $item : implode( '-', (array) $item ) ) . ': ' . $e->getMessage();
			}
		}
		if ( $errors && ! $out && count( $errors ) === count( $items ) ) {
			throw new RuntimeException( implode( '; ', array_slice( $errors, 0, 3 ) ) );
		}
		return $out;
	}
}
