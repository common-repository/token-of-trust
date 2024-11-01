<?php
/**
 *
 * TOT API Request
 *
 * References
 *   - TOT API docs
 *     https://docs.google.com/document/d/1xQ9yymU1CVt5BWxNsmkI76S3otrMO1kuW3HruMkcKrk/edit#heading=h.iuu091a3j2tn
 *     https://app.tokenoftrust.com/developer/guide/embed/
 *
 */

namespace TOT;

class API_Request extends API_Connection {

	public $endpoint_url;
	public $request_details;
	public $data;
    public $timeout;

    public function __construct( $endpoint_path, $data = array(), $method = 'POST', $headers = array() ) {
		$php_timeout = ini_get("max_execution_time");
		$this->timeout = min(is_numeric($php_timeout) ? $php_timeout : 20, 20);

        $connection = parent::__construct();

		if(is_wp_error($connection)) {
			return $connection;
		}

		$this->endpoint_url = $this->base_url . '/' . $endpoint_path;
		$this->set_details($data, $method, $headers);

        if ( $php_timeout && $php_timeout < $this->timeout ){
            $error_details = array(
                'php_variable' => 'max_execution_time',
                'message' => "request timeout (" . $this->timeout . " sec) > max_execution_time can cause PHP timeout (" . $php_timeout . " sec)."
            );

            tot_debugger::inst()->log('PHP Configuration Error',$error_details, 'error');
        }
    }

    public function set_details( $data = array(), $method = 'POST', $headers = array() ) {
        $this->data = $data;
        $this->request_details = array(
			'method' => $method,
			'headers' => array_merge(array(
				'charset' => 'utf-8',
				'Accept-Language' => 'en-US,en;q=0.9'
			), $headers),
			'body' => array_merge($data, array(
				'totApiKey' => $this->public_key,
				'totSecretKey' => $this->secret_key,
				'appDomain' => $this->app_domain
			)),
			'sslverify' => tot_ssl_verify(),
            'timeout' => $this->timeout
		);

	}

	public function send( $error_callback = null ) {

        tot_debugger::inst()->log('API_Request - send', array(
            'endpoint_url' => $this->endpoint_url,
            'request_details' => $this->request_details
        ));

        $response = wp_remote_post(
			$this->endpoint_url,
			$this->request_details
		);
        return $this->handle_response( $response, $error_callback);
	}

	public function sendRaw() {

		tot_debugger::inst()->log('API_Request - raw send', array(
            'endpoint_url' => $this->endpoint_url,
            'request_details' => $this->request_details
        ));

        $response = wp_remote_post(
			$this->endpoint_url,
			$this->request_details
		);
        return $response;
	}
    
    public function get_array_for_request_multiple()
    {
        return [
            'url' => $this->endpoint_url,
            'headers' => $this->request_details['headers'] ?? [],
            'data' => $this->request_details['body'] ?? [],
            'type' => $this->request_details['method'] == 'POST'
                ? \WpOrg\Requests\Requests::POST
                : \WpOrg\Requests\Requests::GET
        ];
    }

    /**
     * @param $response
     * @param null $error_callback
     * @return Object
     */
	public function handle_response( $response, $error_callback = null ) {

        $request = $this->request_details;
        $url = $this->endpoint_url;
        $response = new API_Response( $response, $request, $url);

		if ( $response->error_with_response() && empty( $error_callback ) ) {

			return $response->error;

		} elseif ( $response->error_with_response() && ! empty( $error_callback ) ) {

			call_user_func( $error_callback, $response, $request, $url, $this->data);
			return $response->error;

		} elseif ($response->is_next_step_interactive() && ! empty( $error_callback ) ) {
			// it's not an error but the user needs to verify his identity
			call_user_func( $error_callback, $response, $request, $url, $this->data);
		}

		return $response->body_decoded;
	}

}