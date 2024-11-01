<?php

namespace TOT;

// @todo test
class Hook_WC_Order_Verification_Data {

    public function __construct() {}

    public function register_wordpress_hooks() {
        add_filter('tot_order_verification_data', array($this, 'set_data'), 10, 2);
        add_filter('tot_verify_person_data', array($this, 'set_data'), 10, 2);
    }

    public function set_data( $order_verification_data, $order_id ) {

	    if(false === strpos($order_verification_data['person']['givenName'], 'filter_add_dob')) {
			return $order_verification_data;
	    }

	    $order_verification_data['person']['dateOfBirth'] = array(
		    'day'   => '01',
		    'month' => '01',
		    'year'  => '1990'
	    );

	    return $order_verification_data;
    }

}

(new Hook_WC_Order_Verification_Data())->register_wordpress_hooks();
