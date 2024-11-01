<?php

function tot_log_in_debug_log($key, $message, $return=false) {
//    return tot_log_as_html_pre($key, $message, $return);
    $output = "TOT: " . tot_log_key_value($key, $message);
    if(!$return) {
        error_log($output);
    }
    return $output;
}

function tot_log_as_html_comment($key, $message, $return=false) {
//    return tot_log_as_html_pre($key, $message, $return);
    $output = " <!-- " . tot_log_key_value($key, $message) . " --> ";
    if(!$return) {
        if (tot_debug_mode()) {
            echo $output;
        }
    }
    return $output;
}

function tot_log_as_html_pre($key, $message, $return=false) {
    $output = '<pre>' . tot_log_key_value($key, $message) . '</pre>';
    if(!$return) {
        if (tot_debug_mode()) {
            echo $output;
        }
    }
    return $output;
}

function tot_log_key_value($key, $message) {
    return "[tot-debug][$key]" . print_r( $message, true );
}

function tot_log_debug($log) {
    if (true === WP_DEBUG && tot_debug_mode()) {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}

/**
 * @deprecated
 * @see \TOT\tot_debugger::log()
 *
 * @param String|false  $head    Used to explain the logged message
 * @param String|Array  $log        The body message that will be logged in database
 * @param String        $type       info, error, success, warning.
 */
function tot_log($head = '', $log, $type="info"){

    // Don't store if the debug mode is not active
    if(!tot_debug_mode() && !TOT\Settings::get_param_or_cookie('debug_mode')){
        return;
    }

    if(!in_array($type, ['info', 'error', 'success', 'warning'])){
        $type = 'info';
    }

    // if it's an array
    if(is_array($log) || is_object($log)){
        $log = print_r($log, true);
    }

    // convert special characters to html entities
    $log = htmlspecialchars($log);

    // get old logs
    $db_logs = get_option("tot_logs", array());

    $details = array(
        'timestamp' => current_time('mysql'),
        'body' => $log,
        'type' => $type
    );

    if ($head && !empty($head)) {
        $details['head'] = $head;
    }
    $num_of_messages = array_unshift($db_logs, $details);

    $db_logs = array_slice($db_logs,0, apply_filters('tot_logs_max_length', 200));

    update_option("tot_logs", $db_logs, false);
}

/**
 * @return String|boolean. Return the remaining time in seconds or false for expired
 */
function tot_get_debug_remain_time(){
    $expires = (int) \TOT\Settings::get_setting('debug_mode');
    if(!$expires || time() > $expires){
        return false;
    }

    $time_left = $expires - time();
    if ($time_left > 60){
        return round($time_left / 60,1) . "min";
    }

    return $time_left . "sec";
}
