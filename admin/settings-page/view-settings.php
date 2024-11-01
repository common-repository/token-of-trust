<?php
global $wpdb;

use TOT\Settings;

$settingsUpdated = isset($_GET['settings-updated']);
$forcedLicenseRefresh = isset($_GET['tot-force-license-refreshed']);
if ($settingsUpdated) {
    add_settings_error('tot_messages', 'tot_message', __('Settings Saved', 'token-of-trust'), 'updated');
    $tot_license_detail_request = tot_refresh_keys();
} else {
    $tot_license_detail_request = tot_request_license_details();
}


settings_errors('wporg_messages');
$tot_keys = tot_get_keys();
$options = get_option('tot_options');
$app_domain = tot_get_setting_prod_domain();
$app_license = tot_get_setting_license_key();
$app_title = !is_wp_error($tot_keys) && $tot_keys['app_title'] ? $tot_keys['app_title'] : 'Our app';
$tot_has_license = !is_wp_error($tot_keys);

$tot_get_test_keys_mailto = 'mailto:onboarding@tokenoftrust.com?subject='
    . 'Test Mode for ' . $app_domain
    . '&body='
    . $app_title . ' needs Test Mode keys for ' . $_SERVER['HTTP_HOST'] . '.';


$domain = tot_get_setting_prod_domain();
$testKeyResult = tot_keys_work('test');
$liveKeyResult =  tot_keys_work('live');
if (isset($testKeyResult) && !is_wp_error($testKeyResult) && $testKeyResult != false) {
    $test_keys_work = true;
} else {
    $test_keys_work = false;
}
if (isset($liveKeyResult) && !is_wp_error($liveKeyResult) && $liveKeyResult != false) {
    $live_keys_work = true;
} else {
    $live_keys_work = false;
}
$is_production_mode = tot_is_production();
$tot_origin = tot_origin();

$tot_connection_ok = $live_keys_work || $test_keys_work;

