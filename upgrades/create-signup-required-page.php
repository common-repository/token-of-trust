<?php

use TOT\Settings;

//Function called when the plugin is activated.
//Creates a confirmation email page if it has not yet been created
//Sets the confirmation email redirect option to that page if this option has not been configured before
function tot_create_signup_required_page() {
    $page_title = 'Verification Required';

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
            'post_content' =>
                '
<blockquote>
<!-- wp:shortcode -->
[tot-reputation-status auto-launch-when-not-verified="false" ]
<!-- /wp:shortcode -->
</blockquote>
<p>Customize this page to meet your needs. This is done from the wp-admin Pages menu for this title. Here are some things you might might want to leverage on this page:</p>
<ul>
<li><strong>Provide Context</strong> - Tell the user why they are there. e.g. In order to checkout you\'ll need to get verified.</li>
<li><strong>Shortcode: \'tot-reputation-status\'</strong> - recommended and used above. inserts a text description of the users current state. If they are not yet verified adds a link to get verified.</li>
<li><strong>Shortcode: \'tot-wp-embed\'</strong> - (use with caution) includes a variety of widgets that allow the user to see their current status. These include buttons that could lead users away from the flow you intend.  <a href="https://tokenoftrust.com/docs/integrations/wordpress/'.tot_frontend_link_parameters("sign-up-page", ["send_plugins" => true]).'#common-pages">More information.</a></li>
<li><strong>Auto-launch when not verified: \'auto-launch-when-not-verified\'</strong> - search for this attribute on this pre-defined page and set to "true" to auto-launch. Be careful here - we recommend you do this only if your users are prepared for a popup to get them verified. We not auto-launching and instead setting the stage by telling them what is going to happen and then letting them click a button to get started.</li>

</ul>

<p>References:</p>
<ul>
<li><a href="https://www.wonderplugin.com/wordpress-carousel-plugin/how-to-add-shortcode-to-the-new-gutenberg-editor/">How to add shortcodes to a page</a></li>
</ul>
            ', //content of page
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
	    Settings::set_setting('tot_field_confirm_verification_required_redirect', $page_url);
    }

}