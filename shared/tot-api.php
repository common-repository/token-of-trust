<?php

use TOT\tot_debugger;
use TOT\Settings;

require 'tot-hosts.php';

function tot_is_production() {

	$result = false;

    if(isset($_COOKIE['totForceTestMode'])) {
	    $result = false;
    }else {
        $result = !is_wp_error(tot_keys_work('live')) && tot_keys_work('live');
    }

    return apply_filters('tot_is_production', $result);
}

function tot_is_dev_env() {
	$test_domain = 'test.tokenoftrust.com';
	return strpos(tot_production_origin(), $test_domain) !== false
		|| strpos(tot_test_origin(), $test_domain) !== false;
}

function tot_get_current_origin()
{
    $port = $_SERVER['SERVER_PORT'];
    $host = $_SERVER['HTTP_HOST'];
    $domain = ($port == '80') || ($port == '443') ? $host : $host . ':' . $port;
    $protocol = isset($_SERVER['HTTPS']) && (strtoupper($_SERVER['HTTPS']) == 'ON') ? 'https' : 'http';

    return $protocol . '://' . $domain;
}

function tot_user_id($wp_user_id = NULL, $order = NULL, $auto_create_guid_for_user = true)
{
    $id = !empty($wp_user_id) ? $wp_user_id : get_current_user_id();

    if (!empty($id)) {
        // We can look up (and store) on the user object.

        // Try with traditional guid.
        $tot_appuserid_as_guid = tot_get_stored_appuserid_as_guid($id);
        if (!empty($tot_appuserid_as_guid)) {
            return $tot_appuserid_as_guid;
        }

        // Try with newer email hash.
        $tot_appuserid_as_hash = tot_get_stored_appuserid_as_hash($id);
        if (!empty($tot_appuserid_as_hash)) {
            return $tot_appuserid_as_hash;
        }
    }

    // 1. Try creating with an email hash.
    $tot_appuserid_as_hash = tot_create_appuserid_from_email($wp_user_id, $order);
    if (isset($tot_appuserid_as_hash)) {
        if (!empty($id)) {
            $database_key = 'tot_email_hash';
            add_user_meta(
                $id,
                $database_key,
                $tot_appuserid_as_hash,
                true
            );
        }
        return $tot_appuserid_as_hash;
    }

    if ($auto_create_guid_for_user && !empty($id)) {
        // 2. If email hash fails - use a guid.
        $guid = tot_create_guid();
        $database_key = 'tot_guid';
        add_user_meta(
            $id,
            $database_key,
            $guid,
            true
        );
        return $guid;
    }

    return null;
}

function tot_get_stored_appuserid_as_guid($user_id = NULL)
{
    return tot_get_user_attr('tot_guid', $user_id);
}

function tot_get_stored_appuserid_as_hash($user_id = NULL)
{
    return tot_get_user_attr('tot_email_hash', $user_id);
}

function tot_get_user_attr($database_key, $user_id = NULL)
{
    $id = isset($user_id) && $user_id !== NULL ? $user_id : get_current_user_id();
    return get_user_meta(
        $id,
        $database_key,
        true
    );
}


function tot_production_website()
{
    return 'https://tokenoftrust.com';
}

function tot_is_error( $response_object ) {

	if(!isset($response_object) || !isset($response_object['body'])) {
		return false;
	}
	$decoded = json_decode($response_object['body']);

	if( $decoded->content->type === 'error' ) {
		return true;
	}else {
		return false;
	}

}

function tot_get_setting_prod_domain() {
    $options = get_option('tot_options');

    //Remove headers(https://, www., etc.) from domains names
    if( isset($options) && $options && is_array($options) && array_key_exists('tot_field_prod_domain', $options)) {
        return tot_scrub_prod_domain($options['tot_field_prod_domain']);
    }
}

function tot_remove_registered_license()
{
    $options = get_option('tot_options');
    unset($options['tot_field_prod_domain'], $options['tot_field_license_key']);
    update_option('tot_options', $options);
}

/*
 * Replaces all instances of unnecessary domain name headers.
 */
function tot_scrub_prod_domain($domain){
    if(!empty($domain) && isset($domain)) {
        $domain_check_array = array("https://", "http://", "www.", "https:/", "https//", "https:", "http:/", "http//", "http:");
        foreach ($domain_check_array as $value) {
            if (strpos($domain, $value) !== false) {
                $domain = str_replace($value, "", $domain);
            }
        }
        return trim($domain);
    }
}

