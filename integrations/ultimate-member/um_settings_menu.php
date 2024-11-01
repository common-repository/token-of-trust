<?php

if($tot_ultimate_member_installed){

    add_action('admin_menu', 'tot_submenu');
    add_action('admin_init', 'tot_ultimate_member_settings');
    add_action( 'admin_enqueue_scripts', 'tot_load_um_scripts' );

}

function tot_load_um_scripts(){
    wp_enqueue_script( 'admin-tot-um', plugins_url( '/um_form_controls.js', __FILE__ ), array(), tot_plugin_get_version() );
}


function tot_submenu()
{
	if (
		tot_option_has_a_value(tot_get_setting_license_key())
		&& tot_option_has_a_value(tot_get_setting_prod_domain())
		&& tot_live_or_in_trial()
	) {
        $name = add_submenu_page('totsettings', 'Ultimate Member Settings', 'Ultimate Member Settings', 'manage_options', 'tot_um_settings', 'tot_integration_page');
        add_action("load-{$name}", 'tot_load_um_settings_page');
    }
}

function tot_integration_page()
{
    require('tot_integration_settings_page.php');
}


function tot_ultimate_member_settings()
{
    global $tot_ultimate_member_version;

    add_settings_section(
        'tot_plugin_um_profile_settings',
        __('Profile Page', 'token-of-trust'),
        'tot_plugin_um_callback_section',
        'tot_um_settings'
    );

    add_settings_section(
        'tot_plugin_um_account_settings',
        __('Account Page', 'token-of-trust'),
        'tot_plugin_um_callback_section_account',
        'tot_um_settings'
    );

    add_settings_field(

        'tot-plugin-verification-tab',
        __('Token of Trust Verification Tab', 'token-of-trust'),
        'tot_verification_tab_um_callback',
        'tot_um_settings',
        'tot_plugin_um_profile_settings'
    );
    add_settings_field(

        'tot-plugin-verification-permission',
        __('Who can see Verification Tab?', 'token-of-trust'),
        'tot_verification_permission_um_callback',
        'tot_um_settings',
        'tot_plugin_um_profile_settings'
    );

    add_settings_field(

        'tot-profile-tab-verification-roles',
        __('Allowed Roles', 'token-of-trust'),
        'tot_verification_allowed_roles_um_callback',
        'tot_um_settings',
        'tot_plugin_um_profile_settings'
    );

    register_setting(
        'tot_um_settings',
        'tot-profile_tab_verification_roles-select'
    );

    register_setting(
        'tot_um_settings',
        'tot-plugin-verification-tab',
        'intval'
    );

    register_setting(
        'tot_um_settings',
        'tot-plugin-verification-permission',
        'intval'
    );

    add_settings_field(
        'tot-plugin-report-abuse-button',
        __('Token of Trust Report Abuse Button', 'token-of-trust'),
        'tot_report_abuse_field_um_callback',
        'tot_um_settings',
        'tot_plugin_um_profile_settings'
    );

    add_settings_field(
        'tot-plugin-verification-tab-account',
        __('Verification tab in menu', 'token-of-trust'),
        'tot_plugin_verification_tab_account_callback',
        'tot_um_settings',
        'tot_plugin_um_account_settings'
    );

    if($tot_ultimate_member_version === '1') {
        add_settings_field(
            'tot-plugin-verified-indicator-account',
            __('Verified Indicator', 'token-of-trust'),
            'tot_plugin_verified_indicator_account_callback',
            'tot_um_settings',
            'tot_plugin_um_account_settings'
        );
    }

    register_setting(
        'tot_um_settings',
        'tot-plugin-verification-tab-account',
        'intval'
    );

    if($tot_ultimate_member_version === '1') {
        register_setting(
            'tot_um_settings',
            'tot-plugin-verified-indicator-account',
            'intval'
        );
    }

    register_setting(
        'tot_um_settings',
        'tot-plugin-report-abuse-button',
        'intval'
    );


}

function tot_plugin_um_callback_section_account()
{
    ?>
    <p class="description"><?php _e('The Ultimate Member account settings page (.../account/) can be set to include Token of Trust\'s \'account connector\' component.', 'token-of-trust'); ?></p>
    <?php
}


