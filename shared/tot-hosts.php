<?php

function tot_origin() {
    return apply_filters('tot_origin', tot_production_origin());
}

function tot_test_origin() {
	$origin = 'https://sandbox.tokenoftrust.com';
	return apply_filters('tot_test_origin', $origin);
}

function tot_production_origin() {
    $origin = 'https://app.tokenoftrust.com';
    return apply_filters('tot_production_origin', $origin);
}

function tot_checkout_js() {
    $origin = tot_origin() . '/dist/tot-bind.bundle.js';
    return apply_filters('tot-bind-js', $origin);
}
