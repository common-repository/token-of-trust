<?php

namespace TOT\Admin;
use TOT\tot_debugger;
use TOT\Settings;

class Quickstart
{

	private $quickstart_obj;

	public function __construct()
	{
	}

	public function register_wordpress_hooks()
	{
		add_action('init', function () {
			// check if the current user is an admin
			if (current_user_can('manage_options')) {
				add_action('wp_ajax_tot_quickstart_settings', array($this, 'handle_request'));
			}
		});
	}

	public function handle_request()
	{
		try {
			$this->do_handle_request();
			echo json_encode(['status' => 'success']);
		} catch (\Exception $e) {
			echo json_encode(['status' => 'failed', 'message' => $e->getMessage()]);
		}
		wp_die();
	}

	private function do_handle_request()
	{
		$this->quickstart_obj = json_decode(wp_unslash($_POST['quickstartObj']));
		$mapped_options = $this->mapping_to_options();
		$this->update_mapped_options($mapped_options);
		$this->syncSettings();
	}

	private function mapping_to_options()
	{
		$quickstart_obj = $this->quickstart_obj;
        $is_page_verification_activated = isset($quickstart_obj->is_page_verification_activated)
            && $quickstart_obj->is_page_verification_activated === true;
        $pages_required_verifications = $this->get_pages_required_verifications();
        
		return [
			'tot_field_checkout_require' => isset($quickstart_obj->is_activated) && $quickstart_obj->is_activated === true,
			'tot_field_verification_gates_enabled' => $is_page_verification_activated,
			'tot_field_require_verification_for_pages' => $pages_required_verifications,
            'tot_field_default_setting_verification_on_pages' => empty($pages_required_verifications)
                ? 'exclusive'
                : 'inclusive',
			'tot_field_min_age' => isset($quickstart_obj->minimum_age)
				&& is_numeric($quickstart_obj->minimum_age) ? $quickstart_obj->minimum_age : null
		];
	}

	/**
	 * @return array
	 */
	private function get_pages_required_verifications()
	{
		$quickstart_obj = $this->quickstart_obj;
		return isset($quickstart_obj->pages_required_verification)
		&& is_array($quickstart_obj->pages_required_verification)
			? tot_sanitize_arr($quickstart_obj->pages_required_verification)
			: [];
	}

	/**
	 * @param $mapped_options
	 * @return void
	 * @throws \Exception
	 */
	private function update_mapped_options($mapped_options)
	{
		$tot_options = array_merge(get_option('tot_options'), $mapped_options);
		if (!update_option('tot_options', $tot_options, true)) {
			throw new \Exception('Failed to update options');
		}
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	private function syncSettings()
	{
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
			throw new \Exception('Failed to sync settings');
		}
	}
}