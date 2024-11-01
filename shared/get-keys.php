<?php

use TOT\tot_debugger;

function tot_get_keys()
{
    $keys = get_transient('tot_keys');
    $current_origin = site_url();

    if ( ( $keys === false ) || !isset($keys['test']) || !isset($keys['request_domain']) || !isset($keys['app_id']) || ($keys['request_domain'] !== $current_origin) ) {
		$appDomain = null;
        $keys = array();
        $keys['live'] = array();
        $keys['test'] = array();

        $keys['request_domain'] = $current_origin;

        /**
         * Test response
         */
        $test_response = tot_request_license_details(tot_test_origin());
        if (!is_wp_error($test_response) && !tot_is_error($test_response)) {
            $test_keys = json_decode($test_response);
			$appDomain = $test_keys->content->appDomain ?? $appDomain;
            $keys['test']['webhooks'] = isset($test_keys->content->options->webhooks) ? json_encode($test_keys->content->options->webhooks) : '';

            tot_debugger::inst()->log('Test webhooks settings: ', $keys['test']['webhooks']);
            if (isset($test_keys->content->apiKeys->test)) {
                $keys['public_test'] = $test_keys->content->apiKeys->test->apiKey;
                $keys['secret_test'] = $test_keys->content->apiKeys->test->secretKey;
                // We need to test the keys bc the license itself was fetched via the license keys.
                $keys['tot_test_request'] = tot_test_connection(tot_test_origin(), $keys['public_test'], $keys['secret_test'], $appDomain);
            }
        }

        /**
         * Live response
         */
        $live_response = tot_request_license_details(tot_production_origin());
        if (!is_wp_error($live_response) && !tot_is_error($live_response)) {
            $live_keys = json_decode($live_response);
			$appDomain = $live_keys->content->appDomain ?? $appDomain;

            $keys['live']['webhooks'] = isset($live_keys->content->options->webhooks) ? json_encode($live_keys->content->options->webhooks) : '';

            tot_debugger::inst()->log("Live webhooks settings: ", $keys['live']['webhooks']);
            if (isset($live_keys->content->apiKeys->live)) {
                $keys['public_live'] = $live_keys->content->apiKeys->live->apiKey;
                $keys['secret_live'] = $live_keys->content->apiKeys->live->secretKey;
                $keys['tot_live_request'] = tot_test_connection(tot_production_origin(), $keys['public_live'], $keys['secret_live'], $appDomain);
            }
        }

        if (isset($live_keys)) {
            $keys = array_merge($keys, parseCommonKeys($live_keys));
        } else if (isset($test_keys)) {
            $keys = array_merge($keys, parseCommonKeys($test_keys));
        } else {
            // none of them are working (test and live)
            // then return the error of the test response
            if (is_wp_error($test_response)) {
                return $test_response;
            } elseif (tot_is_error($test_response)) {
                return tot_respond_to_error_with_link('tot_license_api_error', 'An error occurred when connecting to the Token of Trust license API.', array(
                    'response' => $test_response
                ));
            }
        }

        // one of them is working
        if (isset($test_keys)) $raw_keys = $test_keys;
        else if (isset($live_keys)) $raw_keys = $live_keys;

        // check for enabled or disabled settings
        $options = $raw_keys->content->options ?? new stdClass();
        $excise_tax = isset($options->exciseTax->collection) ? options_with_mode($options->exciseTax->collection) : false;
        $keys['exciseTaxEnabled'] = $excise_tax && $excise_tax['mode'] == 'enabled';

        $options_to_check = [
            'wooCommerceBeta',
            'ecommerce',
            'emailUntilVerified',
            'realTimeIdVerificationEnabled',
            'vueOnboardingSteps',
            'vendorViewDocuments'
        ];

        foreach ($options_to_check as $option_name) {
            $option_mode = isset($options->$option_name) ? options_with_mode($options->$option_name) : false;
            $keys[$option_name] = $option_mode && $option_mode['mode'] == 'enabled';
        }

        $includedCountries = $raw_keys->content->options->realWorld->countries->countries ?? '';
        $keys['includedCountries'] = [];

        // make sure it's in valid format
        if (isset($includedCountries->base) && $includedCountries->base == 'none'
            && isset($includedCountries->additional) && is_array($includedCountries->additional)) {
            $keys['includedCountries']['base'] = $includedCountries->base;
            $keys['includedCountries']['additional'] = $includedCountries->additional;
        } else {
            $keys['includedCountries']['base'] = '';
            $keys['includedCountries']['additional'] = [];
        }

        // store errors for displaying later without breaking the cycle
        $keys['errors'] = [];

        if (is_wp_error($test_response)) {
            $keys['errors']["test"] = $test_response;
        } elseif (tot_is_error($test_response)) {
            $keys['errors']["test"] = tot_respond_to_error_with_link('tot_license_api_error', 'An error occurred when connecting to the Token of Trust license API.', array(
                'response' => $test_response
            ));
        }

        if (is_wp_error($live_response)) {
            $keys['errors']["live"] = $live_response;
        } elseif (tot_is_error($live_response)) {
            $keys['errors']["live"] = tot_respond_to_error_with_link('tot_license_api_error', 'An error occurred when connecting to the Token of Trust license API.', array(
                'response' => $live_response
            ));
        }

        // store keys
        set_transient('tot_keys', $keys, DAY_IN_SECONDS * 7);
        if ($appDomain) {
            TOT\Settings::set_setting('tot_field_prod_domain', $appDomain);
        }
    }
    return $keys;
}

