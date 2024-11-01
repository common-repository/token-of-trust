<?php

use TOT\Settings;
use TOT\User;

add_action('template_redirect', 'tot_verification_gates', 99);
add_action('wp_footer', 'tot_debug_verification_gates', 2);

function tot_verification_gates()
{
    // echo '<pre>tot_verification_gates</pre>';

    tot_check_for_query_cookie('verification_gates_enabled');
    tot_check_verification_required();
}
add_action('widgets_init', tot_add_query_params_closure('verification_gates_enabled'));

/**
 * Getting the verification requirement for the current page
 *
 * Use Filter 'tot_get_verification_requirement' to override
 * Use 'tot_build_verification_requirement' to build a requirement when verification is required.
 *
 * @return array
 */
function tot_get_verification_requirement()
{
    /**
     * get_permalink() is only really useful for single pages and posts, and only works inside the loop.
     * So it will not work properly with shop page
     *
     * another problem will appear if the website using a Permalink Settings other than Post name
     */
    $slug = "";
    $current_page = get_queried_object();
    if (isset($current_page->post_name)){
        $slug = $current_page->post_name;
    } else if ($current_page instanceof WP_Post_Type){
        // like shop page
        $slug = $current_page->has_archive;
    }


    // These pages will have no verification by default
    if ($slug == tot_verification_required_default_slug() ||
            $slug == basename(tot_verification_required_default_url()) ||
            tot_is_page_white_listed()) {
        return tot_build_verification_requirement('none');
    }

    /**
     * Determining what pages will require
     * or pass verification using setting options
     *
     * @since 1.6.9
     */
    $options = get_option('tot_options');
    $default_setting_verification = isset($options['tot_field_default_setting_verification_on_pages'])
            ? $options['tot_field_default_setting_verification_on_pages'] : 'exclusive';
    $slugs = tot_get_verification_pages($default_setting_verification, $options);

    if (
            ( $default_setting_verification == 'exclusive' && !in_array($slug, $slugs) ) ||
            ( $default_setting_verification == 'inclusive' && in_array($slug, $slugs))
    ) {
        return apply_filters('tot_get_verification_requirement', tot_build_verification_requirement('redirect'));
    }
    return apply_filters('tot_get_verification_requirement', tot_build_verification_requirement('none'));
}

/**
 * Returning The pages that will pass verification or required for verification
 * depending on the value of $default_setting_verification
 *
 * @param String $default_setting_verification. either 'exclusive' or 'inclusive'
 * @param Array $options
 * @return Array
 */
function tot_get_verification_pages($default_setting_verification, $options){
    $slugs = [];
    if ($default_setting_verification == 'exclusive'
            && isset($options['tot_field_bypass_verification_for_pages'])
            && is_array($options['tot_field_bypass_verification_for_pages'])) {
        $slugs = $options['tot_field_bypass_verification_for_pages'];
    } else if($default_setting_verification == 'inclusive'
            && isset($options['tot_field_require_verification_for_pages'])
            && is_array($options['tot_field_require_verification_for_pages'])) {
        $slugs = $options['tot_field_require_verification_for_pages'];
    }

    return $slugs;
}


function tot_user_has_exempted_role(){
    $user = wp_get_current_user();
    $user_roles = array_map(function ($role) {
        return strtolower(wp_roles()->role_names[$role]);
    }, $user->roles);
    $bypass_roles = Settings::get_setting('tot_field_roles_pass_checkout_verification');

    // Check if the user has at least one role that will allow him to bypass tot verification
    $userHasExemptedRole = false;
    if(!empty($bypass_roles) && !empty($user_roles) && !empty(array_intersect($bypass_roles, $user_roles))){
        $userHasExemptedRole = true;
    }

    return $userHasExemptedRole;
}

// Example Override: tot_override_get_verification_requirements
//
//add_filter('tot_get_verification_requirement', 'tot_override_get_verification_requirements');
//function tot_override_get_verification_requirements ($current) {
//    if (is_page('some-page')) {
//        return tot_build_verification_requirement('redirect', array('redirectUrl' => 'someurl'));
//    }
//    return $current;
//}

/**
 * Only 'redirect' is supported today. In the future we will add others.
 *
 * @param $action - 'redirect' will cause wordpress to redirect to a verification page. Anything else will be ignored.
 * @return array
 */
function tot_build_verification_requirement($action = 'none', $args = array())
{
    $requirements = array('action' => $action);
    switch ($action) {
        case 'none':
        default:
            return new Null_Verification($args);
        case 'redirect':
            return new Redirect_Verification($args);
    }
}

class Verification_Requirement
{
    protected $args;
    protected $name;

    public function __construct($name, $args = array())
    {
        $this->args = $args;
        $this->name = $name;
    }

    public function executeAction()
    {
    }

