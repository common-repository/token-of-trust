<?php

namespace TOT;

class Hook_Set_Connection_Action {

    public function __construct() {
        $this->register_wordpress_hooks();
    }

    public function register_wordpress_hooks() {
        if( isset( $_GET['test_tot_set_connection_action'] ) ) {
            add_action('tot_set_connection_success', array($this, 'set_connection_success'), 10, 3);
            add_action('tot_set_connection_failed', array($this, 'set_connection_failed'), 10, 3);
        }
    }

    public function set_connection_success( $response, $requestDetails, $requestUrl ) {

        echo '<pre id="test_tot_set_connection_action_success_response">' . json_encode($response) . '</pre>';
        echo '<pre id="test_tot_set_connection_action_success_request_details">' . json_encode($requestDetails) . '</pre>';
        echo '<pre id="test_tot_set_connection_action_success_request_url">' . $requestUrl . '</pre>';

    }

    public function set_connection_failed( $response, $requestDetails=null, $requestUrl=null ) {

        echo '<pre id="test_tot_set_connection_action_failed_response">' . json_encode($response) . '</pre>';
        echo '<pre id="test_tot_set_connection_action_failed_request_details">' . json_encode($requestDetails) . '</pre>';
        echo '<pre id="test_tot_set_connection_action_failed_request_url">' . $requestUrl . '</pre>';

    }

}

new Hook_Set_Connection_Action();