/**
 * @param $keys
 * @return mixed
 */
function parseCommonKeys($keys)
{
    return [
        'app_id' => $keys->content->appId,
        'app_title' => $keys->content->appTitle,
        'freeTrialStartTimestamp' => $keys->content->accountSettings->freeTrialStartTimestamp ?? 0,
        'freeTrialEndTimestamp' => $keys->content->accountSettings->freeTrialEndTimestamp ?? 0,
        'goLiveTimestamp' => $keys->content->accountSettings->goLiveTimestamp ?? 0,
        'deactivationTimestamp' => $keys->content->accountSettings->deactivationTimestamp ?? 0,
        'verificationUseCase' => $keys->content->accountSettings->verificationUseCase ?? 'age'
    ];
}

/**
 * @param $app_option
 * @return false|['mode' => 'enabled']
 */
function options_with_mode ($app_option) {
    if (!$app_option) {
        return false;
    } else if (is_string($app_option))  {
        if ($app_option === 'true') {
            return ['mode' => 'enabled'];
        } elseif ($app_option === 'false') {
            return false;
        }
        return strpos($app_option, 'disable') === 0 ? false : ['mode' => 'enabled'];
    } else if (is_bool($app_option)) {
        return !$app_option ? false : ['mode' => 'enabled'];
    } else if (is_object($app_option) && !empty((array) $app_option)) {
        return (isset($app_option->mode) && (strpos($app_option->mode, 'disable') === 0  || !$app_option->mode) ) ? false :
            array_merge((array) $app_option, ['mode' => 'enabled']);
    } else if (is_array($app_option)) {
        return ! empty($app_option) ? $app_option : false;
    }
}

/**
 * @param string $key_type - 'live' or 'test'
 * @return bool
 */
function tot_keys_work($key_type = 'test')
{
    $tot_keys = tot_get_keys();

    if ($key_type == "live") {
        $request_type = 'tot_live_request';
    } else if ($key_type == "test") {
        $request_type = 'tot_test_request';
    }
    if (is_wp_error($tot_keys) || !isset($tot_keys) || !isset($tot_keys[$request_type])) {
        return false;
    }
    return $tot_keys[$request_type];
}

function tot_refresh_keys()
{
    delete_transient('tot_keys');
    $tot_keys = tot_get_keys();
    if (tot_debug_mode()) {
        tot_respond_to_error_with_link('refreshed_keys', 'Token of Trust response for refreshed keys.', array(
            'request_url' => "none",
            'request' => "none",
            'response' => $tot_keys
        ));
    }
    return $tot_keys;
}

