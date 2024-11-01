<?php
/**
 *
 * TOT API Response
 */

namespace TOT;

class API_Response {

	public $error            = null;
	public $error_details    = null;
    public $response_code    = null;
    public $response_raw     = null;
	public $body_decoded = null;
	public $request          = null;
	public $url              = null;

	public function __construct( $response, $request, $url ) {

		$this->response_raw = $response;
		$this->request      = $request;
		$this->url          = $url;

		$this->set_wp_error();
		$this->decode();
		$this->set_api_error();

	}

	public function set_wp_error($force = false) {

		if ( $force || is_wp_error( $this->response_raw ) ) {
            $hasResponseCode = !is_wp_error($this->response_raw) && isset($this->response_raw['response']) && isset($this->response_raw['response']['code']);
            $this->http_response_code = !$hasResponseCode ? 500 : $this->response_raw['response']['code'];
            $this->error_details = array(
                'request_url' => $this->url,
                'request' => $this->request,
                'response' => $this->response_raw,
                'http_response_code' => $this->http_response_code
            );
			tot_debugger::inst()->log('There was an error connecting to the Token of Trust API.', $this->error_details, 'error');
            $this->error = tot_respond_to_error_with_link(
				'tot_api_error',
				'There was an error connecting to the Token of Trust API.',
                $this->error_details
			);
		}

	}

    private function getHttpResponseCode($response_raw)
    {
        $hasResponseCode = !is_wp_error($response_raw) && isset($response_raw['response']) && isset($response_raw['response']['code']);
        return !$hasResponseCode ? 500 : $response_raw['response']['code'];
    }

	public function decode() {

		if ( null !== $this->error ) {
			return null;
		}

		$decoded = json_decode( $this->response_raw['body'] );
        $this->http_response_code = $this->getHttpResponseCode($this->response_raw);

        if (empty( $decoded )
			|| ! isset( $decoded->content )
			|| !$this->http_response_code) {
            $this->set_wp_error(true);
			return;
		}

		$this->body_decoded = $decoded->content;
	}

	public function set_api_error() {

		if ( null !== $this->error ) {
			return null;
		}

        $this->http_response_code = $this->getHttpResponseCode($this->response_raw);
		$http_code = strval( $this->http_response_code );

		if (( isset( $this->body_decoded->content->type ) && ( 'error' === $this->body_decoded->content->type ) )
            // Or if it's a 2xx code.
			|| '2' !== substr( $http_code, 0, 1 )) {

			$this->error = tot_respond_to_error_with_link(
				'tot_api_error_response',
				'The Token of Trust API responded with an error.',
				array(
					'request_url' => $this->url,
					'request'     => $this->request,
					'response'    => $this->response_raw,
				),
				$this->body_decoded
			);
			tot_debugger::inst()->log('The Token of Trust API responded with an error.', $this->error, 'error');
		}

	}

	public function error_with_response() {
	    if (!isset($this->body_decoded)) {
	        return false;
        }
        
		return null !== $this->error && $this->body_decoded->code !== 'WorkflowService:nextStepIsInteractive';
	}
        
	public function is_next_step_interactive(){
		return isset($this->body_decoded->code) && $this->body_decoded->code === 'WorkflowService:nextStepIsInteractive';
	}

}