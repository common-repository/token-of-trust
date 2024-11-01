<?php

//add_action('template_redirect', 'tot_check_email_confirm');
//add_action('user_register', 'tot_registration_create', 10, 1);


function tot_registration_create($wp_user_id) {

    if (!tot_get_option('tot_field_confirm_new_user_emails')) {

        return;
    }

    $appUserid = tot_user_id($wp_user_id);
    $user_data = get_userdata($wp_user_id);
    $csrf = tot_get_user_confirmation_token($wp_user_id);
    $apiCallBackQuery = array('user_id' => $wp_user_id, 'csrf' => $csrf);
    $locale = 'en-US,en;q=0.9';
    if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $locale = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }

    $request = tot_api_call(
        'POST',
        '/api/invites/',
        $locale,
        array(
            'appUserid' => $appUserid,
            'inviteOptions' => array(
                "type"=> 'confirmEmail',
                "recipientEmail"=> $user_data->user_email,
                "recipientName"=> $user_data->first_name ? $user_data->first_name : get_bloginfo($show = 'name'),

            ),
            'appCallbackUrl' => get_site_url() . '/token-of-trust/email/confirm/?' . http_build_query($apiCallBackQuery),
        )
    //, tot_default_successful_mock_response()
    );

    if (is_wp_error($request['error'])) {

        if (!isset($request['response']['response']['code']) || ($request['response']['response']['code'] == 400)) {
            update_user_meta(
                $wp_user_id,
                'tot_email_verification',
                json_encode(
                    array(
                        'status' => 'failed_initial_check',
                        'last_updated' => time()
                    )
                )
            );
        }

        tot_display_error($request['error']);

    } else {


        update_user_meta(
            $wp_user_id,
            'tot_email_verification',
            json_encode(
                array(
                    'status' => 'email_sent',
                    'last_updated' => time()
                )
            )
        );


    }

}

function tot_check_email_confirm() {

    global $wp;
    if ($wp->request == 'token-of-trust/email/confirm') {

        $wp_user_id = $_GET['user_id'];
        $csrf = $_GET['csrf'];
        $stored_csrf = get_user_meta($wp_user_id, 'tot_confirmation_token', true);

        if (isset($stored_csrf) && ($stored_csrf !== '')) {
            if ($stored_csrf == $csrf) {

                update_user_meta(
                    $wp_user_id,
                    'tot_email_verification',
                    json_encode(
                        array(
                            'status' => 'email_confirmed',
                            'last_updated' => time()
                        )
                    )
                );

                $redirect = tot_get_option('tot_field_confirm_email_success_redirect');
                if ($redirect) {
                    wp_redirect(home_url($redirect));
                } else {
                    wp_redirect(home_url('/'));
                }
                exit();

            }
        }
    }

}
