<?php

$tot_license_detail_request = tot_refresh_keys();
$keys = tot_get_keys();
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
$tot_connection_ok = $live_keys_work || $test_keys_work;

$app_domain = tot_get_setting_prod_domain();
$app_title = !is_wp_error($keys) && $keys['app_title'] ? $keys['app_title'] : 'Our app';
$tot_get_live_keys_mailto = 'mailto:sales+wordpress@tokenoftrust.com?subject='
    . 'Live Mode for ' . $app_domain
    . '&body='
    . $app_title . ' is ready to go live and we\'re ready for you to review the site for approval. You can view the site at ' . $_SERVER['HTTP_HOST'] . '. Please contact us if you have any questions.';

?>

<div class="wrap tot-settings-page tot-settings-page-live-mode">

    <div class="tot-left-col">

        <h1>Live Mode</h1>

        <?php

        if(tot_live_mode_available()) {

        ?>

            <h2>Congratulations!</h2>
            <p>Live mode is Enabled.</p>
            <a href="?page=totsettings_license" class="button button-primary">Back to API settings</a>

        <?php

        }else {

        ?>

            <h2>Switch from sandbox to live mode</h2>
            <p>Your website is currently setup to connect with the Token of Trust sandbox, a virtual testing environment that mimics the live Token of Trust production environment. Token of Trust sandbox supports the same components and API features as the live environment.</p>
            <p><strong>Submit Website for Approval.</strong></p>
            <p>All Token of Trust integrations require approval before live-mode may be enabled.</p>
            <p><a href="<?php echo $tot_get_live_keys_mailto; ?>" class="button button-primary">Start a Submission</a></p>

            <br><hr><br>

            <h2>Already done?</h2>
            <p>Once live mode is enabled on your Token of Trust account, update our API keys here.</p>
            <p>
                <a href="" class="button button-primary">Refresh your API keys</a>

            </p>

            <br><hr><br>

            <?php tot_display_error_console(array($tot_license_detail_request, $tot_connection_ok, $keys), tot_keys_work('test'), tot_keys_work('live')); ?>

        <?php

        }

        ?>

    </div>

</div>
