<?php

$tot_supportedLocalhostPorts = '80,443,3000,3001,3443,7888,8000,8080,8888,32080,32443,33080';
$tot_supportedLocalhostPortsArray = explode(',', $tot_supportedLocalhostPorts);

function tot_isSupportedLocalhostPort($port) {
    global $tot_supportedLocalhostPortsArray;
	return in_array($port, $tot_supportedLocalhostPortsArray);
}

function tot_get_admin_access_token($force=false) {
	$admin_access_token = get_transient( 'admin_access_token' );

	if($force || $admin_access_token === false) {

		$admin_access_token = tot_set_connection('wp-admin');

		if ( is_wp_error( $admin_access_token ) ) {
			return $admin_access_token;
		} elseif ( tot_is_error( $admin_access_token ) ) {
			return tot_respond_to_error_with_link('tot_get_admin_access_token',
			    'An error occurred when attempting to connect using client credentials.', array(
				'response' =>  $admin_access_token
			));
		}

		set_transient( 'admin_access_token', $admin_access_token, DAY_IN_SECONDS * 7 );
	}

	return $admin_access_token;
}


function tot_set_connection($wpUserid=null, $appData=null, $appCallbackUrl=null, $returnRequest=false) {

    $appData = tot_get_user_app_data($wpUserid);
    if (!isset($wpUserid)) {
        $wpUserid = get_current_user_id();
    }
    $appUserid = tot_user_id($wpUserid);

    $requestUrl = tot_origin() . '/api/connection/' . $appUserid;
	$public_key = tot_get_public_key();
	$secret_key = tot_get_secret_key();

	if( is_wp_error($public_key) ) {
	    do_action('tot_set_connection_failed', $public_key);
		return $public_key;
	}elseif( is_wp_error($secret_key) ) {
	    do_action('tot_set_connection_failed', $secret_key);
		return $secret_key;
	}

	// check to make sure the user is not trying to run from an unsupported localhost port.
	$localhost = "localhost";
	$port = $_SERVER['SERVER_PORT'];
	$host = $_SERVER['HTTP_HOST'];
	if (substr($host, 0, strlen($localhost)) === $localhost && ($port && !tot_isSupportedLocalhostPort($port))) {
	    global $tot_supportedLocalhostPorts;
	    do_action('tot_set_connection_failed', new WP_Error('unsupported-localhost-port'));
		return tot_respond_to_error_with_link('tot_set_connection',
						'Unsupported localhost port. Please try again with a supported port.', array(
		    	        'supportedLocalhostPorts' => $tot_supportedLocalhostPorts
			        ));
	}

	$appDomain = $host;

	$requestDetails = array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type: application/json',
            'charset: utf-8'
        ),
        'body' => array(
            'totApiKey' => $public_key,
            'totSecretKey' => $secret_key,
            'appUserid' => $appUserid,
            'appDomain' => $appDomain
        ),
        'sslverify' => tot_ssl_verify(),
    );

    $requestDetails['body']['appData'] = apply_filters('tot_set_connection_app_data', $appData, $wpUserid);

	$access_token = wp_remote_post(
		$requestUrl,
		$requestDetails
	);

	if ( is_wp_error( $access_token ) ) {
        do_action('tot_set_connection_failed', $access_token, $requestDetails, $requestUrl);
		return tot_respond_to_error_with_link('tot_set_connection', 'There was an error setting the connection.', array(
            'request' => $requestDetails,
            'response' =>  $access_token
        ));

	}else {

		$decoded = json_decode($access_token["body"]);
		if( !isset($decoded->content->accessToken) || !$decoded->content->accessToken || (isset($decoded->content->type) && ($decoded->content->type === "error")) ) {
		    do_action('tot_set_connection_failed', $access_token, $requestDetails, $requestUrl);
			return tot_respond_to_error_with_link('tot_set_connection', 'There was an error setting the connection.', array(
				'request_url' => $requestUrl,
                'request' => $requestDetails,
                'response' =>  $access_token
            ), $decoded);
		}else {
		    do_action('tot_set_connection_success', $decoded, $requestDetails, $requestUrl);
		    if($returnRequest) {
		        return array(
		            'accessToken' => $decoded->content->accessToken,
		            'request' => $requestDetails
		        );
		    }else {
			    return $decoded->content->accessToken;
            }
		}

	}
}

/**
 * @param $wpUserid
 * @return $appData
 */
function tot_get_user_app_data($wpUserid)
{
    if (isset($wpUserid)) {
        $appUserid = tot_user_id($wpUserid);
        $user_info = get_userdata($wpUserid);

        if (isset($user_info->first_name)) {
            $givenName = $user_info->first_name;
        }
        if (isset($user_info->last_name)) {
            $familyName = $user_info->last_name;
        }
        if (isset($user_info->nickname)) {
            $nickname = $user_info->nickname;
        }
        if (isset($user_info->user_email)) {
            $email = $user_info->user_email;
        }
    } else {
        global $current_user;
        get_currentuserinfo();
        $wpUserid = get_current_user_id();
        $appUserid = tot_user_id($wpUserid);
        $givenName = $current_user->first_name;
        $familyName = $current_user->last_name;
        $nickname = $current_user->display_name;
        $email = $current_user->user_email;
    }

    $givenName = isset($givenName) ? $givenName : "n/a";
    $familyName = isset($familyName) ? $familyName : "n/a";

    $appData = array();
    $appData['givenName'] = array(
        'current' => $givenName
    );

    if (tot_get_option('tot_field_auto_identify')) {
        if (isset($email)) {
            $appData['email'] = $email;
        }
        $appData['familyName'] = array(
            'current' => $familyName
        );
    }

    return $appData;
}
