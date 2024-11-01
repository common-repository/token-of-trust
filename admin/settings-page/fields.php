<?php

use \TOT\Integrations\Sentry\Sentry;
/**
 * Adding a new field:
 *
 * 1. The section is registered with `register_setting`
 * 2. The section is rendered with `settings_fields` and `do_settings_sections` in `view-general-settings.php`
 * 3. The section is added with `add_settings_section`
 * 4. The field is added with `add_settings_field`
 * 5. The callback function is defined. These commonly use:
 *        `tot_checkbox_field`
 *        `tot_standard_text_field`
 *        `tot_dropdown_field`
 *        `tot_textarea_field`
 * 6. The field is saved in `tot_save_theme_settings`
 */

add_action('admin_init', 'tot_settings_init');

function tot_settings_init()
{
    register_setting('tot', 'tot_options');
    register_setting('tot_settings_email', 'tot_options');
    register_setting('tot_settings_tracking', 'tot_options');
    register_setting('tot_settings_generated_pages', 'tot_options');
    register_setting('tot_settings_advanced_general', 'tot_options');
    add_action("load-toplevel_page_totsettings", 'tot_load_settings_page');
    // Section: Keys
    add_settings_section(
        'tot_section_keys',
        __('License & API', 'token-of-trust'),
        'tot_no_op',
        'tot'
    );
    add_settings_section(
        'tot_section_email',
        __('Email Confirmation', 'token-of-trust'),
        'tot_no_op',
        'tot_settings_email'
    );
    add_settings_section(
        'tot_settings_verification_gates',
        __('Verification Gates', 'token-of-trust'),
        'tot_no_op',
        'tot_settings_verification_gates'
    );

    add_settings_section(
        'tot_settings_approval',
        __('User Approval', 'token-of-trust'),
        'tot_no_op',
        'tot_settings_approval'
    );
    add_settings_section(
        'tot_section_tracking',
        __('User Tracking', 'token-of-trust'),
        'tot_no_op',
        'tot_settings_tracking'
    );

    add_settings_section(
        'tot_section_disabled_pages',
        __('Disabled Pages', 'token-of-trust'),
        'tot_no_op',
        'tot_settings_disabled_pages'
    );

	add_settings_section(
		'tot_section_generated_pages',
		__('Generated Pages', 'token-of-trust'),
		'tot_no_op',
		'tot_settings_generated_pages'
	);

    // Field: Disable on Home Page
    add_settings_field(
        'tot_field_disable_load_home', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Disable on Home Page', 'token-of-trust'),
        'tot_field_disable_load_home_cb',
        'tot_settings_disabled_pages',
        'tot_section_disabled_pages',
        [
            'label_for' => 'tot_field_disable_load_home'
        ]
    );

	// Field: Disable tot FAQ
	add_settings_field(
		'tot_field_disable_generating_faq', // as of WP 4.6 this value is used only internally
		// use $args' label_for to populate the id inside the callback
		__('Disable FAQ page', 'token-of-trust'),
		'tot_field_disable_generating_faq_cb',
		'tot_settings_generated_pages',
		'tot_section_generated_pages',
		[
			'label_for' => 'tot_field_disable_generating_faq'
		]
	);

	add_settings_section(
		'tot_section_advanced_general',
		__('Advanced', 'token-of-trust'),
		'tot_no_op',
		'tot_settings_advanced_general'
	);

	add_settings_field(
		'tot_field_sentry_dsn', // as of WP 4.6 this value is used only internally
		// use $args' label_for to populate the id inside the callback
		__('Sentry DSN', 'token-of-trust'),
		'tot_standard_text_field',
		'tot_settings_advanced_general',
		'tot_section_advanced_general',
		[
			'label_for' => 'tot_field_sentry_dsn'
		]
	);

    // Field: License Key
    add_settings_field(
        'tot_field_license_key', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('License Key<sup class="tot-required">*</sup>', 'token-of-trust'),
        'tot_field_license_key_cb',
        'tot',
        'tot_section_keys',
        [
            'label_for' => 'tot_field_license_key'
        ]
    );

    // Field: Confirm New User Emails
    add_settings_field(
        'tot_field_confirm_new_user_emails', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Confirm New User Emails', 'token-of-trust'),
        'tot_field_confirm_new_user_emails_cb',
        'tot_settings_email',
        'tot_section_email',
        [
            'label_for' => 'tot_field_confirm_new_user_emails'
        ]
    );

    // Field: Confirm Email Success Redirect
    add_settings_field(
        'tot_field_confirm_email_success_redirect', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Confirm Email Success Redirect', 'token-of-trust'),
        'tot_field_confirm_email_success_redirect_cb',
        'tot_settings_email',
        'tot_section_email',
        [
            'label_for' => 'tot_field_confirm_email_success_redirect'
        ]
    );

    // Field: Debug Mode
    add_settings_field(
        'tot_field_debug_mode', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Debug Mode', 'token-of-trust'),
        'tot_field_debug_mode_cb',
        'tot',
        'tot_section_keys',
        [
            'label_for' => 'tot_field_debug_mode',
            'is_transient' => false
        ]
    );


    // Field: Enable Approval of Users via Admin panels.
    add_settings_field(
        'tot_field_verification_gates_enabled', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Enable Verifications by Page', 'token-of-trust'),
        'tot_field_verification_gates_enabled_cb',
        'tot_settings_verification_gates',
        'tot_settings_verification_gates',
        [
            'label_for' => 'tot_field_verification_gates_enabled',
            'class'     => 'tot_field_verification_gates_enabled'
        ]
    );

    // Field: Default Setting for verification on pages
    add_settings_field(
        'tot_field_default_setting_verification_on_pages',
        __('Default Setting for Verification on Pages', 'token-of-trust'),
        'tot_field_default_setting_verification_on_pages_cb',
        'tot_settings_verification_gates',
        'tot_settings_verification_gates',
        [
            'label_for' => 'tot_field_default_setting_verification_on_pages',
            'class'     => 'tot_field_default_setting_verification_on_pages'
        ]
    );

    // Field: Bypass Token of Trust Verification for Page
    $pages = get_pages();
    $options = [];
    foreach ($pages as $page){
        $option = array(
            'label' => $page->post_title,
            'value' => $page->post_name
        );

        array_push($options, $option);
    }

    add_settings_field(
        'tot_field_bypass_verification_for_pages',
        __('Bypass Token of Trust Verification for Pages', 'token-of-trust'),
        'tot_field_bypass_verification_for_pages_cb',
        'tot_settings_verification_gates',
        'tot_settings_verification_gates',
        [
            'label_for' => 'tot_field_bypass_verification_for_pages',
            'class'     => 'tot_field_bypass_verification_for_pages',
            'options'   => $options
        ]
    );

    add_settings_field(
        'tot_field_require_verification_for_pages',
        __('Require Token of Trust Verification for Pages', 'token-of-trust'),
        'tot_field_require_verification_for_pages_cb',
        'tot_settings_verification_gates',
        'tot_settings_verification_gates',
        [
            'label_for' => 'tot_field_require_verification_for_pages',
            'class'     => 'tot_field_require_verification_for_pages',
            'options'   => $options
        ]
    );

    // Roles to bypass Token of Trust Verification
    $role_options = array();
    $role_results = get_editable_roles();
    if (!empty($role_results)) {
        foreach ($role_results as $role) {
            array_push($role_options, array(
                'label' => $role['name'],
                'value' => strtolower($role['name'])
            ));
        }
    }
    add_settings_field(
        'tot_field_roles_pass_checkout_verification',
        __('Roles to bypass Token of Trust Verification', 'token-of-trust'),
        'tot_field_roles_pass_checkout_verification_cb',
        'tot_settings_verification_gates',
        'tot_settings_verification_gates',
        [
            'label_for' => 'tot_field_roles_pass_checkout_verification',
            'class'     => 'tot_field_roles_pass_checkout_verification',
            'options' => $role_options
        ]
    );
    // Field Enable Simple Age Gate
    add_settings_field(
        'tot_field_age_gate_enabled', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Enable Age Gate', 'token-of-trust'),
        'tot_field_age_gate_enabled_cb',
        'tot_settings_verification_gates',
        'tot_settings_verification_gates',
        [
            'label_for' => 'tot_field_age_gate_enabled'
        ]
    );

    // Minimum Age
    add_settings_field(
            '',
		__('Minimum age', 'token-of-trust'),
		function () {
                echo '<a href="admin.php?page=totsettings&open-age-modal=1">Edit Minimum Age</a>';
        },
		'tot_settings_verification_gates',
		'tot_settings_verification_gates'
    );

    // Field: Enable Approval of Users via Admin panels.
    add_settings_field(
        'tot_field_approval', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Allow admin\'s to manually approve users.', 'token-of-trust'),
        'tot_field_approval_cb',
        'tot_settings_approval',
        'tot_settings_approval',
        [
            'label_for' => 'tot_field_approval'
        ]
    );

    // Field: Approval Role
    add_settings_field(
        'tot_field_approved_role', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Role for approved users', 'token-of-trust'),
        'tot_field_approved_role_cb',
        'tot_settings_approval',
        'tot_settings_approval',
        [
            'label_for' => 'tot_field_approved_role'
        ]
    );

    // Field: Debug Mode
    add_settings_field(
        'tot_field_rejected_role', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Role for rejected users', 'token-of-trust'),
        'tot_field_rejected_role_cb',
        'tot_settings_approval',
        'tot_settings_approval',
        [
            'label_for' => 'tot_field_rejected_role'
        ]
    );

    // Field: Debug Mode
    add_settings_field(
        'tot_field_pending_role', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Role for pending users', 'token-of-trust'),
        'tot_field_pending_role_cb',
        'tot_settings_approval',
        'tot_settings_approval',
        [
            'label_for' => 'tot_field_pending_role'
        ]
    );

