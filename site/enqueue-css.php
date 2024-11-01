<?php

add_action( 'wp_enqueue_scripts', 'tot_load_wp_style' );

function tot_load_wp_style() {
    global $wp_query;

    if (!tot_is_excluded($wp_query)) {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'tot-get-verified',
            plugins_url('../shared/assets/tot-get-verified.js', __FILE__),
            array('jquery'), tot_plugin_get_version());
        wp_enqueue_style( 'token-of-trust',
            plugins_url( '/token-of-trust.css', __FILE__ ), '', tot_plugin_get_version());
    }
}