function tot_request_license_details($origin = null)
{

    $options = get_option('tot_options');

    if (!isset($options)) {
        return tot_respond_to_error('tot_no_options', 'Token of Trust settings are missing.', array());
    }

    if(isset(get_option('tot_options')['tot_field_license_key'])) {
        $license_key = get_option('tot_options')['tot_field_license_key'];
    }
    if (!isset($license_key) || !$license_key) {
        return tot_respond_to_error('tot_no_license', 'Token of Trust license key is not set in plugin settings.', array());
    }

    $app_domain = tot_get_setting_prod_domain();

    $baseUrl = isset($origin) ? $origin : tot_origin();
    if(!$app_domain) {
        // This allows us to support fetching api details WITHOUT knowing the appDomain.
        $endpoint = '/api/apps';

        $request_details = array(
            'method' => 'GET',
            'headers' => array(
                'Content-Type: application/json',
                'charset: utf-8',
                'authorization: ' . $license_key
            ),
            'body' => array(
                'totLicenseKey' => $license_key
            )
        );
    } else {
        $endpoint = '/api/apps/' . $app_domain;
        $request_details = array(
            'method' => 'GET',
            'headers' => array(
                'Content-Type: application/json',
                'charset: utf-8',
                'authorization: ' . $license_key
            ),
            'body' => array(
                'totLicenseKey' => $license_key
            )
        );
    }

    return tot_curl_request($baseUrl, $endpoint, $request_details);
}



function tot_test_connection($baseUrl, $public_key, $secret_key, $appDomain) {
    $endpoint = '/api/accessToken';
    $request_details = array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type: application/json',
            'charset: utf-8'
        ),
        'body' => array(
            'totApiKey' => $public_key,
            'totSecretKey' => $secret_key,
            'appDomain' => $appDomain
        ),
        'sslverify' => tot_ssl_verify()
    );
    return tot_curl_request($baseUrl, $endpoint, $request_details);
}
/**
 * @param $baseUrl
 * @param $endpoint
 * @param array $request_details
 * @return bool|string|WP_Error
 */
function tot_curl_request($baseUrl, $endpoint, array $request_details, $logResults = false)
{
    $request_url = $baseUrl . $endpoint;

    // Using curl because of this issue https://core.trac.wordpress.org/ticket/37820
    $ch = curl_init();

    if ($request_details['method'] == 'POST' ) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_details['body']));
    } else {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		if (!empty($request_details['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_details['body']));
        }
	}

    if (isset($request_details['query'])) {
        $query = http_build_query($request_details['query']);
        // We're intentionally not polluting $requrest_url to keep errors and logging (below) clean.
        curl_setopt($ch, CURLOPT_URL, $request_url."?".$query);
    } else {
        curl_setopt($ch, CURLOPT_URL, $request_url);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $request_details['headers']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, tot_ssl_verify());
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds

    $output = curl_exec($ch);

    $is_ssl_configured = true;

    if ((curl_errno($ch) == 60)) { //if there's an SSL error

        $is_ssl_configured = false;

        if (class_exists('Requests') && method_exists('Requests', 'get_certificate_path')) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CAINFO, Requests::get_certificate_path());
            $output = curl_exec($ch);
            delete_option('tot_ssl_misconfiguration');
        } else {
            update_option('tot_ssl_misconfiguration', true, false);
        }

    } else {
        delete_option('tot_ssl_misconfiguration');
    }

    if (curl_errno($ch)) { //check for other CURL errors
        return tot_respond_to_error_with_link('tot_curl_request_connection_error:' . $endpoint, 'There was an error connecting to the Token of Trust API with : ' . $request_url, array(
            'request_url' => $request_url,
            'request' => $request_details,
            'response' => $output,
            'request_error' => curl_errno($ch) . ": " . curl_error($ch),
            'request_info' => curl_getinfo($ch)
        ));

    }

    curl_close($ch);

    if( tot_is_json( $output ) ) {
        $decoded = json_decode( $output );
        if( !$decoded || !$decoded->content || (isset($decoded->content->type) && ($decoded->content->type  === 'error')) ) {

            return tot_respond_to_error_with_link('tot_curl_request_error:' . $endpoint, 'Token of Trust api error with : ' . $request_url, array(
                'request_url' => $request_url,
                'request' => $request_details,
                'response' => $output
            ), $decoded);

        } elseif (!$is_ssl_configured) { //return a error if SSL in not configured and app in is production

            return tot_respond_to_error_with_link('tot_get_ssl_not_configured_error', 'SSL is not properly configured on Wordpress.', array(
                'request_url' => $request_url,
                'request' => $request_details,
                'response' =>
                    'SSL certificate library not found.
					Please download \'cacert.pem\' certificates from https://curl.haxx.se/docs/caextract.html
					Upload the file to your host.
					Add the following line to your php.ini server file: curl.cainfo = "path_to_cert\cacert.pem".'
            ));

        } else {
            if ($logResults) {
                return tot_respond_to_error_with_link('tot_curl_request_results' . $endpoint, 'Token of Trust response for'.$request_url, array(
                    'request_url' => $request_url,
                    'request' => $request_details,
                    'response' => $output
                ));
            }
            return $output;
        }

    } else {
        return tot_respond_to_error_with_link('tot_curl_request_json_error:' . $endpoint, 'Token of Trust invalid json response from : ' . $request_url, array(
            'request_url' => $request_url,
            'request' => $request_details,
            'response' => $output
        ));
    }
}

