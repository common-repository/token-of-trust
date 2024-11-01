<?php

function tot_get_user_confirmation_token($user_id)
{
    $database_key = 'tot_confirmation_token';

    $user_meta = get_user_meta(
        $user_id,
        $database_key,
        true
    );

    if (isset($user_meta) && ($user_meta !== '')) {
        return $user_meta;
    } else {
        $guid = tot_create_guid();

        add_user_meta(
            $user_id,
            $database_key,
            $guid,
            true
        );

        return $guid;
    }
}
