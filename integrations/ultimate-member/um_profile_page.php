<?php

// http://docs.ultimatemember.com/article/64-extending-ultimate-member-profile-menu-using-hooks
// https://wordpress.org/support/topic/problems-with-extending-account-page-with-custom-tab/

add_filter('um_profile_tabs', 'tot_add_custom_profile_tab', 1000);
add_action('um_profile_content_verification_default', 'tot_um_profile_content_verification_default');

function tot_add_custom_profile_tab($tabs)
{

    $tabs['verification'] = array(
        'name' => __('Verification', 'token-of-trust'),
        'icon' => 'um-faicon-custom-tot'
    );
    return $tabs;

}

function tot_um_profile_content_verification_default($args)
{
    $profileIsSameAsLoggedin = um_profile_id() == get_current_user_id() ? true : false;
    $widget_type = $profileIsSameAsLoggedin ? 'accountConnector' : 'reputationSummary';
    if (tot_debug_mode()) {
        echo '<!-- um_profile_id: ' . um_profile_id() . ' get_current_user_id: ' . get_current_user_id() . ' match: ' . !!(um_profile_id() == get_current_user_id()) . ' -->';
    }
    echo do_shortcode('[tot-wp-embed wp-userid="' . um_profile_id() . '" tot-widget="' . $widget_type . '"][/tot-wp-embed]');
    if (!is_user_logged_in()) {
        global $wp;
        $url = home_url($wp->request);
        echo '<div> To report abuse or fraud committed by this user, please <u><a href=' . wp_login_url($url) . ' title=\"Login\">log in</a></u>.</div>';
    }elseif (!$profileIsSameAsLoggedin && get_option('tot_options')['tot_report_abuse']) {
        echo do_shortcode('[tot-wp-embed tot-widget="reportAbuse" wp-reported-userid="' . um_profile_id() . '" wp-userid="' . get_current_user_id() . '"][/tot-wp-embed]');
    }
}
