<?php

function tot_get_user_approval_log( $user_id ) {
    if(!isset($user_id)) {
        $user_id = get_current_user_id();
    }

    $user_approval_log = get_user_meta($user_id, 'tot_approval_status_log', true);

    if(!$user_approval_log || ($user_approval_log === '') ) {
        return array();
    }

    $user_approval_log = json_decode($user_approval_log);

    return $user_approval_log;
}

function tot_add_user_approval_entry( $user_id, $status ) {
    if( !current_user_can('edit_users') ) {
        return null;
    }
    if(!isset($user_id)) {
        $user_id = get_current_user_id();
    }

    $current_status = tot_get_user_approval_status($user_id);
    $user_approval_log = tot_get_user_approval_log($user_id);

    if($current_status === $status) {
        return $user_approval_log;
    }

    array_push($user_approval_log, array(
        'status' => $status,
        'timestamp' => time(),
        'updated_by' => $user_id
    ));

    update_user_meta(
        $user_id,
        'tot_approval_status_log',
        json_encode($user_approval_log)
    );

    update_user_meta(
        $user_id,
        'tot_approval_status',
        $status
    );

    tot_update_role_based_on_status($user_id, $status);

    return $user_approval_log;
}

function tot_update_role_based_on_status($user_id, $status) {
    if(!current_user_can('promote_users') || user_can($user_id, 'administrator')) {
        return;
    }
    if(!tot_get_option('tot_field_approval') || !isset($user_id) || !isset($status) || (($status !== 'approved') && ($status !== 'rejected') && ($status !== ''))) {
        return;
    }

    $field = null;

    if($status === 'approved') {
        $field = tot_get_option('tot_field_approved_role');
    }elseif ($status === 'rejected') {
        $field = tot_get_option('tot_field_rejected_role');
    }elseif ($status === '') {
        $field = tot_get_option('tot_field_pending_role');
    }

    if($field && ($field !== '')) {
        wp_update_user(array(
            'ID' => $user_id,
            'role' => $field
        ));
    }
}

function tot_get_user_approval_most_recent_entry( $user_id ) {
    if(!isset($user_id)) {
        $user_id = get_current_user_id();
    }

    $user_approval_log = tot_get_user_approval_log($user_id);

    if( count($user_approval_log) === 0 ) {
        return null;
    }

    return end($user_approval_log);
}

function tot_get_user_approval_status( $user_id ) {
    if(!isset($user_id)) {
        $user_id = get_current_user_id();
    }

    $user_approval_entry = get_user_meta($user_id, 'tot_approval_status', true);

    if( isset($user_approval_entry) && ($user_approval_entry !== '') ) {
        return $user_approval_entry;
    }else {
        return null;
    }
}