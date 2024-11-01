<?php
use TOT\Settings;
function tot_set_default_options()
{
	// no keys are connected yet
	delete_transient('tot_keys');

	// woocommerce settings
	Settings::set_setting('tot_field_checkout_require_total_amount', '0');
	Settings::set_setting('tot_field_woo_enable_verification_before_payment', 1);
    Settings::set_setting('tot_field_enable_backend_checkout_verification', 1);
}