?>
<div class="wrap tot-settings-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="admin.php?page=totsettings" method="post">
        <div class="tot-right-col">
            <h3>Installation Status</h3>
            <table class="tot-support-table">
                <tr>
                    <td>Plugin Version</td>
                    <td><?php echo tot_plugin_get_version(); ?></td>
                    <td><?php echo '<i class="dashicons dashicons-yes tot-match"></i>'; ?></td>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo phpversion(); ?></td>
                    <td><?php

                        if (version_compare(phpversion(), '5.4.0', '>=')) {
                            echo '<i class="dashicons dashicons-yes tot-match"></i>';
                        } else {
                            echo '<i class="dashicons dashicons-no-alt tot-no-match"></i>';
                        }

                        ?></td>
                </tr>
                <tr>
                    <td>MySQL Version</td>
                    <td><?php
                        echo esc_html($wpdb->db_version());
                        ?></td>
                    <td><?php

                        if (version_compare($wpdb->db_version(), '5.0.0', '>')) {
                            echo '<i class="dashicons dashicons-yes tot-match"></i>';
                        } else {
                            echo '<i class="dashicons dashicons-no-alt tot-no-match"></i>';
                        }

                        ?></td>
                </tr>
                <tr>
                    <td>WordPress Version</td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                    <td><?php

                        if (version_compare(get_bloginfo('version'), '0.0.0', '>')) {
                            echo '<i class="dashicons dashicons-yes tot-match"></i>';
                        } else {
                            echo '<i class="dashicons dashicons-no-alt tot-no-match"></i>';
                        }

                        ?></td>
                </tr>
                <tr>
                    <td>SSL (https)</td>
                    <td></td>
                    <td><?php
                        $tot_ssl_is_enabled = false;
                        if (isset($_SERVER['HTTPS'])) {
                            if (strtoupper($_SERVER['HTTPS']) == 'ON') {
                                $tot_ssl_is_enabled = true;
                                echo '<i class="dashicons dashicons-yes tot-match"></i>';
                            }
                        }
                        if (!$tot_ssl_is_enabled) {
                            echo '<i class="dashicons dashicons-no-alt tot-no-match"></i>';
                        }
                        ?></td>
                </tr>
                <tr id="tot-tr-api-connection">
                    <td>API Connection</td>
                    <td></td>
                    <td><?php
                        if ($live_keys_work || $test_keys_work) {
                            echo '<i class="dashicons dashicons-yes tot-match"></i>';
                        } else {
                            echo '<i class="dashicons dashicons-no-alt tot-no-match"></i>';
                        }
                        ?></td>
                </tr>
                <tr id="tot-tr-test-mode">
                    <td>Test Mode</td>
                    <td><?php
                        if ($tot_has_license && !is_wp_error($tot_license_detail_request) && isset($tot_keys['public_test']) && isset($tot_keys['secret_test'])) {
                            ?>
                            <a href="<?php echo tot_production_website(); ?>/docs/integrations/wordpress/<?php echo tot_frontend_link_parameters('next-step-after-test', ['send_plugins' => true]); ?>" target="_blank" class="button">
                                What's next
                            </a>
                            <?php
                        }
                        ?></td>
                    <td><?php
                        if ($test_keys_work) {
                            echo '<i class="dashicons dashicons-yes tot-match"></i>';
                        } else {
                            echo '<i class="dashicons dashicons-no-alt tot-no-match"></i>';
                        }
                        ?></td>
                </tr>
                <tr id="tot-tr-live-mode">
                    <td>Live Mode</td>
                    <td><?php

                        if (($app_domain !== '') && $tot_has_license && (!is_wp_error($tot_license_detail_request) || !$is_production_mode)) {
                            if (!$live_keys_work) {
                                ?>
                                <a href="<?php echo '?page=totsettings_license&tot-subpage=live'; ?>" class="button">
                                    Go live
                                </a>
                                <?php
                            }
                        }
                        ?></td>
                    <td><?php
                        if ($live_keys_work) {
                            echo '<i class="dashicons dashicons-yes tot-match"></i>';
                        } else {
                            echo '<i class="dashicons dashicons-no-alt tot-no-match"></i>';
                        }
                        ?></td>
                </tr>
                <tr id="tot-tr-connection-type">
                    <td>Connection Type</td>
                    <td colspan="2"><?php

                        if (is_wp_error($is_production_mode)) {
                            echo 'Error';
                        } elseif ($is_production_mode) {
                            echo 'Live Mode';
                        } else {
                            echo 'Test Mode';
                        }
                        ?></td>
                </tr>
            </table>
            <a href="https://tokenoftrust.com/resources<?php echo tot_frontend_link_parameters("give-feedback"); ?>" class="button">
                Give Feedback
            </a>
        </div>
        <div class="tot-left-col">
            <?php
            settings_fields('tot');
            do_settings_sections('tot');
            submit_button('Save Settings');
            ?>
        </div>


        <?php
        $possibleErrors =
            tot_debug_mode() ?
            array($tot_license_detail_request, $tot_connection_ok, $tot_keys, tot_keys_work('test'), tot_keys_work('live')) :
            array($tot_license_detail_request, $tot_connection_ok, $tot_keys, tot_keys_work('test'));

        tot_display_error_console($possibleErrors);
        ?>
        <?php echo '<p><a href="' . admin_url('admin.php') . '?page=totsettings_license&tot-webhooks' . '" id="tot-webhook-log-link">Webhook activity log</a></p>'; ?>
        <?php echo '<p><a href="' . admin_url('admin.php') . '?page=totsettings_license&tot-logs' . '" id="tot-logs-link">Debug logs</a></p>'; ?>
        <?php echo '<p><a href="' . admin_url('admin.php') . '?page=totsettings_license&tot-force-license-refresh=true">Force Refresh of API Keys</a></p>'; ?>

    </form>
</div>