function tot_normalize_url_path( $url ) {
    $url = strtolower($url);
    $url = preg_replace('/\s*/', '', $url);
    if (preg_match('/^https?:\/\//', $url)) {
        $url = preg_replace('/^https?:\/\//', '', $url);
        $pos = strpos($url,'/');
        if ($pos !== false) {
            $url = substr_replace($url,'',0, $pos);
        }
    }
    $url = preg_replace('/\/$/', '', $url);
    $url = preg_replace('/^\/*/', '', $url);
    $url = strtolower($url);
    return $url;
}

function tot_get_setting_license_key() {
    $options = get_option('tot_options');

    if( isset( $options ) ) {
        if(isset($options['tot_field_license_key'])) {
            $app_license = $options['tot_field_license_key'];
            return trim($app_license);
        }
    }
}

function tot_respond_to_error( $error_key, $error_description, $error_details ) {
	tot_debugger::inst()->log($error_description, $error_details, 'error');
	return new WP_Error( $error_key, $error_description, $error_details );
}

function tot_display_error($error)
{
    if (tot_debug_mode()) {
        tot_always_display_error($error);
    }
}

function tot_error_text($error)
{
    $error_codes = $error->get_error_codes();
    $html = '';
    foreach ($error_codes as $code) {
        $html .= $error->get_error_message($code) . ' ';
        if ($error->get_error_data($code)) {
            $html .= $error->get_error_data($code) . ' ';
        }
    }
    return $html;
}

function tot_always_display_error($error)
{
    if (is_array($error) && isset($error['errors']) && !empty($error['errors'])) {
        foreach ($error['errors'] as $err) tot_always_display_error($err);
        return;
    }

	if(!method_exists($error, 'get_error_codes')) {
		return;
	}
    $error_codes = $error->get_error_codes();
    echo '<pre>';
    foreach ($error_codes as $code) {
        echo '----------' . "\n";
        echo $error->get_error_message($code) . "\n";
        if ($error->get_error_data($code)) {
            echo "\n";
            print_r($error->get_error_data($code));
            echo "\n";
        }
        echo '----------' . "\n";
    }
    echo '</pre>';
}

function tot_is_json($string)
{
    if (is_string($string)) {
        return is_array(json_decode($string, true)) ? true : false;
    }
    return false;
}

function tot_create_guid()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((int)((double)microtime() * 10000));//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return $uuid;
    }
}

function tot_create_appuserid_from_email($wp_user_id = null, $order = null)
{
    $app_id = tot_get_app_id();
    $user_data = get_userdata($wp_user_id);

    $has_user_email = !empty($user_data) && !empty($user_data->user_email);
    if ($has_user_email) {
        $email_to_hash = $user_data->user_email;
    } else if (is_object($order)) {
        $email_to_hash = $order->get_billing_email();
    } else {
        $email_to_hash = $order['billing_email'] ?? null;
    }

    return tot_generate_app_userid_from_email_and_appid($email_to_hash, $app_id);
}

/**
 * @param $email
 * @param $app_id
 * @return string
 */
function tot_generate_app_userid_from_email_and_appid($email, $app_id)
{
    if (empty($app_id) || empty($email)) {
        return NULL;
    }

    $hyphen = chr(45); // "-"
    $appUseridPreHash = strtolower($email) . $hyphen . $app_id;
    $app_userid = hash('sha3-256', $appUseridPreHash);
    return $app_userid;
}

function tot_default_successful_mock_response()
{
    return array(
        'headers' => (object)array(),
        'body' => json_encode('{}'),
        'response' => array(
            'code' => 200,
            'message' => 'Ok'
        )
    );
}