// TODO - Future support for not_verified here.
// Field: Debug Mode
//    add_settings_field(
//        'tot_field_not_verified_role', // as of WP 4.6 this value is used only internally
//        // use $args' label_for to populate the id inside the callback
//        __('Role for unverified users', 'token-of-trust'),
//        'tot_field_not_verified_role_cb',
//        'tot_settings_approval',
//        'tot_settings_approval',
//        [
//            'label_for' => 'tot_field_not_verified_role'
//        ]
//    );

    // Field: Debug Mode
    add_settings_field(
        'tot_field_auto_identify', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Identify user', 'token-of-trust'),
        'tot_field_auto_identify_cb',
        'tot_settings_tracking',
        'tot_section_tracking',
        [
            'label_for' => 'tot_field_auto_identify'
        ]
    );

    add_action('admin_enqueue_scripts', function () {
        wp_enqueue_script(
                'admin-token-of-trust-select2-js',
                plugins_url('../../lib/select2/dist/js/select2.js', __FILE__),
                array('jquery')
        );
        wp_enqueue_style(
                'admin-token-of-trust-select2-css',
                plugins_url('../../lib/select2/dist/css/select2.css', __FILE__)
        );
        if (isset($_GET['page']) && strpos($_GET['page'], 'totsettings') !== false) {
            wp_enqueue_script('tot-admin-tot-quickstart-js', plugin_dir_url(__FILE__) . 'common/tot-quickstart.js', array('jquery', 'admin-token-of-trust'));
            wp_enqueue_script('tot-admin-view-quickstart-js', plugin_dir_url(__FILE__) . 'view-quickstart.js', array('jquery', 'admin-token-of-trust'));
        }
    });
}

