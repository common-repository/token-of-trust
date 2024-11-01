<?php
/**
 *
 * TOT API Connection
 *
 * References
 *   - TOT API docs
 *     https://docs.google.com/document/d/1xQ9yymU1CVt5BWxNsmkI76S3otrMO1kuW3HruMkcKrk/edit#heading=h.iuu091a3j2tn
 *     https://app.tokenoftrust.com/developer/guide/embed/
 *
 */

namespace TOT;
use WP_Error;

class API_Connection {

	public $base_url;
	public $public_key;
	public $secret_key;
	public $app_domain;
	public $supported_localhost_ports = [80,443,3000,3001,3443,7888,8000,8080,8888,32080,32443,33080];

	public function __construct() {
		$this->base_url = \tot_origin();
		$this->app_domain = \tot_get_option('tot_field_prod_domain');
		$key_result = $this->load_keys();
		$host_result = $this->verify_host();

		if( is_wp_error($host_result) ) {
			return $host_result;
		}elseif ( is_wp_error($key_result) ) {
			return $key_result;
		}

	}

	public function load_keys() {
		$this->public_key = tot_get_public_key();
		$this->secret_key = tot_get_secret_key();

		if (\is_wp_error($this->public_key)) {
			return $this->public_key;
		}elseif (is_wp_error($this->secret_key)) {
			return $this->secret_key;
		}
	}

	public function check_ports($port) {
		return in_array($port, $this->supported_localhost_ports);
	}

	public function verify_host() {
		// check to make sure the user is not trying to run from an unsupported localhost port.
		$localhost = "localhost";
		$port = $_SERVER['SERVER_PORT'];
		$host = $_SERVER['HTTP_HOST'];

		if (substr($host, 0, strlen($localhost)) === $localhost && ($port && !$this->check_ports($port))) {
			return new WP_Error('unsupported_api_host', 'Attempted to connect to API using unsupported localhost port' . $port);
		}
	}

}