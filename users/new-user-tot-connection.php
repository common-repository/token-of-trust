<?php

add_action('user_register', 'tot_new_user_tot_connection', 10, 1);

//////////

function tot_new_user_tot_connection($wp_user_id) {

    $connection = tot_set_connection($wp_user_id, null, null, true);

    do_action('tot_after_user_register_connection', $connection);

}