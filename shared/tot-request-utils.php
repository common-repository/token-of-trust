<?php

use TOT\Settings;
global $tot_plugin_text_domain;

add_action('widgets_init', 'tot_widgets_init', 9);
add_action('wp_loaded', 'tot_wp_loaded', 9);

function tot_widgets_init()
{
    // check for COOKIE related updates.
    tot_add_query_params('ctp_order_key');
    tot_add_query_params('debug_mode');
}

function tot_wp_loaded(){
    // Currently we don't allow this to be overridden.
    //    tot_check_for_query_cookie('debug_mode');
}

function tot_add_query_params_closure($query_var) {
    return function() use ($query_var){
        tot_add_query_params($query_var);
    };
}

function tot_add_query_params($query_var) {
        global $wp;
        $query_var = Settings::stripTotFieldKey($query_var);
        $wp->add_query_var($query_var);
        // tot_log_in_debug_log('TOT: added query param', $query_var);
}

function tot_set_time_based_cookie($cookie, $paramValue, $time = 60*30) {
    $cookie = Settings::stripTotFieldKey($cookie);
    if (isset($paramValue)) {
        $expires = time() + $time;
        $success = setcookie($cookie, $paramValue, $expires, '/');
//        if (empty($success)) {
//            tot_log_in_debug_log('Problem trying to setcookie - per docs this means output exists prior to calling this function: ', array(
//                'cookie' => $cookie,
//                'value' => $paramValue,
//                'expires' => $expires
//            ));
//        } else {
//            tot_log_in_debug_log('Successfully set cookie: ', array(
//                'cookie' => $cookie,
//                'value' => $paramValue,
//                'expires' => $expires
//                ));
//        }
        // echo "$cookie = $paramValue";
    }
}
function tot_get_time_based_cookie($cookie) {
    $cookie = Settings::stripTotFieldKey($cookie);
    $cookieValue = Settings::get_param_or_cookie($cookie);
    if (!empty($cookieValue)) {
//        tot_log_in_debug_log('Found cookie: ', array(
//            'cookie' => $cookie,
//            'value' => $cookieValue
//        ));
    } else {
//        tot_log_in_debug_log('No value found for cookie: ', array(
//            'cookie' => $cookie
//        ));
    }
    return $cookieValue;
}

function tot_check_for_query_cookie($cookie, $time = 60*30) {
    $cookie = Settings::stripTotFieldKey($cookie);
//    tot_log_as_html_comment('TOT - searching via get_query_var for ', $cookie);
    $paramValue = get_query_var($cookie, NULL);
    if (isset($paramValue)) {
        setcookie($cookie, $paramValue, time() + $time, '/');
        // echo "$cookie = $paramValue";
    } else {
        $paramValue = Settings::get_param_or_cookie($cookie);
    }

    if ($paramValue) {
        // tot_log_as_html_comment('TOT - set cookie ' . $cookie, $paramValue);
    }
}


/**
 * @param $wp_query
 * @return bool
 */
function tot_is_excluded($wp_query)
{
    $current_route = $_SERVER['REQUEST_URI'];
    $get_site_url = get_site_url();
    $normalized_current_url = tot_normalize_url_path($current_route);
    $normalized_site_url = tot_normalize_url_path($get_site_url . '/');
    $exclude_home_page = Settings::get_setting('tot_field_disable_load_home');
    $exclusions = $exclude_home_page ? array($normalized_site_url) : array();
    $post = $wp_query->post;
    $template_name = empty($post) ? null : get_post_meta($post->ID, '_wp_page_template', true);

    $slug = basename(get_permalink());
    $excluded = in_array($normalized_current_url, $exclusions);

//	\TOT\tot_debugger::inst()->log($excluded ? 'tot_scripts_are_excluded' : 'tot_scripts_included', array(
////        '$slug' => $slug,
////        '$current_route' => $current_route,
////        '$get_site_url' => $get_site_url,
////        '$normalized_site_url' => $normalized_site_url,
//        '$normalized_current_url' => $normalized_current_url,
//        '$exclusions' => $exclusions,
////        '$template_name' => $template_name,
////        '$exclude_home_page' => $exclude_home_page,
////        '$excluded' => $excluded,
//    ));

    return $excluded;
}
