<?php

add_filter('um_account_page_default_tabs_hook', 'tot_add_custom_account_tab', 1000);
add_action('um_account_tab__verification', 'tot_um_account_tab__verification');
add_filter('um_account_content_hook_verification', 'tot_um_profile_account_content_verification');

if($tot_ultimate_member_version === '1') {
    add_action('um_account_user_photo_hook', 'tot_um_verified_indicator', 100);
}


function tot_um_verified_indicator(){
    $tot_options = get_option('tot_options');

    if( isset($tot_options['tot-plugin-verified-indicator-account']) && ($tot_options['tot-plugin-verified-indicator-account'] == 1)){
        echo do_shortcode('[tot-wp-embed tot-widget="verifiedIndicator" tot-show-when-not-verified="true" wp-userid="' . um_profile_id() . '"][/tot-wp-embed]');
    }

}
function tot_add_custom_account_tab($tabs)
{

    $tot_options = get_option('tot_options');

    if(isset($tot_options['tot-plugin-verification-tab-account']) && ($tot_options['tot-plugin-verification-tab-account'] == 1)){
        $tabs[150]['verification'] = array(
            'icon' => 'um-faicon-custom-tot-gray',
            'title' => __( 'Identity Verification', 'token-of-trust' ),
            'custom' => true,
            'show_button' => false
        );
    }

    return $tabs;
}

/* make our new tab hookable */


function tot_um_account_tab__verification($info)
{

    global $ultimatemember;
    extract($info);
    $output = $ultimatemember->account->get_tab_output('verification');

    // $myfile = fopen("newfile3.txt", "w") or die("Unable to open file!");
    // fwrite($myfile, json_encode($output, JSON_UNESCAPED_SLASHES));
    // fclose($myfile);

    //$output = json_encode($output, JSON_UNESCAPED_SLASHES);

    if ($output) {
        echo $output;
    }
}

/* Finally we add some content in the tab */


function tot_um_profile_account_content_verification($output)
{
    $widget_type = 'accountConnector';
    ob_start();
    ?>
    <div class="um-field">
        <?php
        if (tot_debug_mode()) {
            echo '<!-- um_profile_id: ' . um_profile_id() . ' get_current_user_id: ' . get_current_user_id() . ' match: ' . !!(um_profile_id() == get_current_user_id()) . ' -->';
        }
        echo do_shortcode('[tot-wp-embed wp-userid="' . um_profile_id() . '" tot-widget="' . $widget_type . '"][/tot-wp-embed]');
        ?>
    </div>
    <?php

    $output .= ob_get_contents();
    ob_end_clean();
    return $output;

}

