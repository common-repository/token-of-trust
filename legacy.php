<?php

function tot_ssl_verify() {
//	if(tot_test_origin() == 'https://my.tokenoftrust.com:3443') {
		return false;
//	}
//	return true;
}

function tot_get_option($key) {
	return TOT\Settings::get_setting($key);
}

function tot_debug_mode () {
    return time() < \TOT\Settings::get_setting('debug_mode');
}