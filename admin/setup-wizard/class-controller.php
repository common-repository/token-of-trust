<?php

namespace TOT\Admin\SetupWizard;

use TOT\Settings;
use TOT\tot_debugger;

class Controller extends \WP_REST_Controller
{
    protected $namespace;
    protected $rest_base;

    public function __construct()
    {
        $this->namespace = 'tot/sz/v1';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/options', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_options'),
                'permission_callback' => array($this, 'wz_permission_cb')
            ),
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_options'),
                'permission_callback' => array($this, 'wz_permission_cb')
            )
        ));

        register_rest_route($this->namespace, '/where-user-get-verified-page/', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_where_user_get_verified_page'),
            'permission_callback' => array($this, 'wz_permission_cb')
        ));

        register_rest_route($this->namespace, '/activate-where-user-get-verified-card/' . '(?P<id>[a-zA-Z0-9_-]+)', array(
            'methods' => \WP_REST_Server::EDITABLE,
            'callback' => array($this, 'set_where_user_get_verified_card'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function ($param, $request, $key) {
                        return sanitize_title($param) == $param;
                    }
                ),
            )
        ));

        register_rest_route($this->namespace, '/toggle-verification-pages/' . '(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'toggle_verification_page'),
                'permission_callback' => array($this, 'wz_permission_cb'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function ($param, $request, $key) {
                            return is_numeric($param);
                        }
                    ),
                ),
            )
        ));

		register_rest_route($this->namespace, '/age-settings', array(
			array(
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => array($this, 'update_age_settings'),
				'args' => array(
					'min_age' => array(
						'validate_callback' => function ($param) {
							return is_numeric($param);
						}
					)
				)
			)
		));
    }

    /**
     * @TODO make the callback permission function
     * @return true
     */
    public function wz_permission_cb()
    {
        return true;
    }

    public function get_options()
    {
        $tot_options = get_option('tot_options');
        $keys = tot_get_keys();
        $has_license = isset($tot_options['tot_field_license_key']) && $tot_options['tot_field_license_key'];
        $options = [
            'has_license' => $has_license,
            'is_license_valid' => $has_license && is_array($keys) && (tot_keys_work('live') || tot_keys_work('test')),
            'is_checkout_verification_activated' => $tot_options['tot_field_checkout_require'] ?? false,
            'use_case' => is_array($keys) && isset($keys['verificationUseCase']) ? $keys['verificationUseCase'] : 'unknown',
            'is_page_verification_activated' => $tot_options['tot_field_verification_gates_enabled'] ?? false,
            'is_manual_verification_activated' => $tot_options['tot_field_is_manual_verification_activated'] ?? false,
            'pages' => $this->get_pages_with_verification_status(),
            'minimum_age' => $tot_options['tot_field_min_age'] ?? null,
        ];

        return rest_ensure_response(array_merge($options, $this->get_trial_data_from_keys($keys)));
    }
    
    public function update_options(\WP_REST_Request $request)
    {
        $data = [];
        $body = json_decode($request->get_body());
        if (!is_object($body) && isset($body->use_case)) {
            return new WP_Error('invalid_data', __('Cannot update the verification status.'), array('status' => 400));
        }
        
        // map
        $options = [
            'tot_field_checkout_require' => (bool) $body->is_checkout_verification_activated,
            'tot_field_verification_gates_enabled' => (bool) $body->is_page_verification_activated,
            'tot_field_is_manual_verification_activated' => (bool) $body->is_manual_verification_activated
        ];

        foreach ($options as $key => $val) {
            Settings::set_setting($key, $val);
        }

        $data['msg'] = 'success';
        return rest_ensure_response($data);
    }

    public function toggle_verification_page(\WP_REST_Request $request) {
        $page_id = (int) $request->get_param('id');
        $page = get_post($page_id);
        $page_slug = $page->post_name;
        
        $requiredVerification = Settings::get_setting('tot_field_require_verification_for_pages') ?: [];

        if (($key = array_search($page_slug, $requiredVerification)) !== false) {
            unset($requiredVerification[$key]);
        } else {
            $requiredVerification[] = $page_slug;
        }
        Settings::set_setting('tot_field_require_verification_for_pages', $requiredVerification);

        $data = [];
        $data['msg'] = 'success';
        return rest_ensure_response($data);
    }

    private function get_trial_data_from_keys($keys)
    {
        $now = time();

        $trial_data = [
            'trial_days_remaining' => 0,
            'trial_hours_remaining' => 0,
            'trial_minutes_remaining' => 0,
            'has_card_on_file' => false,
            'has_extended_trial' => false
        ];

        if (is_wp_error($keys) || !is_array($keys)) {
            return $trial_data;
        }

        // free trial
        $freeTrialStartTimestamp = $keys['freeTrialStartTimestamp'] ? floor($keys['freeTrialStartTimestamp'] / 1000) : 0;
        $freeTrialEndTimestamp = $keys['freeTrialEndTimestamp'] ? floor($keys['freeTrialEndTimestamp'] / 1000) : 0;
        $freeTrialDiff = $freeTrialEndTimestamp - $now;
        if ($now < $freeTrialEndTimestamp) {
            $trial_data = [
                'trial_days_remaining' => floor($freeTrialDiff / DAY_IN_SECONDS),
                'trial_hours_remaining' => floor(($freeTrialDiff % DAY_IN_SECONDS) / HOUR_IN_SECONDS),
                'trial_minutes_remaining' => floor(($freeTrialDiff % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS)
            ];
        }

        // live
        $trial_data['has_card_on_file'] = isset($keys['goLiveTimestamp']) && $keys['goLiveTimestamp'] > 0;

        // Assume they've extended their trial at least once if the amount of total trial time is > 6 days.
        $trial_data['has_extended_trial'] = isset($keys['goLiveTimestamp']) && $keys['goLiveTimestamp'] > 0;

        return $trial_data;
    }
    
    
    public function get_where_user_get_verified_page()
    {
        $data = [
            'is_woocommerce_active' => class_exists('woocommerce'),
            'selected_where_users_get_verified_card' => Settings::get_setting('tot_field_where_user_get_verified_card')
        ];
        return rest_ensure_response($data);
    }
    
    public function set_where_user_get_verified_card(\WP_REST_Request $request) {
        $card_id = $this->sanitize_slug( $request->get_param('id'));
        Settings::set_setting('tot_field_where_user_get_verified_card', $card_id);

        /**
         * @todo create new page to handle woocommerce configurations
         * for now, we will set the recommended
         */

        if ($card_id === 'checkout')
        {
            Settings::set_setting('tot_field_woo_enable_verification_before_payment', 1);

            // disable page verification
            Settings::set_setting('tot_field_verification_gates_enabled', 0);
        } elseif ($card_id == 'create-account') {
            Settings::set_setting('tot_field_default_setting_verification_on_pages', 'inclusive');

            // disable checkout verification
            Settings::set_setting('tot_field_checkout_require', 0);
        } else {
            // disable checkout verification
            Settings::set_setting('tot_field_checkout_require', 0);

            // disable page verification
            Settings::set_setting('tot_field_verification_gates_enabled', 0);
        }
        
        $data = [];
        $data['msg'] = 'success';
        return rest_ensure_response($data);
    }

    public function get_pages_with_verification_status()
    {
        $requiredVerification = Settings::get_setting('tot_field_require_verification_for_pages');
        $pages = [];

        $query = new \WP_Query([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        foreach ($query->posts as $page) {
            $is_checkout = function_exists('wc_get_page_id') && wc_get_page_id('checkout') == $page->ID;
            $is_verification_page = $page->post_name === 'verification-required';
            
            $pages[] = [
                'id' => $page->ID, 
                'slug' => $page->post_name,
                'label' => $page->post_title,
                'checked' => !$is_checkout && !$is_verification_page
                    && $requiredVerification && in_array($page->post_name, $requiredVerification),
                'disabled' => $is_verification_page || $is_checkout,
                'is_checkout' => $is_checkout // to display custom text in front-end
            ];
        }

        return $pages;
    }

	public function update_age_settings(\WP_REST_Request $request)
	{
		$minimum_age = (int) $request->get_param('minimum_age');
		$minimum_age && Settings::set_setting('tot_field_min_age', $minimum_age);

		$api_product = new \TOT\API_Settings();
		$response = $api_product->sendRaw();
		$responseBody = isset($response['body']) ? json_decode($response['body']) : '';
		tot_debugger::inst()->log('The response of syncing settings using API_Settings',
			$responseBody);

		if (
			is_wp_error($response)
			|| (is_object($responseBody)
				&& isset($responseBody->content->type)
				&& $responseBody->content->type == 'error')
		) {
			$data = new \WP_Error('Failed to sync settings');
		} else {
			$data = ['msg' => 'success'];
		}

		return rest_ensure_response($data);
	}
}