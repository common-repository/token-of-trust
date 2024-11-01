<?php

use TOT\Settings;

//Function called when the plugin is activated.
function tot_create_faq_page() {
	$is_tot_connection_ok = is_tot_connection_ok();

    $page_title = 'Token of Trust FAQ';
    $args = array(
        'post_type'              => 'page',
        'title'                  => $page_title,
        'post_status'            => 'all',
        'posts_per_page'         => 1,
        'no_found_rows'          => true,
        'order'                  => 'ASC',
    );
    $query = new WP_Query($args);

    $page = ! empty( $query->post ) ? $query->post : null;
    //only creates a page if it does not exist.
    if (!$page) {
        //create a variable to specify the details of page
        $post = array(
            'post_content' => '',
            'post_title' => $page_title,
            'post_status' => 'draft',
            'post_type' => 'page',
			'post_slug' => 'tokenoftrust-faq'
        );
        wp_insert_post($post); // creates page
        $query = new WP_Query($args);
        $page = ! empty( $query->post ) ? $query->post : null;
    }

	// convert FAQ page to draft if api connection failed OR it's disabled from setting
	if ($page) {
		if (!$is_tot_connection_ok || Settings::get_setting('tot_field_disable_generating_faq')) {
			wp_update_post(array(
				'ID' => $page->ID,
				'post_status' => 'draft'
			));
		}
	}

}