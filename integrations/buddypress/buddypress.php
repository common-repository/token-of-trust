<?php

// https://buddypress.org/support/topic/add-tab-to-user-profile-from-plugin/
// https://gist.github.com/shanebp/5d3d2f298727a0a036e5
add_action( 'bp_setup_nav', 'tot_bp_add_verification_tab', 100 );


function tot_bp_add_verification_tab() {
	global $bp;

    if(bp_get_option( 'tot-plugin-verification-tab' )) {
        bp_core_new_nav_item(array(
            'name' => 'Verification',
            'slug' => 'verification',
            'parent_url' => $bp->displayed_user->domain,
            'parent_slug' => $bp->profile->slug,
            'screen_function' => 'tot_bp_verification_screen',
            'position' => 200,
            'default_subnav_slug' => 'verification'
        ));
    }
}

function tot_bp_verification_screen() {
    add_action('bp_template_title', 'tot_bp_verification_screen_title');
    add_action('bp_template_content', 'tot_bp_verification_screen_content');
    bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));

}

function tot_bp_verification_screen_title() {
	echo '<h2>Verification</h2>';
}

function tot_bp_verification_screen_content() {

    $profileIsSameAsLoggedin =  bp_displayed_user_id() == bp_loggedin_user_id() ? true : false;
    $widget_type = $profileIsSameAsLoggedin  ? 'accountConnector' : 'reputationSummary';

    if(tot_debug_mode()) {
        echo '<!-- bp_displayed_user_id: ' . bp_displayed_user_id() . ' bp_loggedin_user_id: ' . bp_loggedin_user_id() . ' match: ' . !!(bp_displayed_user_id() == bp_loggedin_user_id()) . ' -->';
    }
    echo do_shortcode('[tot-wp-embed wp-userid="' . bp_displayed_user_id() . '" app-buddypress="true" tot-widget="' . $widget_type . '"][/tot-wp-embed]');
    if(!is_user_logged_in()){
        global $wp;
        $url =  home_url( $wp->request );
        echo '<div> To report abuse or fraud committed by this user, please <u><a href=' . wp_login_url($url) . ' title=\"Login\">log in</a></u>.</div>';
    }elseif(!$profileIsSameAsLoggedin && bp_get_option( 'tot-plugin-report-abuse-button' )){
        echo do_shortcode('[tot-wp-embed tot-widget="reportAbuse" wp-reported-userid="' . bp_displayed_user_id() . '" wp-userid="' . bp_loggedin_user_id() . '"][/tot-wp-embed]');
    }

}

/**
 * Your setting main function
 */
function tot_plugin_admin_settings() {
    /* This is how you add a new section to BuddyPress settings */
    add_settings_section(
        'tot_plugin_section',
        __( 'Token of Trust Settings',  'token-of-trust' ),
        'tot_plugin_setting_callback_section',
        'buddypress'
    );

    /* This is how you add a new field to your plugin's section */
    add_settings_field(
    /* the option name you want to use for your plugin */
        'tot-plugin-verification-tab',
        __( 'Token of Trust Verification Tab', 'token-of-trust' ),
        'tot_verification_tab_callback',
        'buddypress',
        'tot_plugin_section'
    );

    /*
       This is where you add your setting to BuddyPress ones
       Here you are directly using intval as your validation function
    */
    register_setting(
        'buddypress',
        'tot-plugin-verification-tab',
        'intval'
    );
    /* This is how you add a new field to your plugin's section */
    add_settings_field(
        'tot-plugin-report-abuse-button',
        __( 'Token of Trust Report Abuse Button', 'token-of-trust' ),
        'tot_report_abuse_field_callback',
        'buddypress',
        'tot_plugin_section'
    );

    /*
       This is where you add your setting to BuddyPress ones
       Here you are directly using intval as your validation function
    */
    register_setting(
        'buddypress',
        'tot-plugin-report-abuse-button',
        'intval'
    );

}

/**
 * You need to hook bp_register_admin_settings to register your settings
 */
add_action( 'bp_register_admin_settings', 'tot_plugin_admin_settings' );

/**
 * This is the display function for your section's description
 */
function tot_plugin_setting_callback_section() {
    ?>
    <p class="description"><?php _e( 'Token of Trust Plugin settings', 'token-of-trust' );?></p>
    <?php
}

/**
 * This is the display function for your field
 */
function tot_verification_tab_callback() {
    $bp_plugin_option_value = bp_get_option( 'tot-plugin-verification-tab' );
    ?>
    <input id="tot-plugin-verification-tab" name="tot-plugin-verification-tab" type="checkbox" value="1" <?php checked( $bp_plugin_option_value ); ?> />
    <label for="tot-plugin-verification-tab"><?php _e( 'Show Token of Trust Verification tab on users profiles', 'token-of-trust' ); ?></label>
    <p class="description"><?php _e( 'If selected, the Token of Trust Reputation Summary widget will show on the users\' profile, in a new tab', 'token-of-trust' ); ?></p>
    <?php
}

function tot_report_abuse_field_callback() {
    $bp_plugin_option_value = bp_get_option( 'tot-plugin-report-abuse-button' );
    ?>
    <input id="tot-plugin-report-abuse-button" name="tot-plugin-report-abuse-button" type="checkbox" value="1" <?php checked( $bp_plugin_option_value ); ?> />
    <label for="tot-plugin-report-abuse-button"><?php _e( 'Show Token of Trust Report Abuse widget on users profiles', 'token-of-trust' ); ?></label>
    <p class="description"><?php _e( 'If selected, the Token of Trust Report Abuse widget will show on the users\' profile', 'token-of-trust' ); ?></p>
    <?php
}



function tot_bp_setDefaultVals(){
    if(function_exists("bp_get_option")) {
        if( bp_get_option('tot-plugin-verification-tab', 'not set') === 'not set' ) {
            update_option('tot-plugin-verification-tab', 1, false);
        }

        if( bp_get_option('tot-plugin-report-abuse-button', 'not set') === 'not set' ) {
            update_option('tot-plugin-report-abuse-button', 0, false);
        }
    }
}