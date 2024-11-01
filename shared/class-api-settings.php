<?php

/**
 *
 * TOT API API_Settings Request
 *
 * to sync data from WordPress to the dashboard of Token of trust
 * Supported properties:
 *    minimumAge
 *
 * References
 *   - TOT API docs
 */

namespace TOT;

class API_Settings extends API_Request
{
	public $endpoint_url;
	public $request_details;

	public function __construct()
	{
		$endpoint_path = 'api/apps/' . \tot_get_option('tot_field_prod_domain');
		$data = $this->getData();
		$headers = array(
			'Content-Type' => 'application/json',
			'Accept' => 'application/json'
		);
		parent::__construct($endpoint_path, $data, 'POST', $headers);
	}

	private function getData()
	{
		$tot_options = get_option('tot_options');
		return [
			'appDomain' => \tot_get_option('tot_field_prod_domain'),
			'app' => [
				'options' => [
					'realWorld' => [
						'minimumAge' => $tot_options['tot_field_min_age'] ?? 21
					]
				]
			]
		];
	}

	public function set_details($data = array(), $method = 'POST', $headers = array())
	{
		$license_key = get_option('tot_options')['tot_field_license_key'];
		$this->request_details = array(
			'method' => $method,
			'headers' => array_merge(array(
				'charset' => 'utf-8',
				'Accept-Language' => 'en-US,en;q=0.9',
				'Authorization' => $license_key,
				'Referer' => $this->app_domain
			), $headers),
			'body' => json_encode($data),
			'sslverify' => tot_ssl_verify(),
			'timeout' => $this->timeout
		);
	}
}