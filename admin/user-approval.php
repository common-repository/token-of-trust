<?php

add_action( 'wp_ajax_tot_set_user_approval', 'tot_set_user_approval_handler' );

function tot_set_user_approval_handler() {
    if( !current_user_can('edit_users') ) {
        wp_send_json_error(null, 400);
        return;
    }

    $new_state = $_POST['newState'];
    $user_id = $_POST['userId'];

    tot_add_user_approval_entry($user_id, $new_state);

    $user_data = get_userdata($user_id);
    $response = array(
        'user_id' => $user_id,
        'roles' => array()
    );
    $wp_roles = wp_roles();

    foreach ( $user_data->roles as $role ) {
        if ( isset( $wp_roles->role_names[ $role ] ) ) {
            $response['roles'][ $role ] = translate_user_role( $wp_roles->role_names[ $role ] );
        }
    }

    if ( empty( $response['roles'] ) ) {
        $response['roles']['none'] = _x( 'None', 'no user roles', 'token-of-trust' );
    }

    wp_send_json($response);
}