function tot_plugin_um_callback_section()
{
    ?>
    <p class="description"><?php _e('You can enable and control Token of Trust on Ultimate Member\'s profile pages', 'token-of-trust'); ?></p>
    <?php
}

/**
 * This is the display function for your field
 */
function tot_verification_tab_um_callback()
{
    $um_option_value = um_get_option('profile_tab_verification');
    ?>

    <div class="tot-switch-options">
        <label class="tot-enable<?php echo $um_option_value == "1" ? " tot-selected" : "" ;?>" data-id="tot-plugin-verification-tab">
            <span>On</span>
        </label>
        <label class="tot-disable<?php echo $um_option_value != "1" ? " tot-selected" : "" ;?> " data-id="tot-plugin-verification-tab">
            <span>Off</span></label>
        <input type="hidden" id="tot-plugin-verification-tab" name="tot-plugin-verification-tab" type="checkbox"
               value="<?php echo $um_option_value ;?>" <?php checked($um_option_value); ?> />
    </div>


    <?php
}
function tot_verification_permission_um_callback()
{
    global $tot_ultimate_member_version;
    $um_option_value = um_get_option('profile_tab_verification_privacy');
    $um_option_verify_tab = um_get_option('profile_tab_verification');
    ?>
    <select <?php echo $um_option_verify_tab != "1" ? "style='display:none'"  : "" ;?> class="tot_field_standard" id="tot-plugin-verification-permission"
            name="tot-plugin-verification-permission">
        <?php
            if($tot_ultimate_member_version === '1') {
                global $ultimatemember;
                foreach ($ultimatemember->profile->tabs_privacy() as $i=>$opt) {
                    echo "<option value=" . $i . " " . selected($um_option_value, $i) . ">" . $opt . "</option>";
                }
            }else {
                foreach (UM()->profile()->tabs_privacy() as $i=>$opt) {
                    echo "<option value=" . $i . " " . selected($um_option_value, $i) . ">" . $opt . "</option>";
                }
            }
         ?>
    </select>
       <?php


}
function tot_verification_allowed_roles_um_callback()
{
    global $tot_ultimate_member_version;
    $um_option_value = um_get_option('profile_tab_verification_roles') ? um_get_option('profile_tab_verification_roles') : [];
    $um_option_verify_tab = um_get_option('profile_tab_verification');
    ?>
    <select <?php echo $um_option_verify_tab != "1" ? "style='display:none'" : "" ;?> multiple style ="display: none" class="tot_field_standard" id="tot-profile-tab-verification-roles"
            name="tot-profile-tab-verification-roles[]" >
        <?php
        echo "<option value='' disabled>Choose user roles...</option>";

        if($tot_ultimate_member_version === '1') {
            global $ultimatemember;
            foreach ($ultimatemember->query->get_roles() as $opt) {
                $selected = in_array( strtolower($opt), $um_option_value ) ? ' selected="selected" ' : '';
                echo "<option value=" . strtolower($opt) . " " . $selected . ">" . $opt . "</option>";
            }
        }else {
            foreach (UM()->roles()->get_roles() as $opt) {
                $selected = in_array( strtolower($opt), $um_option_value ) ? ' selected="selected" ' : '';
                echo "<option value=" . strtolower($opt) . " " . $selected . ">" . $opt . "</option>";
            }
        }
        ?>
    </select>
    <?php
}
function tot_report_abuse_field_um_callback()
{
    $um_option_value = isset(get_option('tot_options')['tot_report_abuse']) ? get_option('tot_options')['tot_report_abuse'] : " ";
    $um_option_verify_tab = um_get_option('profile_tab_verification');
    ?>
    <div class="tot-switch-options">
        <label class="tot-enable<?php echo $um_option_value == "1" ? " tot-selected" : "" ;?> <?php echo $um_option_verify_tab != "1" ? " tot-disabled" : "" ;?>" data-id="tot-plugin-report-abuse-button">
            <span>On</span>
        </label>
        <label class="tot-disable<?php echo $um_option_value != "1" ? " tot-selected" : "" ;?> <?php echo $um_option_verify_tab != "1" ? " tot-disabled" : "" ;?>" data-id="tot-plugin-report-abuse-button">
            <span>Off</span></label>
        <input type="hidden" id="tot-plugin-report-abuse-button" name="tot-plugin-report-abuse-button" type="checkbox"
               value="<?php echo $um_option_value ;?>" <?php checked($um_option_value); ?> />
    </div>



    <?php
}

