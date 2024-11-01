<?php
require 'educational-content-div.php';

if (isset($_GET['settings-updated'])) {
	tot_refresh_keys();
    $tot_admin_connection_test = tot_get_admin_access_token(true);
} else {
    $tot_admin_connection_test = tot_get_admin_access_token();
}

$tot_keys = tot_get_keys();
$testKeyResult = tot_keys_work('test');
$liveKeyResult =  tot_keys_work('live');
if (isset($testKeyResult) && !is_wp_error($testKeyResult) && $testKeyResult != false) {
    $test_keys_work = true;
} else {
    $test_keys_work = false;
}
if (isset($liveKeyResult) && !is_wp_error($liveKeyResult) && $liveKeyResult != false) {
    $live_keys_work = true;
} else {
    $live_keys_work = false;
}

$tot_is_production = tot_is_production();
$tot_connection_ok = $live_keys_work || $test_keys_work;

tot_add_educational_div($tot_connection_ok, $tot_keys, $test_keys_work, $live_keys_work, $tot_is_production);