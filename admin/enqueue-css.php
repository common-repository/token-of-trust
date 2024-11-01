<?php

add_action( 'admin_enqueue_scripts', 'tot_load_wp_admin_style' );

function tot_load_wp_admin_style() {
	wp_enqueue_style( 'admin-token-of-trust', plugins_url( '/token-of-trust.css', __FILE__ ), array(), tot_plugin_get_version());
}