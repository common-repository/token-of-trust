<?php

use TOT\Settings;

function tot_add_embed()
{
    global $wp_query;

    //Check which template is assigned to current page we are looking at
    $excluded = tot_is_excluded($wp_query);
    if ($excluded) {
        //If page is not on the exclusion list then load Token of Trust if it's enabled.
        //tot_log_as_html_comment('tot_scripts_are_excluded', '$excluded');
        return;
    }

    // Only add if TOT is enabled.
    $manual_verification = Settings::get_setting('tot_field_is_manual_verification_activated');
    $checkout_required = Settings::get_setting('tot_field_checkout_require');
    $verification_required = Settings::get_setting('tot_field_verification_gates_enabled');
    if (!($manual_verification || $checkout_required || $verification_required)) {
        \TOT\tot_debugger::inst()->log('tot_scripts_are_excluded', array(
            '$manual_verification' => $manual_verification,
            '$checkout_required' => $checkout_required,
            '$verification_required' => $verification_required,
        ));

        return;
    }

    /////////////////////////////////////////////////////////
    // Below is code for the Age Gate modal.
    $isAgeGateEnabled = Settings::get_setting('tot_field_age_gate_enabled');

    if ( !is_admin() &&
          isset($isAgeGateEnabled) && $isAgeGateEnabled == 1 &&
          !is_checkout() &&
          !is_order_received_page()
       ) {

        // In order for this to fire...
        // - you can't be logged in as an admin.
        // - age gate must be enabled in WP Admin
        // - you can't be on the checkout or thank you pages

        wp_enqueue_script( 'tot-age-gate',
            plugins_url('../shared/assets/tot-age-gate.js', __FILE__),
            array('jquery'), tot_plugin_get_version());
    }

    $tot_keys = tot_get_public_key();
    if (!is_wp_error($tot_keys)) {
        ?>
        <script id="tot-embed-code">
            (function (d) {
                var b = window, a = document;
                b.tot = b.tot || function () {
                    (b.tot.q = b.tot.q || []).push(
                        arguments)
                };
                var c = a.getElementsByTagName("script")[0];
                a.getElementById("tot-embed") || (a = a.createElement("script"), a.id = "tot-embed", a.async = 1, a.src = d, c.parentNode.insertBefore(a, c))
            })("<?php echo tot_origin(); ?>/embed/embed.js");

            tot('setPublicKey', '<?php echo $tot_keys; ?>');
        </script>
        <?php
    } else {
	    \TOT\tot_debugger::inst()->log('tot_scripts_are_excluded', 'is_wp_error($tot_keys)');
    }
}