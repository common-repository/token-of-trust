<?php
/** @var string $page */

$currDomain = tot_get_setting_prod_domain();
$currLicense = tot_get_setting_license_key();

$old_tot_keys = get_option('old_tot_keys', []);
$oldDomain = $old_tot_keys['tot_field_prod_domain'] ?? false;
$oldLicense = $old_tot_keys['tot_field_license_key'] ?? false;

$urlDomain = isset($_GET["appDomain"]) ? tot_scrub_prod_domain(sanitize_text_field($_GET["appDomain"])) : false;
$urlLicense = isset($_GET["license"]) ? sanitize_text_field($_GET["license"]) : false;

$tot_connection_ok = tot_test_keys();

if ($urlLicense && $urlDomain) {

    // This condition will be met after reverting to old keys process
    if (($oldDomain !== false || $oldLicense !== false) &&
            ($oldDomain == $currDomain && $oldLicense == $currLicense)) :

        // delete after reverting 
        delete_option('old_tot_keys');

    elseif ($currDomain != $urlDomain || $currLicense != $urlLicense): // new keys are detected
        ?>
        <div class="tot-modal-wrapper" data-open-automatically="true">
            <div class='tot-modal modal-transition' id='tot-modal-test'>
                <div class='tot-modal-header' >
                    <h3>Connect Token of Trust to WordPress</h3>
                </div>
                <div class='tot-modal-content'>
                    <div class="icon"></div>
                    Connect your Token of Trust account to <br>
                    WordPress
                    <input type="hidden" value="<?php echo esc_attr($urlDomain); ?>" id="tot_field_prod_domain_url" />
                    <input type="hidden" value="<?php echo esc_attr($urlLicense); ?>" id="tot_field_license_key_url" />
                </div>
                <div class='tot-modal-action' id='tot-modal-action' data-modal-id='tot-modal-test'>

                    <a href="#" class="tot-btn tot-btn-full-width tot-btn-primary tot-modal-connect trackable" data-action="clicked_connect_license_keys" id="modal-connect-btn">
                        <!-- loader -->
                        <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner_P7sC{transform-origin:center;animation:spinner_svv2 .75s infinite linear}@keyframes spinner_svv2{100%{transform:rotate(360deg)}}</style><path d="M10.14,1.16a11,11,0,0,0-9,8.92A1.59,1.59,0,0,0,2.46,12,1.52,1.52,0,0,0,4.11,10.7a8,8,0,0,1,6.66-6.61A1.42,1.42,0,0,0,12,2.69h0A1.57,1.57,0,0,0,10.14,1.16Z" class="spinner_P7sC"/></svg>
                        <!-- end-loader -->
                        
                        <span>Authorize Connection</span>
                    </a>
                    
                    <a href="<?php echo admin_url($page); ?>"
                       class="tot-btn tot-btn-full-width tot-btn-remove tot-modal-close tot-modal-toggle trackable"
                       data-action="clicked_decline_license_keys">
                        Cancel Setup
                    </a>
                </div>
            </div>
        </div>
    <?php
    // after updating keys
    elseif ($currDomain == $urlDomain && $currLicense == $urlLicense): ?>

        <div class="tot-modal-wrapper" data-open-automatically="true">
            <div class='tot-modal modal-transition' id='tot-modal-test'>

                <?php if ($tot_connection_ok): ?>        
                    <div class='tot-modal-header'>
                        <h3>Nice! Your site is connected</h3>
                    </div>
                    <div class='tot-modal-content'>
                        <?php delete_option('old_tot_keys'); ?>
                        You've successfully connected to Token of Trust!
                    </div>
                    <div class='tot-modal-action' id='tot-modal-action' data-modal-id='tot-modal-test'>
                        <a href="#" class="tot-btn tot-btn-full-width tot-btn-primary tot-modal-close tot-modal-toggle">
                            Continue to Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <?php tot_remove_registered_license(); ?>
                    <div class='tot-modal-header'>
                        <h3>Something went wrong</h3>
                    </div>
                    <div class='tot-modal-content'>
                        Your website failed to connect to Token of Trust.
                    </div>
                    <div class='tot-modal-action' id='tot-modal-action' data-modal-id='tot-modal-test'>
                        <a href="#" id="contactSupportForConnecting" class="tot-btn tot-btn-full-width tot-btn-primary tot-modal-close tot-modal-toggle">
                            Contact Support for Help
                        </a>
                        <a href="#" class="tot-link tot-modal-close tot-modal-toggle">Go to my WordPress dashboard</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
    endif;
	?>
    <div class="tot-hide-but-accessible automatic-keys-form-wrapper">
        <form action="admin.php?page=totsettings" method="post">
			<?php
			settings_fields( 'tot' );
			do_settings_sections( 'tot' );
			submit_button('Save Settings');
			?>
        </form>
    </div>
	<?php
}
