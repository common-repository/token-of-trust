<?php

add_action( 'wp_ajax_tot_dismissed_notice_handler', 'tot_ajax_notice_handler' );

function tot_ajax_notice_handler() {
    $type = $_POST['type'];
    update_option( 'tot-dismissed-' . $type, true, false);
}