function tot_live_or_in_trial() {
    $keys = tot_get_keys();
    if (is_wp_error($keys) || !is_array($keys)) {
        return false;
    }

    $now = time();

    $deactivationTimestamp = isset($keys['deactivationTimestamp']) ? floor($keys['deactivationTimestamp'] / 1000) : 0;
    $goLiveTimestamp = isset($keys['goLiveTimestamp']) ? floor($keys['goLiveTimestamp'] / 1000) : 0;

    // The system is considered live if either:
    // 1) goLiveTimestamp > deactivationTimestamp (accounting for reactivation)
    // 2) goLiveTimestamp > 0 and deactivationTimestamp > goLiveTimestamp, but current time is still less than deactivationTimestamp
    $is_live = ($goLiveTimestamp > $deactivationTimestamp) ||
        ($goLiveTimestamp > 0 && $deactivationTimestamp > $goLiveTimestamp && $now < $deactivationTimestamp);

    $freeTrialEndTimestamp = isset($keys['freeTrialEndTimestamp']) ? floor($keys['freeTrialEndTimestamp'] / 1000) : 0;
    $is_in_trial = ($freeTrialEndTimestamp - $now) > 0;

    return $is_live || $is_in_trial;
}

function tot_live_mode_available() {
    $tot_keys_work = tot_keys_work('live');
    return !is_wp_error($tot_keys_work) && $tot_keys_work;
}
function tot_test_keys(){
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
    return $live_keys_work || $test_keys_work;
}

function tot_get_public_key () {
    $is_production = tot_is_production();

    $keys = tot_get_keys();

    if( !is_wp_error($is_production) && !is_wp_error( $keys ) && $is_production ) {

        if (isset($keys['public_live'])) {
            return $keys['public_live'];
        } elseif (isset($keys['errors']['live'])) {
            // return stored error
            return $keys['errors']['live'];
        } else {
            return new WP_Error('tot_no_public_live', 'Token of Trust public_live is missing.');
        }

    }elseif( !is_wp_error($is_production) && !is_wp_error( $keys ) ) {

        if (isset($keys['public_test'])) {
            return $keys['public_test'];
        } elseif (isset($keys['errors']['test'])) {
            // return stored error
            return $keys['errors']['test'];
        } else {
            return new WP_Error('tot_no_public_test', 'Token of Trust public_test is missing.');
        }

    }else {
        return $keys;
    }
}

function tot_get_app_id () {
    $keys = tot_get_keys();
    return (!empty($keys) && !is_wp_error($keys)) ? $keys['app_id'] : null;
}

function tot_get_secret_key () {

    $is_production = tot_is_production();
    $keys = tot_get_keys();

    if( !is_wp_error($is_production) && !is_wp_error( $keys ) && $is_production ) {

        return $keys['secret_live'];

    } elseif( !is_wp_error($is_production) && !is_wp_error( $keys ) ) {

        return $keys['secret_test'];

    }else {
        return $keys;
    }
}

function is_tot_connection_ok () {
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
    return $live_keys_work || $test_keys_work;
}