    public function is_met()
    {
        $wpUserid = isset($this->args['wpUserid']) ? $this->args['wpUserid'] : get_current_user_id();
        $default_is_met = false;
        $reasons = null;
        $gates = null;
        if ($wpUserid) {
            $tot_user = new User($wpUserid);
            $reasons = $tot_user->get_reputation_reasons(tot_user_id($wpUserid));
            $gates = $tot_user->get_reputation_gates(tot_user_id($wpUserid));
            $hasGates = isset($gates) && !is_wp_error($gates);
            $default_is_met = $hasGates && $gates->is_positive('isCleared');
         }

        $is_met = apply_filters('tot_verification_gates_is_met', $default_is_met, $wpUserid, $reasons, $tot_user ?? null);

        return $is_met;
    }

    public function name()
    {
        return $this->name;
    }
}

class Null_Verification extends Verification_Requirement
{
    public function __construct($args)
    {
        parent::__construct('none', $args);
    }

    public function is_met()
    {
        return true;
    }
}

class Redirect_Verification extends Verification_Requirement
{

    public function __construct($args)
    {
        parent::__construct('redirect', $args);
    }

    public function executeAction()
    {
        $redirectUrl = $this->get_redirect_url();
        if (isset($redirectUrl) && $redirectUrl) {
            wp_redirect($redirectUrl);
        } else {
            wp_redirect(home_url('/?tot_verification_is_required=true'));
        }
        exit();
    }

    public function get_redirect_url()
    {
        return isset($this->args['redirectUrl']) ? $this->args['redirectUrl'] : tot_verification_required_default_url();
    }
}

/**
 * By default home page and verification page are white listed - others can be
 * added by overriding the filter tot_is_page_white_listed.
 * @return mixed
 */
function tot_is_page_white_listed()
{
    return apply_filters('tot_is_page_white_listed', is_home() || is_page(tot_verification_required_default_url()));
}

// Example Override: tot_override_get_verification_requirements
//
//add_filter('tot_is_page_white_listed', 'tot_override_is_page_white_listed');
//function tot_override_is_page_white_listed ($current) {
//    if (is_page('some-page')) {  // or you can say NOT some-pages...
//        return true;
//    }
//    return $current;
//}

/**
 * By default home page and verification page are white listed - others can be
 * added by overriding the filter tot_is_page_white_listed.
 * @return mixed
 */
function tot_is_verification_enabled()
{
    $userHasExemptedRole = tot_user_has_exempted_role();

    return tot_live_or_in_trial()
		&& !$userHasExemptedRole
		&& Settings::get_setting('verification_gates_enabled');
}

function tot_check_verification_required()
{
    // Is verification enabled?
    if (tot_is_verification_enabled()) {
        // echo '<pre>tot_check_verification_required()</pre>';

        // Does this page require verification?
        $verification_requirement = tot_get_verification_requirement();
        if (empty($verification_requirement)) {
            // echo '<pre>no verification_requirement</pre>';
        } else {
            $is_met = $verification_requirement->is_met();
            if (empty($is_met)) {
	            \TOT\tot_debugger::inst()->log("VerificationGates - executing action for => ", $verification_requirement);
                $verification_requirement->executeAction();
            } else {
                // echo '<pre>verification_requirement is_met</pre>';
            }
        }
    }
}


function tot_debug_verification_gates()
{
    if (tot_debug_mode()) {
//		$tot_debugger = \TOT\tot_debugger::inst();
//		$tot_debugger->register_new_operation(__FUNCTION__);
        // Is verification enabled?
        if (tot_is_verification_enabled()) {
//	        $tot_debugger->add_part_to_operation('', "VerificationGates", " Enabled");
            $slug = basename(get_permalink());
//	        $tot_debugger->add_part_to_operation('', "VerificationGates", ' Page Slug = "' . $slug . '"');

            // Does this page require verification?
            $verification_requirement = tot_get_verification_requirement();
            if (empty($verification_requirement)) {
//	            $tot_debugger->add_part_to_operation('', "VerificationGates", " No gate requirement returned for this page.");
            } else {
                $is_met = $verification_requirement->is_met();
//                if (empty($is_met)) {
//	                $tot_debugger->add_part_to_operation('', "VerificationGates", "Verification Requirement not yet met - executed action for requirement : " . $verification_requirement->name());
//                } else {
//	                $tot_debugger->add_part_to_operation('', "VerificationGates", "Verification Requirement already met - taking no action.");
//                }
            }
        } else {
//	        $tot_debugger->add_part_to_operation('', "VerificationGates", "Disabled");
            $slug = basename(get_permalink());
//	        $tot_debugger->add_part_to_operation('', "VerificationGates", 'Page Slug = "' . $slug . '"');
        }

//	    $tot_debugger->log_operation(__FUNCTION__);
    }
}

// Example Override: tot_override_get_verification_requirements
//
//add_filter('tot_verification_required_default_url', 'tot_override_verification_required_default_url');
//function tot_override_verification_required_default_url($current)
//{
//    return 'alternative-verification-page';
//}

/**
 * Returning the default slug of verification required page
 * Use Filter 'tot_verification_required_default_slug' to override
 *
 * @return String
 */
function tot_verification_required_default_slug(){
    $default_slug = 'verification-required';
    return apply_filters('tot_verification_required_default_slug', $default_slug);
}
function tot_verification_required_default_url()
{
    $verification_page = get_page_by_path(tot_verification_required_default_slug());

    return apply_filters('tot_verification_required_default_url', get_permalink($verification_page->ID));
}
