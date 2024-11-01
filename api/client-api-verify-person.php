<?php

namespace TOT\Admin;

use TOT\API_Response;
use TOT\tot_debugger;

/**
 * Uses the wp_ajax_ mechanisms to create an endpoint for a request to verify person from the
 * WP/Woo server side.
 *
 * Class Client_API_VerifyPerson
 * @package TOT\Admin
 */
class Client_API_VerifyPerson {

    public function __construct() {
    }
    
    /**
     * After all the matching, validation and parsing, this is the actual processing.
     * @param $route
     * @param $body
     */
    public function do_process_request($route, $body)
    {
        $body = (array)$body;
        $clientAppData = $body['appData'];
        $this->validateNonce($route, $clientAppData);
        $verify = new \TOT\API_Person();
        $verify->set_details_from_appData($clientAppData);

        if (!empty($body['appReservationToken'])) {
            $verify->set_totReservationToken( $body['appReservationToken']);
			tot_debugger::inst()->add_part_to_operation('', 'ajax appReservationToken', $body['appReservationToken']);
        }

        $response = $verify->sendRaw();
        $response = new API_Response( $response, $verify->request_details, $verify->endpoint_url);
        if ($response->error_with_response()) {
            $response_error = $response->error_details;
	        tot_debugger::inst()->add_part_to_operation('','handle_verify_person_error_response : '. $route, $response_error);
            wp_send_json(array(
                'message' => 'verifyPerson api error - check logs for details.'
            ), $response->http_response_code);
        } else {
            $body_decoded = $response->body_decoded;
            //tot_log_in_debug_log('handle_verify_person_response : '. $route, $response);
            if (isset($body_decoded->appReservationToken)) {
                // Record the reservationToken on the server side / in a cookie?
                $appReservationToken = $body_decoded->appReservationToken;
	            tot_debugger::inst()->add_part_to_operation('','store new appReservationToken', $appReservationToken);
                // Set the reservation as a long duration cookie.
                tot_set_time_based_cookie('totReservationToken', $appReservationToken, 60*60*24*89);
            }
            wp_send_json($body_decoded, 200);
        }
    }

    public function register_routes()
    {
        if ( is_admin() ) {
//            tot_log_in_debug_log('registering verify_person route:', array(
//                '$REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
//                '$current_route' => $_SERVER['REQUEST_URI'],
//                '$_REQUEST' => $_REQUEST
//            ));
            add_action( 'wp_ajax_tot_verify_person', array($this, 'process_request') );
            add_action( 'wp_ajax_nopriv_tot_verify_person', array($this, 'process_request') );
        }

    }

    public function process_request() {
//        tot_log_in_debug_log('attempting to process verify_person route:', array(
//            '$REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
//            '$current_route' => $_SERVER['REQUEST_URI']
//        ));
	    tot_debugger::inst()->register_new_operation(__FUNCTION__);

        if (!$this->validate_request()) {
            $this->handle_invalid_request();
        } else {
            $this->handle_valid_request();
        }

		tot_debugger::inst()->log_operation(__FUNCTION__);
    }

    public function handle_invalid_request() {
        $input = $this->get_post_body();
        $body = json_decode($input);
        do_action('tot_invalid_api_request', $body, $input);
        $route = $this->getRoute();
        
        $error_details = array(
            'route' => $route,
            'body' => $body,
            'input' => $input
        );

	    tot_debugger::inst()->add_part_to_operation('','Invalid tot api request', $error_details);
        wp_send_json(json_decode('{}'), 422);
    }

    public function handle_valid_request()
    {
        $input = $this->get_post_body();
        $body = json_decode($input);
        if (empty($body)) {
            $body = $_REQUEST;
        }
        $route = $this->getRoute();

		tot_debugger::inst()->add_part_to_operation('','valid tot api request: ' . $route, array(
			'route' => $route,
			'body' => $body,
			'input' => $input
		));
        do_action('pre:' . $route, $body, $input);

        $this->do_process_request($route, $body);

        do_action('post:' . $route, $body, $input);
    }

    public function validate_request()
    {
//        tot_log_in_debug_log('verify person api request: ', array(
//            '$REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
//            '$current_route' => $_SERVER['REQUEST_URI']
//        ));
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public function get_post_body()
    {
        return @file_get_contents('php://input');
    }

    /**
     * By default 'token-of-trust/verifyPerson'
     * @return String
     */
    public function getRoute()
    {
        return admin_url('admin-ajax.php') . '?action=tot_verify_person';
    }

    /**
     * @param $route
     * @param $body - where the nonce is located.
     */
    public function validateNonce($route, $body): void
    {
        $body = (array)$body;
        $nonce_value = wc_get_var($body['woocommerce-process-checkout-nonce'], wc_get_var($body['_wpnonce'], ''));
        if (empty($nonce_value) || !wp_verify_nonce($nonce_value, 'woocommerce-process_checkout')) {
            // Taken from WooCommerce internals: https://woocommerce.wp-a2z.org/oik_api/wc_checkoutprocess_checkout/
            if (empty($nonce_value)) {
	            tot_debugger::inst()->add_part_to_operation('', 'Error in : ' . $route, 'Empty woocommerce-nonce');
            } else {
	            tot_debugger::inst()->add_part_to_operation('','Error in : ' . $route, 'Invalid woocommerce-nonce');
            }
            $wc = \WC();
            $wc->session->set('refresh_totals', true);
            throw new \Exception(__('We were unable to process your order, please try again.', 'woocommerce'));
        } else {
	        tot_debugger::inst()->add_part_to_operation('','Nonce Validation for : ' . $route, 'OK.');
        }
    }

}
