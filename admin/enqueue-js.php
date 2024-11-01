<?php

add_action( 'admin_enqueue_scripts', 'tot_load_wp_admin_scripts' );

function tot_load_wp_admin_scripts() {
	wp_enqueue_script( 'admin-token-of-trust', plugins_url( '/token-of-trust.js', __FILE__ ), array(), tot_plugin_get_version());
    wp_localize_script('admin-token-of-trust', 'totObj', [
        'totHost' => tot_origin(),
        'version' => tot_plugin_get_version(),
        'appDomain' => tot_get_setting_prod_domain() ?: parse_url(home_url('/'))['host'],
        'restUrl' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('tot_rest')
    ]);
}