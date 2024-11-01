<?php

use TOT\Settings;

//Function called when the plugin is activated.
//Creates a confirmation email page if it has not yet been created
//Sets the confirmation email redirect option to that page if this option has not been configured before
function tot_create_email_redirect_page() {
    $page_title = 'Welcome to ' . get_bloginfo($show = 'name') . '!';

    $args = array(
        'post_type'              => 'page',
        'title'                  => $page_title,
        'post_status'            => 'all',
        'posts_per_page'         => 1,
        'no_found_rows'          => true,
        'orderby'                => 'post_date ID',
        'order'                  => 'ASC',
    );
    $query = new WP_Query($args);
    $page = ! empty( $query->post ) ? $query->post : null;

    //only creates a page if it does not exists. even if $page is in trash, a new one will not be created.
    if (!$page) {
        //create a variable to specify the details of page
        $post = array(
            'post_content' => '<h3>Your email was successfully confirmed.
            Thank you for signing up</h3> <h2><a href=' . wp_login_url() . ' title=\"Login\">Log in</a></h2>
            <h2><a href=' . home_url() . ' title=\"Home\">Home</a></h2>', //content of page
            'post_title' => $page_title, //title of page
            'post_status' => 'publish', //status of page - publish or draft
            'post_type' => 'page'  // type of post
        );
        wp_insert_post($post); // creates page

        $query = new WP_Query($args);
        $page = ! empty( $query->post ) ? $query->post : null;
    }
    //check if page is published and not in trash or is a draft.
    if($page && $page->post_status === 'publish'){
	    $page_url = str_replace(get_site_url(), '', get_page_link($page->ID));
	    Settings::set_setting('tot_field_confirm_email_success_redirect', $page_url);
    }

}