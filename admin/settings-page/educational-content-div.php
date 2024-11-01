<?php

function tot_add_educational_div($tot_connection_ok, $is_test_mode, $is_live_mode, $tot_is_production) {
    ?>
    <div class="wrap">
        <div class="tot-edu-row">
            <div class="tot-edu-column">
                <h1 id="tot-edu-header">Get started with Token of Trust.</h1>
            </div>
        </div>
        <div id = "tot-edu-main-content">
            <p>Welcome to the Token of Trust Identity Verification plugin for WordPress.</p>
                <?php if (tot_option_has_a_value(tot_get_setting_license_key()) && tot_option_has_a_value(tot_get_setting_prod_domain())): ?>

                    <div class="tot-edu-row">
                        <div class="tot-edu-column">
                            <h3>Features on paid plans include:</h3>
                            <ul id = "tot_features_list">
                                <li>Age verification</li>
                                <li>Government issued ID verification</li>
                                <li>Configurable/custom verification workflows</li>
                                <li>Page level protection using verification results</li>
                                <li>Viewable user verification summaries</li>
                            </ul>
                        </div>
                        <div class="tot-edu-column"></div>
                    </div>

                    <div class="tot-edu-row tot-callout-row">
                        <div class="tot-edu-column">
                            <a href="<?php echo tot_production_origin(); ?>/p/id_verification_wordpress/<?php echo tot_frontend_link_parameters('workflows-example'); ?>#workflows" class="tot-edu-img"><img src="<?php echo tot_production_origin(); ?>/external_assets/wordpress/<?php echo tot_plugin_get_version(); ?>/showVerificationWorkflowDetails/img_wp_verificationWorkflow.jpg"/></a>
                            <div class="tot-edu-content">
                                <h1>Verification Workflows</h1>
                                <p>Verification workflow sequences can be configured to suit the needs of your website.</p>
                                <p><a href="https://tokenoftrust.com/resources/integrations/wordpress<?php echo tot_frontend_link_parameters('workflows-example'); ?>">View example workflows</a></p>
                            </div>
                        </div>
                     </div>
                <?php else: ?>
                    <div class="tot-edu-row tot-callout-row">

                        <div class="tot-edu-column">
                            <a href="<?php echo tot_production_origin(); ?>/p/id_verification_wordpress/<?php echo tot_frontend_link_parameters('workflows-example'); ?>#workflows" class="tot-edu-img"><img src="<?php echo tot_production_origin(); ?>/external_assets/wordpress/<?php echo tot_plugin_get_version(); ?>/showVerificationWorkflowDetails/img_wp_verificationWorkflow.jpg"/></a>
                            <div class="tot-edu-content">
                                <a  href="<?php echo tot_production_origin() . '/hq/register/' . tot_frontend_link_parameters("get-license", ['send_plugins' => true]); ?>"
                                    class="button button-primary button-large" id="tot_configure_button">
                                    Get Started Now!
                                </a>
                                <br>
                                <a id="tot_configure_manually" href="?page=totsettings_license">Setup license keys manually</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
        </div>
    </div>
    <?php
}
function tot_mailto_support($tot_connection_ok, $is_test_mode, $is_live_mode, $tot_is_production){
    global $wpdb;
    $subject = "[". tot_get_setting_prod_domain(). "] Support Request";

    $body = "Environment Information:\nPlugin Version: " . tot_plugin_get_version(). "\nPHP Version:" . phpversion() .
        "\nMySQL Version: " .  $wpdb->db_version() . "\nWordPress Version: " . get_bloginfo('version').
        "\nSSL enable: " . (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) == 'ON' ? "true" : "false") .
        "\nAPI Connection: " . (!is_wp_error($tot_connection_ok) ? "true" : "false") .
        "\nTest Mode: " . ($is_test_mode ? "true" : "false") . "\nLive Mode: " . ($is_live_mode  ? "true" : "false") .
        "\nConnection Type: " . (is_wp_error($tot_is_production) ? "Error" : ($tot_is_production ?  'Live Mode':'Test Mode')) ;

    $a = "mailto:support@tokenoftrust.com?subject=". rawurlencode($subject)."&amp;body=". rawurlencode($body);

    return $a;
}