function tot_api_call($method, $endpoint, $user_locale, $data, $mock_response = null)
{
    $requestUrl = tot_origin() . $endpoint;
    $public_key = tot_get_public_key();
    $secret_key = tot_get_secret_key();

    if (is_wp_error($public_key)) {
        return array('error' => $public_key);
    } elseif (is_wp_error($secret_key)) {
        return array('error' => $secret_key);
    }

    // check to make sure the user is not trying to run from an unsupported localhost port.
    $localhost = "localhost";
    $port = $_SERVER['SERVER_PORT'];
    $host = $_SERVER['HTTP_HOST'];

    if (substr($host, 0, strlen($localhost)) === $localhost && ($port && !tot_isSupportedLocalhostPort($port))) {
        global $tot_supportedLocalhostPorts;
        return array('error' => tot_respond_to_error_with_link(
            'tot_set_connection',
            'Unsupported localhost port. Please try again with a supported port.', array(
            'supportedLocalhostPorts' => $tot_supportedLocalhostPorts
        )));
    }

    $appDomain = tot_get_option('tot_field_prod_domain');
    $request_data = array_merge(array(
        'totApiKey' => $public_key,
        'totSecretKey' => $secret_key,
        'appDomain' => $appDomain
    ), $data);

    $requestDetails = array(
        'method' => $method,
        'headers' => array(
            'charset' => 'utf-8',
            'Accept-Language' => $user_locale),
        'body' => $request_data
    );
	$response_content = null;
    if (isset($mock_response)) {

        $response = $mock_response;
        $response_content = json_decode($mock_response['body']);

    } else {

        $response = wp_remote_post(
            $requestUrl,
            $requestDetails
        );

        if (is_wp_error($response)) {

            $error = tot_respond_to_error_with_link('tot_api_error', 'There was an error connecting to the Token of Trust API.', array(
                'request_url' => $requestUrl,
                'request' => $requestDetails,
                'response' => $response
            ));

        } else {

            $decoded = json_decode($response["body"]);

            if (!$decoded || !$decoded->content || (isset($decoded->content->type) && ($decoded->content->type === 'error')) || ($response['response']['code'] == 404)) {
                $error = tot_respond_to_error_with_link('tot_api_error_decode', 'The Token of Trust API responded with an error.', array(
                    'request_url' => $requestUrl,
                    'request' => $requestDetails,
                    'response' => $response
                ), $decoded);
            } else {

                $response_content = $decoded->content;
            }
        }
    }
    if(!isset($error)) $error = "No error";


    return array(
        'error' => $error,
        'request_details' => $requestDetails,
        'response' => $response,
        'response_content' => $response_content
    );

}

/**
* Getting the same transaction_id of The order
* before and after it has been placed
*
* @param WC_Order|int|false $order
* @return String
*/
function tot_get_transaction_id($order = false){
    $transaction_id = null;

    $wc = \WC();
    $order = tot_wc_get_order($order);
    $session_transaction_id = $wc->session->get('_tot_transaction_id');

    // the order has been placed
    if ( $order ){

        // if it exists in session and not in the DB then Move it from session to DB
        if (!is_null($session_transaction_id) &&
                ! ($order->get_meta('tot_transaction_id', true)) ) {

            tot_store_transaction_id($order);

        } else if (is_null($session_transaction_id) &&
                !($order->get_meta('tot_transaction_id', true)) ){
            // this condition shouldn't be met which is
            // order has been placed but the id neither in session nor DB
            $order->update_meta_data('tot_transaction_id', $order->get_id());
            $order->save();
        }

        $transaction_id = $order->get_meta('tot_transaction_id', true);

    } else if($session_transaction_id) {
        // The order hasn't been placed but we have the id in session
        $transaction_id = $session_transaction_id;

    } else {
        // Generate transaction id because It doesn't exist before
        // $transaction_id = tot_create_guid();

	    // the transaction id is null before placing the order
	    $transaction_id = null;
        $wc->session->set('_tot_transaction_id', $transaction_id);
    }

    return $transaction_id;
}

/**
 * Store transaction id in database
 * @param WC_Order|int|false $order
 */
function tot_store_transaction_id($order) {

    if(!($order = tot_wc_get_order($order))){
        return;
    }
    $wc = \WC();

    // getting transaction from session so don't pass $order_id
	//    $transaction_id = tot_get_transaction_id();

	// new Orders will have orderId as transactionId
    $order->update_meta_data('tot_transaction_id', $order->get_id());
    $order->save();
    $wc->session->set('_tot_transaction_id', null);
}


/**
 * Getting the same cart hash before and after the order has been placed
 * @param WC_Order|int|false $order
 * @return string
 */
function tot_get_cart_hash($order = false){
    $cartHash = null;

    $wc = \WC();
    $order = tot_wc_get_order($order);
    
    // order has been placed && cart is empty
    if ($order && !$wc->cart->get_cart_contents_count()){
        $cartHash = tot_generate_order_cartHash($order);
    } else if($wc->cart->get_cart_contents_count()) {
        // cart has items
        $cartHash = $wc->session->get('_tot_cart_hash') ?
                $wc->session->get('_tot_cart_hash') : tot_generate_cart_hash();
    } else if($wc->session->get('_tot_cart_hash')){
        // this will met when the cart is empty but the order still not placed
        // which happen during the final stage of checkout process
        $cartHash = $wc->session->get('_tot_cart_hash');
    }

    return $cartHash;
}
/**
 * This function will replace the current cart hash with new
 */
