<?php

namespace TOT;

// @todo test
class Hook_WC_Process_Order_Set_Quarantine {

    public function __construct() {
        $this->register_wordpress_hooks();
    }

    public function register_wordpress_hooks() {
        add_filter('tot_process_order_set_quarantine', array($this, 'set_quarantine'), 10, 3);
    }

    public function set_quarantine( $set_quarantine, $order_id, $verify_result ) {

	    $order = tot_wc_get_order( $order_id );

	    if( 'apply_set_quarantine_filter' !== $order->get_shipping_first_name() ) {
	    	return $set_quarantine;
	    }

	    $connection_request = new \TOT\API_Request(
		    'api/reputation/transaction/' . $order_id,
		    [],
		    'GET'
	    );

	    $results = $connection_request->send();

	    if(is_wp_error($results) || !isset($results->reasons)) {
	    	return false;
	    }

		return true;
    }

}

new Hook_WC_Process_Order_Set_Quarantine();
