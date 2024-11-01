<?php

use TOT\Settings;

require 'ssl-not-configured.php';
require 'create-email-redirect-page.php';
require 'create-signup-required-page.php';
require 'create-faq-page.php';
require 'set-default-options.php';

// For testing...
//delete_option('tot_activated');
//delete_option('tot_version');
//update_option('tot_version', '0.0.1', false);
//delete_option('tot-dismissed-first_activation');
//delete_option('tot-dismissed-no_key');


/**
 * Prevent auto-updating the major releases
 */
add_filter('auto_update_plugin', 'tot_prevent_dangerous_auto_updates', 99, 2);
function tot_prevent_dangerous_auto_updates($should_update, $plugin)
{
    global $tot_plugin_slug;
    if ( ! isset( $plugin->plugin, $plugin->new_version )
        || $tot_plugin_slug !== $plugin->plugin
        || version_compare($plugin->new_version, $plugin->Version, '<=')
    ) {
        return $should_update;
    }
    
    // TOT update
    $version = explode('.', $plugin->Version);
    $new_version = explode('.', $plugin->new_version);

    // if it's a major release
    if (
        $new_version[0] > $version[0]
    ) {
        $should_update = false;
    }

    return $should_update;
}

add_action( 'admin_init', 'tot_run_upgrades' );

function tot_upgrade_steps() {
	return array(
		'1.4.4', '1.5.5', '1.7.0', '1.14.1', '3.0.0'
	);
}

function tot_run_upgrades() {

	$option_name = 'tot_version';

	$previous_version   = get_option( $option_name );
	$tot_version        = tot_plugin_get_version();

	if( $previous_version === false ) {
		update_option( $option_name, '0.0.0', false );
		$previous_version = '0.0.0';

        tot_create_email_redirect_page();
		tot_set_default_options();
	} else {
		tot_ssl_present_notice();
	}

    // Run these everytime we upgrade.
    tot_create_signup_required_page();
	tot_create_faq_page();
    tot_bp_setDefaultVals();
    tot_um_setDefaultVals();

	if($previous_version != '0.0.0' && version_compare( $tot_version, $previous_version, '>' ) ) {
        \TOT\tot_debugger::inst()->register_new_operation(__FUNCTION__);
        \TOT\tot_debugger::inst()->add_part_to_operation('','previous_version is ' . $previous_version);
        $upgrade_steps = tot_upgrade_steps();

        foreach( $upgrade_steps as $version ) {
			if( version_compare( $version, $previous_version, '>' ) ) {
                $path = 'scripts/' . preg_replace('/\./', '-', $version)  . '.php';
				\TOT\tot_debugger::inst()->add_part_to_operation('',"running update " . $path);
				include $path;
			}
		}

        \TOT\tot_debugger::inst()->log_operation(__FUNCTION__);
	}
    update_option( $option_name, $tot_version, false );
}

function tot_ssl_present_notice(){
    //adds notice to screen if SSL is not properly configured
    $ssl_configured = get_option('tot_ssl_misconfiguration');
    if( isset($ssl_configured) && ($ssl_configured === true) ) {
        $is_TOT_settings_page = strpos($_SERVER['REQUEST_URI'], 'totsettings');
        if (!$ssl_configured && $is_TOT_settings_page) {
            add_action('admin_notices', 'tot_ssl_not_configured_notice');
        }
    }
}

function tot_license_is_added() {
	$options = get_option('tot_options');
	if( isset( $options ) ) {
		return !!(isset($options['tot_field_license_key']) && ($options['tot_field_license_key'] !== ''));
	}else {
		return false;
	}
}