function tot_generate_cart_hash(){
    $wc = \WC();
    if(!$wc->cart->get_cart_contents_count()){
        return null;
    }
    $cartHash = $wc->cart->get_cart_hash(). time();
    $wc->session->set('_tot_cart_hash', $cartHash);

    return $cartHash;
}

/**
 * if the order doesn't have cartHash
 * this function will generate and store one for it
 * @param WC_Order|int|false $order
 * @return string
 */
function tot_generate_order_cartHash($order){
	if (!($order = tot_wc_get_order($order))) {
		return;
	}

	if ($traceId = $order->get_meta('_tot_cart_hash', true)) {
		return $traceId;
	}

	// generate trace id
	$traceId = $order->get_cart_hash(). time();
	$order->update_meta_data('_tot_traceid', $traceId);
	$order->update_meta_data('_tot_cart_hash', $traceId);
    $order->save();

	return $traceId;
}

/**
 * Store cart hash in database
 * @param WC_Order|int|false $order
 */
function tot_store_cart_hash($order){
    $order = tot_wc_get_order($order);
    if (!$order || $order->get_meta('_tot_cart_hash', true)){
        return;
    }

    // get it from session so don't pass order_id
    $cartHash = tot_get_cart_hash();
    $order->update_meta_data('_tot_cart_hash', $cartHash);
    $order->save();

    $wc = \WC();
    $wc->session->set('_tot_cart_hash', null);
}

function tot_get_traceid($order = false, $user_id = false){
    $traceId = null;

    $wc = \WC();
    $order = tot_wc_get_order($order);

    if ($order){

        $traceId = $order->get_meta('_tot_traceid', true);

        if ($traceId){
            return $traceId;
        }

        if (!$user_id){
            $user_id = $order->get_user_id();
        }
    }

    // try to get cart hash
    if ($cartHash = tot_get_cart_hash($order)) {
        // Ideally, I want to get a woocommerce hash for your shopping cart.
        $traceId = $cartHash;
    } else if ( $user_id ) {
        // If not, do you have an order with a user id?
        $traceId = "USERID-".$user_id;
    } else if ($transaction_id = tot_get_transaction_id($order)) {
        // If not, do you have a transaction ID?
        $traceId = "TXID-" . $transaction_id;
    } else {
        // If we couldn't get a cart has, we're just gonna send an empty string.
        $traceId = "";
    }

    return $traceId;
}

/**
 * Store traceid in database
 * @param WC_Order|int|false $order
 */
function tot_store_traceid($order = false){
    $order = tot_wc_get_order($order);
    if (!$order || $order->get_meta('_tot_traceid', true)){
        return;
    }

    // get it from session so don't pass order_id
    $traceid = tot_get_traceid();
    $order->update_meta_data('_tot_traceid', $traceid);
    $order->save();
}

/**
 * @return bool
 */
function tot_user_has_wholesale_role($user_id = null, $order = null) {

	if (!is_user_logged_in()) {
		return false;
	}

	if ($order) {
        $order = tot_wc_get_order($order);
		return $order->get_meta('_tot_ordertype', true) == 'wholesale';
	}

    if ($user_id) {
        $user = get_user_by('id', $user_id);
    } else if ($customer = WC()->customer) {
        $user = get_user_by('id', $customer->get_id());
    } else {
        $user = wp_get_current_user();
    }

    if (!is_object($user) || !isset($user->roles) || !is_array($user->roles)) {
        return false;
    }

    $user_roles = array_map(function ($role) {
        return strtolower(wp_roles()->role_names[$role]);
    }, $user->roles);

	$wholesale_roles = Settings::get_setting('tot_field_roles_as_wholesalers');

	$userHasWholesaleRole = false;
	if(!empty($wholesale_roles) && !empty($user_roles) && !empty(array_intersect($wholesale_roles, $user_roles))){
		$userHasWholesaleRole = true;
	}

	return $userHasWholesaleRole;
}

function tot_store_orderType($order) {
    $order = tot_wc_get_order($order);
	if (!$order || $order->get_meta('_tot_ordertype', true)) {
		return;
	}

	$orderType = tot_user_has_wholesale_role($order->get_customer_id()) ? 'wholesale' : 'retail';

    $order->update_meta_data('_tot_ordertype', $orderType);
    $order->save();
}