function tot_plugin_verification_tab_account_callback(){
    $um_option_value = isset(get_option('tot_options')['tot-plugin-verification-tab-account']) ? get_option('tot_options')['tot-plugin-verification-tab-account'] : " ";
    ?>
    <div class="tot-switch-options">
        <label class="tot-enable<?php echo $um_option_value == "1" ? " tot-selected" : "" ;?>" data-id="tot-plugin-verification-tab-account">
            <span>On</span>
        </label>
        <label class="tot-disable<?php echo $um_option_value != "1" ? " tot-selected" : "" ;?> " data-id="tot-plugin-verification-tab-account">
            <span>Off</span></label>
        <input type="hidden" id="tot-plugin-verification-tab-account" name="tot-plugin-verification-tab-account" type="checkbox"
               value="<?php echo $um_option_value ;?>" <?php checked($um_option_value); ?> />
    </div>

   <?php
}

function tot_plugin_verified_indicator_account_callback(){
    global $tot_ultimate_member_version;
    if($tot_ultimate_member_version === '1') {
        $um_option_value = isset(get_option('tot_options')['tot-plugin-verified-indicator-account']) ? get_option('tot_options')['tot-plugin-verified-indicator-account'] : " ";
        ?>
        <div class="tot-switch-options">
            <label class="tot-enable<?php echo $um_option_value == "1" ? " tot-selected" : "" ;?>" data-id="tot-plugin-verified-indicator-account">
                <span>On</span>
            </label>
            <label class="tot-disable<?php echo $um_option_value != "1" ? " tot-selected" : "" ;?> " data-id="tot-plugin-verified-indicator-account">
                <span>Off</span></label>
            <input type="hidden" id="tot-plugin-verified-indicator-account" name="tot-plugin-verified-indicator-account" type="checkbox"
                   value="<?php echo $um_option_value ;?>" <?php checked($um_option_value); ?> />
        </div>

        <?php
    }
}

function tot_load_um_settings_page()
{
    if (isset($_POST["option_page"]) && $_POST["option_page"] == "tot_um_settings") {
        tot_um_save_theme_settings();

        //after submitting the form, redirects the user to the correct setting page
        tot_add_flash_notice('Settings updated', 'success', false);
        wp_redirect(admin_url('admin.php?page=tot_um_settings'));
        exit;
    }
}

function tot_um_save_theme_settings()
{
    $tot_settings = get_option('tot_options');
    $um_settings = get_option('um_options');
    $tot_settings['tot_report_abuse'] = $_POST['tot-plugin-report-abuse-button'];
    $tot_settings['tot-plugin-verification-tab-account'] = $_POST['tot-plugin-verification-tab-account'];
    $tot_settings['tot-plugin-verified-indicator-account'] = $_POST['tot-plugin-verified-indicator-account'];
    $um_settings['profile_tab_verification_privacy'] = $_POST['tot-plugin-verification-permission'];
    $um_settings['profile_tab_verification_roles'] = $_POST['tot-profile-tab-verification-roles'];

    if ($_POST['tot-plugin-verification-tab'] == '1') {
        $um_settings['profile_tab_verification'] = $_POST['tot-plugin-verification-tab'];
    } else {
        $um_settings['profile_tab_verification'] = 0;
    }

    update_option('um_options', $um_settings, false);
    update_option('tot_options', $tot_settings, true);

}

function tot_um_setDefaultVals()
{
    if (function_exists("um_get_option")) {
        $tot_options = get_option('tot_options');

        if(!array_key_exists('tot_report_abuse', $tot_options)) {
            $tot_options['tot_report_abuse'] = 0;
        }

        if(!array_key_exists('tot-plugin-verification-tab-account', $tot_options)) {
            $tot_options['tot-plugin-verification-tab-account'] = 1;
        }

        if(!array_key_exists('tot-plugin-verified-indicator-account', $tot_options)) {
            $tot_options['tot-plugin-verified-indicator-account'] = 0;
        }

        update_option('tot_options', $tot_options, true);

        $um_settings = get_option('um_options');

        if(!array_key_exists('profile_tab_verification', $um_settings)) {
            $um_settings['profile_tab_verification'] = 1;
        }

        update_option('um_options', $um_settings, false);
    }
}