//////////
// Section Callbacks

// Section: Keys
function tot_no_op($args)
{
    // no-op
}

//detects  calls to update settings
//Code for this method and tot_save_theme_settings is based on "https://www.smashingmagazine.com/2011/10/create-tabs-wordpress-settings-pages/"
function tot_load_settings_page()
{

    if (current_user_can('manage_options') && isset($_POST["tot_options"])) {
        tot_save_theme_settings();
        $goback = add_query_arg('settings-updated', 'true', wp_get_referer());
        tot_add_flash_notice('Settings updated', 'success', false);
        wp_redirect($goback);
        exit;
    }
}

//Method that handles post data from settings form submssion
function tot_sanitize_arr($arr){
    $sanitized = array_map(function ($value){
        return sanitize_text_field($value);
    }, $arr);

    return $sanitized;
}
function tot_save_theme_settings()
{
    global $pagenow;
    $settings = get_option('tot_options');
    $old_sentry_dsn = $settings['tot_field_sentry_dsn'] ?? null;

    $page = sanitize_text_field($_GET['page']);
    if ($pagenow == 'admin.php' && strpos($page, 'totsettings') !== false) {
        if (strpos(wp_get_referer(), 'totsettings_general') !== false) {


            $settings['tot_field_disable_generating_faq'] = isset($_POST['tot_options']['tot_field_disable_generating_faq']) ? '1' : '';
            $settings['tot_field_disable_load_home'] = isset($_POST['tot_options']['tot_field_disable_load_home']) ? '1' : '';
            $settings['tot_field_age_gate_enabled'] = isset($_POST['tot_options']['tot_field_age_gate_enabled']) ? '1' : '';
            $settings['tot_field_verification_gates_enabled'] = isset($_POST['tot_options']['tot_field_verification_gates_enabled']) ? '1' : '';
            $settings['tot_field_default_setting_verification_on_pages'] = isset($_POST['tot_options']['tot_field_default_setting_verification_on_pages'])
                    ? sanitize_text_field($_POST['tot_options']['tot_field_default_setting_verification_on_pages']) : 'exclusive';
			$settings['tot_field_sentry_dsn'] = isset($_POST['tot_options']['tot_field_sentry_dsn'])
				? sanitize_text_field($_POST['tot_options']['tot_field_sentry_dsn']) : '';
            $settings['tot_field_bypass_verification_for_pages'] = isset($_POST['tot_options']['tot_field_bypass_verification_for_pages'])
                    ? tot_sanitize_arr($_POST['tot_options']['tot_field_bypass_verification_for_pages']) : '';
            $settings['tot_field_require_verification_for_pages'] = isset($_POST['tot_options']['tot_field_require_verification_for_pages'])
                    ? tot_sanitize_arr($_POST['tot_options']['tot_field_require_verification_for_pages']) : '';
            $settings['tot_field_roles_pass_checkout_verification'] = isset($_POST['tot_options']['tot_field_roles_pass_checkout_verification'])
                    ? tot_sanitize_arr($_POST['tot_options']['tot_field_roles_pass_checkout_verification']) : '';

            $settings['tot_field_approval'] = isset($_POST['tot_options']['tot_field_approval']) ? '1' : '';
            $settings['tot_field_approved_role'] = $_POST['tot_options']['tot_field_approved_role'];
            $settings['tot_field_rejected_role'] = $_POST['tot_options']['tot_field_rejected_role'];
            $settings['tot_field_pending_role'] = $_POST['tot_options']['tot_field_pending_role'];
            $settings['tot_field_auto_identify'] = isset($_POST['tot_options']['tot_field_auto_identify']) ? '1' : '';

        } elseif (strpos(wp_get_referer(), 'totsettings_license') !== false || $page == 'totsettings') {
            $settings['tot_field_prod_domain'] = $_POST['tot_options']['tot_field_prod_domain'];
            $settings['tot_field_license_key'] = $_POST['tot_options']['tot_field_license_key'];

            // Debug mode
            if(isset($_POST['tot_options']['tot_field_debug_mode'])){
                $settings['tot_field_debug_mode'] = time() + apply_filters('tot_field_debug_mode_duration', 60 * 60 * 24);
            } else {
                unset($settings['tot_field_debug_mode']);
            }

            // revert to old keys due to failure in connection
            if($_POST['tot_field_return_old_keys'] ?? false){
                $old_keys = get_option('old_tot_keys',[]);
                $settings['tot_field_prod_domain'] = $old_keys['tot_field_prod_domain'] ?? '';
                $settings['tot_field_license_key'] = $old_keys['tot_field_license_key'] ?? '';
            }else {
                $settings['tot_field_prod_domain'] = sanitize_text_field($_POST['tot_options']['tot_field_prod_domain']);
                $settings['tot_field_license_key'] = sanitize_text_field($_POST['tot_options']['tot_field_license_key']);
            }


            // automatic license_key and old keys are different from the new keys
            if ($_POST['tot_field_automatic_connect'] ?? false
                    && (
                    $settings['tot_field_prod_domain'] != $old_options['tot_field_prod_domain'] &&
                    $settings['tot_field_license_key'] != $old_options['tot_field_license_key']
                    )) {

                // store old keys in case automatic connection is failed
                $old_options = get_option('tot_options', []);
                update_option('old_tot_keys', [
                    'tot_field_prod_domain' => $old_options['tot_field_prod_domain'] ?? false,
                    'tot_field_license_key' => $old_options['tot_field_license_key'] ?? false
                ], false);
            }
        }
    }
    $updated = update_option('tot_options', $settings, true);

    // send refreshed DSN to sentry
	$updated && $old_sentry_dsn !== $settings['tot_field_sentry_dsn'] && Sentry::captureRefreshedDSN();
}

