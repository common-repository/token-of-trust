<?php

namespace TOT;

// @todo test
class Hook_WC_Is_Verification_Required_For_Order {

    public function __construct() {
        $this->register_wordpress_hooks();
    }

    public function register_wordpress_hooks() {
        add_filter('tot_is_verification_required_for_order', array($this, 'is_verification_required_for_order'), 10, 3);
    }

    public function is_verification_required_for_order( $requires_verification, $order_id, $cart ) {

    	// Note $order_id may be null if $cart is available and user has NOT completed checkout
	    // Note $cart may be null if $order_id is available and user HAS completed checkout

	    return $requires_verification;
    }

}

new Hook_WC_Is_Verification_Required_For_Order();
