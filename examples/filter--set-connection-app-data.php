<?php

namespace TOT;

class Hook_Set_Connection_App_Data {

    public function __construct() {
        $this->register_wordpress_hooks();
    }

    public function register_wordpress_hooks() {
        if( isset( $_GET['test_tot_set_connection_app_data'] ) ) {
            add_filter('tot_set_connection_app_data', array($this, 'add_location_data'), 10, 2);
        }
    }

    public function add_location_data( $data, $user_id ) {

        $user_data = \get_userdata($user_id);
        // echo '<pre>';
        // print_r( $user_data );
        // echo '</pre>';

        // Would use $user_data->data->USER_FIELD_NAME but it is not a core WP user field, example below.
        $data['location'] = array(
            'locality' => 'Minneapolis',
            'region' => 'MN',
            'countryCode'=> 'US'
        );

        return $data;

    }

}

new Hook_Set_Connection_App_Data();