/////////
// Field Callbacks

function tot_standard_text_field($args, $description = null, $additional_text = null)
{
    $options = get_option('tot_options');

    if (isset($additional_text)) {
        foreach ($additional_text as $paragraph) {
            ?>
            <p>
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }

    ?>

    <input class="tot_field_standard"
           type="text"
           value="<?php echo isset($options[$args['label_for']]) ? $options[$args['label_for']] : ""; ?>"
           id="<?php echo esc_attr($args['label_for']); ?>"
           name="tot_options[<?php echo esc_attr($args['label_for']); ?>]"/>
    <?php

    if (isset($description)) {
        foreach ($description as $paragraph) {
            ?>
            <p class="description">
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }
}

function tot_textarea_field($args, $description = null, $additional_text = null)
{
    $options = get_option('tot_options');

    if (isset($additional_text)) {
        foreach ($additional_text as $paragraph) {
            ?>
            <p>
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }

    ?>
    <textarea rows="5" class="tot_field_standard" id="<?php echo esc_attr($args['label_for']); ?>"
              name="tot_options[<?php echo esc_attr($args['label_for']); ?>]"><?php echo isset($options[$args['label_for']]) ? $options[$args['label_for']] : "" ?></textarea>
    <?php

    if (isset($description)) {
        foreach ($description as $paragraph) {
            ?>
            <p class="description">
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }
}


function tot_dropdown_field($args, $options_array, $description = null, $additional_text = null)
{
    $options = get_option('tot_options');

    if (isset($additional_text)) {
        foreach ($additional_text as $paragraph) {
            ?>
            <p>
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }

    ?>
    <select class="tot_field_standard"
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="tot_options[<?php echo esc_attr($args['label_for']); ?>]">
        <?php
        foreach ($options_array as $opt) {
            $selected = '';
            if (isset($options) && isset($options[$args['label_for']])) {
                $selected = selected($options[$args['label_for']], $opt['value'], false);
            }
            echo '<option value="' . $opt['value'] . '" ' . $selected . '>' . $opt['label'] . '</option>';
        } ?>
    </select>
    <?php

    if (isset($description)) {
        foreach ($description as $paragraph) {
            ?>
            <p class="description">
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }
}

function tot_checkbox_field($args, $description = null, $additional_text = null)
{
    $options = get_option('tot_options');

    if (isset($additional_text)) {
        foreach ($additional_text as $paragraph) {
            ?>
            <p>
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }

    $value = "";
    // is it a transient option
    if( isset($args['is_transient']) && $args['is_transient'] ){
        $value = get_transient($args['label_for']);
    } elseif ( isset($options[$args['label_for']]) ) {
        $value = $options[$args['label_for']];
    }
    
    $checked = isset($args['checked'])
        ? (bool) $args['checked']
        : (bool) $value;
    ?>
    <input type="checkbox" class="tot_field_standard" id="<?php echo esc_attr($args['label_for']); ?>"
           name="tot_options[<?php echo esc_attr($args['label_for']); ?>]"
           value="1" <?php checked($checked); ?> />
    <?php

    if (isset($description)) {
        foreach ($description as $paragraph) {
            ?>
            <p class="description">
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }
}


function tot_radio_fields($args, $description = null, $additional_text = null)
{
    $options = get_option('tot_options');

    $default_value = isset($args['default_value']) ? $args['default_value'] : '';
    $saved_value = isset($options[$args['label_for']])
        ? $options[$args['label_for']] : $default_value;
    $radio_btns = isset($args['radio_btns']) ? $args['radio_btns'] : [];

    if (isset($additional_text)) {
        foreach ($additional_text as $paragraph) {
            ?>
            <p>
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }

    foreach ($radio_btns as $value => $desc) {
        $id = esc_attr($args['label_for']. '_' . $value);
        ?>
            <div class="tot-admin-radio-field">
                <input type="radio" id="<?php echo $id; ?>"
                       name="tot_options[<?php echo esc_attr($args['label_for']); ?>]"
                       value="<?php echo esc_attr($value); ?>"
                       <?php checked(esc_attr($value), esc_attr($saved_value)); ?> />
                <label for="<?php echo $id; ?>"><?php echo esc_attr($desc); ?></label>
            </div>
        <?php
    }

    if (isset($description)) {
        foreach ($description as $paragraph) {
            ?>
            <p class="description">
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }
}

function tot_multiselect_field($args, $description = null, $additional_text = null) {
    $options = get_option('tot_options');

    if (isset($additional_text)) {
        foreach ($additional_text as $paragraph) {
            ?>
                        <p>
            <?php echo $paragraph; ?>
                        </p>
            <?php
        }
    }

    ?>
    <select class="tot_field_multiselect"
        multiple
        id="<?php echo esc_attr($args['label_for']); ?>"
        name="tot_options[<?php echo esc_attr($args['label_for']); ?>][]">
                <?php
                foreach ($args['options'] as $opt) {
                        $selected = '';
                        if(isset($options) && isset($options[$args['label_for']]) && $options[$args['label_for']] !== '') {
                                foreach ( $options[ $args['label_for'] ] as $selection ) {
                                        $result = selected( $selection, $opt['value'], false );
                                        if ( $result ) {
                                                $selected = $result;
                                                break;
                                        }
                                }
                        }
                        echo '<option value="' . $opt['value'] . '" ' . $selected . '>' . $opt['label'] . '</option>';
                } ?>
    </select>
    <?php

    if (isset($description)) {
        foreach ($description as $paragraph) {
            ?>
            <p class="description">
                <?php echo $paragraph; ?>
            </p>
            <?php
        }
    }
}

// Field: Production Domain
function tot_field_prod_domain_cb($args)
{
    tot_standard_text_field($args, array(
        'Example: ' . $_SERVER['HTTP_HOST'],
        'This site will run in live mode, test and development sites will automatically run in "Test Mode."'
    ));
}

// Field: License Key
function tot_field_license_key_cb($args)
{
    $options = get_option('tot_options');
    $additional_text = null;
    $hint = null;
    $license_field_value = tot_get_setting_license_key();
    if (!tot_option_has_a_value($license_field_value)) {
        $additional_text = array('<a href="' . tot_production_origin() . '/hq/register/'.tot_frontend_link_parameters("get-license", ['send_plugins' => true]).'" class="button" target="_blank">Get a license key</a>');
    } else if (!is_wp_error(tot_get_keys())) {
        $hint = array(
            '<a href="' . tot_production_origin() . '/hq/wordpressIntegrationDetails/'.tot_frontend_link_parameters("view-license").'" target="_blank">View license details</a>'
        );
    }

    tot_textarea_field($args, $hint, $additional_text);
}

// Field: Confirm New User Emails
function tot_field_debug_mode_cb($args)
{
    $description = 'Displays messages inline within pages.
        <br />
        <div style="font-size:80%; line-height:140%; margin-top:1em;">
            This should not be enabled on the live site unless absolutely necessary as site visitors users will see error details.
        </div>';

    // time left before debug mode is expired
    $time_left = tot_get_debug_remain_time();
    $args['checked'] = tot_debug_mode();
    if($time_left){
        $description.= '<div style="font-size:80%; color: #17a2b8; line-height:140%; margin-top:1em;">
                        Remaining time before automatically disable debug mode: ' . $time_left.
                        '</div>';
    }
    tot_checkbox_field($args, array($description));
}

// Field: Confirm Email Success Redirect
function tot_field_confirm_email_success_redirect_cb($args) {
    $options_array = [];
    $wp_pages = get_pages($arg = array('post_type' => 'page'));

    array_push($options_array, array(
        'label' => '/',
        'value' => '/'
    ));

    foreach ($wp_pages as $wp_page) {
        $path = str_replace(get_site_url(), '', get_page_link($wp_page->ID));
        array_push($options_array, array(
            'label' => $path,
            'value' => $path
        ));
    }

    tot_dropdown_field($args, $options_array, array(
        'Example: /email-thank-you',
        'Once the user confirms their email address, they will be redirected to this page on your site. Your website domain will be added to the begining of this url'
    ));
}

// Field: Debug Mode
function tot_field_confirm_new_user_emails_cb($args)
{
    tot_checkbox_field($args, array(
        'Send new users an email to confirm they own the email address provided.'
    ));
}

function tot_field_disable_load_home_cb($args)
{
    tot_checkbox_field($args, array(
        'Disable Token of Trust on the home page. Improves home page load time and can improve SEO.
        <div style="font-size:80%; line-height:140%; margin-top:1em;">Only check this when Token of Trust gates and components are <i>not</i> used on the home page.</div>'
    ));
}

function tot_field_disable_generating_faq_cb($args)
{
	tot_checkbox_field($args, array(
		'Disable Token of Trust FAQ page
        <div style="font-size:80%; line-height:140%; margin-top:1em;">It will change the status of Token of Trust FAQ page as Draft</div>'
	));
}

function tot_field_approval_cb($args)
{
    tot_checkbox_field($args, array(
        'Approve users within WordPress admin pages.'
    ));
}

function tot_shared_role_dropdown_field($args, $hint)
{
    global $wp_roles;

    $options_array = array();
    $roles = $wp_roles->roles;

    array_push($options_array, array(
            'label' => 'Do not change role',
            'value' => '')
    );

    foreach (array_keys($roles) as $key) {
        array_push($options_array, array('label' => $roles[$key]['name'], 'value' => $key));
    }

    tot_dropdown_field($args, $options_array, array($hint));
}

function tot_field_approved_role_cb($args)
{
    tot_shared_role_dropdown_field($args, 'The role to assign approved users, all other roles will be removed from the user. Administrator\'s role(s) will not be changed.');
}

function tot_field_rejected_role_cb($args)
{
    tot_shared_role_dropdown_field($args, 'The role to assign rejected users, all other roles will be removed from the user. Administrator\'s  role(s) will not be changed.');
}

function tot_field_pending_role_cb($args)
{
    tot_shared_role_dropdown_field($args, 'The role to assign pending users, all other roles will be removed from the user. Administrator\'s  role(s) will not be changed.');
}

function tot_field_auto_identify_cb($args)
{
    tot_checkbox_field($args, array(
        "Enable this feature to pass user data to Token of Trust about your WordPress users prior to accepting Token of Trust's End-User Terms of Service and Privacy Policy.
        <div style='font-size:80%; line-height:140%; margin-top:1em;'>This feature requires an active Service Agreement with Token of Trust and an opt-in statement in your user registration form. <a href=\"mailto:privacy@tokenoftrust.com?Subject=DPA Request\">Request a Data Processing Addendum</a>.</div>"
    ));
}

function tot_field_verification_gates_enabled_cb($args) {
    tot_checkbox_field($args, array(
        "Allows you to use Token of Trust to protect any page. Specify which ones via the options that will appear below."
    ));
}

function tot_field_age_gate_enabled_cb($args) {
    tot_checkbox_field($args, array(
        "Will display an age gate for modal for all first time users. A modal will display the minimum age to visit and discloses that there will be verification later on. Once the modal is accepted, it won't be displayed again for 8 hours.
        <div style='font-size:80%; line-height:140%; margin-top:1em;'>Before enabling, make sure you have a minimum age set. This can be found in your <a href=" . tot_production_origin() . "/hq/".tot_frontend_link_parameters("check-minimum-age")."' target='_blank'>Token of Trust dashboard</a>.",
    ));
}

function tot_field_default_setting_verification_on_pages_cb($args){

    // value => description
    $args['radio_btns'] = [
        'exclusive' => 'Verification is required on all pages EXCEPT for the pages indicated below.',
        'inclusive' => 'Only require Verification for the pages indicated below.'
    ];
    $args['default_value'] = "exclusive";

    tot_radio_fields($args);
}


function tot_field_bypass_verification_for_pages_cb($args){
    tot_multiselect_field($args, array(
        "When no pages are specified all pages will be required to get verified."
    ));
}

function tot_field_require_verification_for_pages_cb($args) {
    tot_multiselect_field($args, array(
        "When no pages are specified above verification will not be required on any pages."
    ));
}

function tot_field_roles_pass_checkout_verification_cb($args){
    tot_multiselect_field($args, array(
        "When no roles are specified all roles will be required to get verified."
    ));
}
