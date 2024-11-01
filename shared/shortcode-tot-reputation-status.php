<?php

use TOT\Reasons;
use TOT\API_Request;
use TOT\Integrations\WooCommerce\Checkout;

add_shortcode('tot-reputation-status', 'tot_reputation_status_shortcode');

/**
 * Provides short contextual text snippets to describe the current state of the user.
 * @param $attrs
 * @param null $content
 * @return string
 */
function tot_reputation_status_shortcode($attrs, $content = null)
{
	if (!tot_live_or_in_trial()) {
		return '';
	}
    global $tot_plugin_text_domain;
    global $post;
	$tot_debugger = \TOT\tot_debugger::inst();
	$tot_debugger->register_new_operation(__FUNCTION__);

    // normalize attribute keys, lowercase
    if (!empty($attrs)) {
        $attrs = array_change_key_case($attrs, CASE_LOWER);
    }

    $settings = shortcode_atts(

    // IMPORTANT - this is written ONLY for account based verification with the current user.
        array(
            'app-userid' => '',
            'auto-launch-when-not-verified' => '',
            'show-get-verified-button' => 'false'
        ),
        $attrs
    );

    $current_user = wp_get_current_user(); // Need the current_user object to extract out some info we'll need later.
    $currentUserID = get_current_user_id(); // Also we need the current_user's ID so that we can create an appUserid with it.
    $appUserid = tot_user_id($currentUserID, null, false);
    $settings['app-userid'] = $appUserid;

    // resolve the appTransactionId
    $appTransactionId = null;
    if ( isset($settings['order-id']) && class_exists( 'WooCommerce' ) ) {
        // I have an order-id defined in the settings, and you have WooCommerce in this Wordpress environment.
        $order_id = Checkout::get_current_wc_order_id($settings['order-id']);
        $order = tot_wc_get_order($order_id);
    } else {
        $order = null;
    }

    $current_user = wp_get_current_user();
    if (isset($current_user)) {
        $givenName = $current_user->first_name;
        $familyName = $current_user->last_name;
    }

    $reputation = null;
    if (!empty($order) && !is_wp_error($order)) {
        $tot_debugger->add_part_to_operation('','Saw order.', $order->get_id());
        // If we're within the context of an order then we ALWAYS use the transaction id to find the reputation.
        $appTransactionId = $settings['tot-transaction-id'] ? $settings['tot-transaction-id'] : $order->get_meta('tot_transaction_id', true );
        $settings['tot-transaction-id'] = $appTransactionId;
        if (!empty($appTransactionId)) {
            $reputation = tot_get_order_reputation($appTransactionId);
        }
    } else if (!empty($appUserid) && !is_wp_error($appUserid)) {
        $reputation = tot_get_user_reputation($appUserid);
    } else if (isset($current_user) && isset($current_user->user_email)) {
        $reputation = tot_get_user_reputation($current_user->user_email);
    }

    // Must either be an existing user or part of a TOT checkout.
    if ((empty($appTransactionId) || is_wp_error($appTransactionId)) && (empty($appUserid) || is_wp_error($appUserid))) {
        $tot_debugger->add_part_to_operation('','Neither user nor order found.', '');
        $msg = "
        <div class='tot-alert-error'>
            <div class='tot-alert-error-h'>ERROR: Your Setup Is Incomplete</div>

            <br>

            The setup for the verification process is incomplete.

            <br><br>

            <b>Users must be logged into an account on this site to view the verification prompt on this page.</b>

            <br><br>

            <b>Steps to Fix This Issue:</b>
            <ol>
                <li>
                    <b>Ensure User Login Requirement:</b>
                    Make sure users are required to log in before they can proceed with verification. This is a necessary step for the Token of Trust plugin to function correctly.
                </li>
                <li><b>Review Documentation:</b> Refer to the
                    <a target='_blank' href='https://help.tokenoftrust.com/article/68-how-do-i-add-verification-for-account-based-wordpress-and-woocommerce-sites'>
                        Token of Trust Support Docs
                    </a>
                    for detailed instructions on correct account-based setup.
                </li>
                <li><b>Contact the site administrator:</b> If the site does not support account login, report this error to them for further assistance.
            Thank you for your understanding and cooperation.</li>
            </ol>
        </div>
        ";
        return apply_filters('tot_verification_gates_request_signin_block', "$msg");
    }

    if (isset($reputation) && !is_wp_error($reputation) && isset($reputation->gates) ) {
        $reasons = $reputation->reasons;
        $gates = $reputation->gates;
    } else {
        $reasons = null;
        $gates = null;
    }

    if (is_wp_error($reasons)) {
        return apply_filters('tot_verification_gates_error_block', "<p>There was a problem confirming your identity - please check back later.</p>");
    } else {
        $reasons = new Reasons($reasons);
        $gates = new Reasons($gates);
    }

    $hasReasons = isset($reasons);
    $hasGates = isset($gates);

    $govtIdPositiveReview = $hasReasons && $reasons->is_positive('govtIdPositiveReview'); // This one is verified by ToT
    $govtIdPositiveAppReview = $hasReasons && $reasons->is_positive('$govtIdPositiveAppReview'); // This one was approved by the vendor
    $govtIdPendingReview  = $hasReasons && $reasons->is_positive('govtIdPendingReview'); // This one is currently pending verification, it may or may not be approved by the vendor.

    $isSubmitted = $hasGates && $gates->is_positive('isSubmitted'); // The request has all documents, but has not been either verified or approved.
    $isRejected = $hasGates && $gates->is_positive('isRejected'); // This one has been explicitly rejected.
    $isCleared = $hasGates && $gates->is_positive('isCleared'); // This one is good to go (either approved or verified)

    if ($govtIdPositiveReview) {
        $tot_debugger->add_part_to_operation('','tot_verification_gates_approved_block', $reasons);
        return apply_filters('tot_verification_gates_approved_block', "<p>Thank you - you've been verified!</p>");
    } else if ($govtIdPositiveAppReview) {
        $tot_debugger->add_part_to_operation('','tot_verification_gates_approved_block', $reasons);
        return apply_filters('tot_verification_gates_approved_block', "<p>Thank you - you've been approved!</p>");
    } else if ($isSubmitted) {
        $tot_debugger->add_part_to_operation('','tot_verification_gates_pending_block', $gates);
        return apply_filters('tot_verification_gates_pending_block', "<p>Thank you - your verification has been submitted! Watch your email inbox. We will get that approved as quickly as we're able to!</p>");
    } else if ($isCleared) {
        $tot_debugger->add_part_to_operation('','tot_verification_gates_approved_block', $gates);
        return apply_filters('tot_verification_gates_approved_block', "<p>Thank you - you've been cleared!</p>");
    } else if ($isRejected) {
        $tot_debugger->add_part_to_operation('','tot_verification_gates_rejected_block', $gates);
        return apply_filters('tot_verification_gates_rejected_block', "<p>You have not passed verification. Please contact us for more information.</p>");
    }

    $cleared = $hasGates && $gates->is_positive('isCleared');
    $pendingReview = $hasGates && $gates->is_positive('isSubmitted');
    $rejected = $hasGates && $gates->is_positive('isRejected');
    $not_verified = !($pendingReview || $rejected || $cleared);

    if ($not_verified) {

        $tot_debugger->add_part_to_operation('','tot_verification_gates_not_verified_block', $reasons);

        $verify_person_data = array();
        $verify = null;


        // Okay, let's set up an App User ID from the current user's email address (as saved in WP)
        if (isset($current_user) && isset($current_user->user_email)) {
            $appData = tot_get_user_app_data($current_user);
            $verify_person_data['appUserid'] = $appUserid;
            $verify_person_data['person'] = $appData;
            $verify_person_data['person']['primaryEmail'] = $current_user->user_email; // Email will be sent as a secondary datapoint.
        }

        if (isset($givenName)) {
            $verify_person_data['person']['givenName'] = $givenName;
        }
        if (isset($familyName)) {
            $verify_person_data['person']['familyName'] = $familyName;
        }

        // Append the reservation if we have one.
        $totReservationToken = tot_get_time_based_cookie('totReservationToken');
        $error_callback = array('\TOT\API_Person', 'handle_verify_person_api_error');
        if (!empty($totReservationToken)) {
            $verify_person_data['appReservationToken'] = $totReservationToken;
        }
        $verify = new API_Request('api/person', $verify_person_data, 'POST');
        $verify->verify_result = $verify->send();
        if (isset($verify) && isset($verify->verify_result) && isset($verify->verify_result->continuation) && isset($verify->verify_result->continuation->modalType) && isset($verify->verify_result->continuation->params)) {
            echo '<script>
                var totModalType = "'.$verify->verify_result->continuation->modalType.'";
                var totModalParams = '.json_encode($verify->verify_result->continuation->params).';
                window.totModalType = totModalType;
                window.totModalParams = totModalParams;
                tot("bind", "modalClose", function (evt) {
                    window.location.reload();
                });
            </script>';
        }

        $autolaunchwhennotverified = $settings['auto-launch-when-not-verified'];
        $autoLaunchModal = '' !== $autolaunchwhennotverified && $autolaunchwhennotverified !== 'false';
        $settingsData = '';
        if ($autoLaunchModal) {
            $settingsData .= ' data-tot-auto-open-modal="true" ';
        }

        // Add a div with id = tot-auto-launch-modal to the page to automatically launch the modal.
        return apply_filters("tot_verification_gates_not_verified_block",
            "<div class='tot-wc-order-validation' $settingsData>"
            . '<a data-tot-verification-required="true" href="#tot_get_verified">'
            . __('Verification', $tot_plugin_text_domain)
            . '</a> '
            . __(' is required before you can proceed.', 'token-of-trust') . '</div>');
    }

	$tot_debugger->log_operation(__FUNCTION__);
}
