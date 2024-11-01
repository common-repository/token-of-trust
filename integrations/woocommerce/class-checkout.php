<?php
/**
 *
 * WooCommerce Integration
 *
 * References
 *   - WoCommerce checkout fields
 *     https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
 *
 */

namespace TOT\Integrations\WooCommerce;

use TOT\API_Response;
use TOT\Settings;
use TOT\API_Request;
use TOT\tot_debugger;
use TOT\User;
use WP_Error;
use TOT\Reasons;

class Checkout_Enabled extends Checkout
{
    private static $inst = null;
    public $verify_result = null;
    public $order_reputation_result = null;
    public $email_reputation_result = null;

    /**
     * Singleton pattern
     * @return self
     */
    public static function getInstance()
    {
        if (is_null(self::$inst)) {
            self::$inst = new self();
        }
        return self::$inst;
    }
    private function __construct()
    {
        parent::__construct();
    }

    public function get_name()
    {
        return "Checkout_Enabled";
    }

    public function initialize()
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        /*
         * References:
         * - Checkout fields: https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
         * - Checkout hooks: https://businessbloomer.com/woocommerce-visual-hook-guide-checkout-page/
         * - Order methods: https://businessbloomer.com/woocommerce-easily-get-order-info-total-items-etc-from-order-object/
         * - WooCommerce hooks: https://premmerce.com/woocommerce-hooks-guide/
         * - WooCommerce (Docs) hooks: https://docs.woocommerce.com/wc-apidocs/hook-docs.html
         */

        // Shared across screens
        add_action('wp_enqueue_scripts', array($this->hookHandler, 'enqueue_javascript'));
        add_filter('wc_order_statuses', array($this->hookHandler, 'add_awaiting_verification_to_order_statuses'));

        // Validation before order is complete
        add_action('woocommerce_review_order_before_submit', array($this->hookHandler, 'checkout_field_verification_acknowledgement'));
        add_action('woocommerce_checkout_process', array($this->hookHandler, 'checkout_field_validation'));

        // Validation after order is processed
//        add_action('woocommerce_after_order_object_save', array($this->hookHandler, 'process_order'), 1, 1);
        add_action('woocommerce_checkout_update_order_meta', array($this->hookHandler, 'process_order'), 1, 1);
        add_action('woocommerce_checkout_order_created', array($this->hookHandler, 'process_order'), 1, 1);
        add_action('woocommerce_checkout_order_processed', array($this->hookHandler, 'process_order'), 1, 1);
        add_action('woocommerce_order_status_changed', array($this->hookHandler, 'post_payment_status_check'), 10, 2);

        add_action('woocommerce_before_template_part', array($this->hookHandler, 'handle_order_complete_notice_and_modal'), 99, 4);
        add_filter('body_class', array($this->hookHandler, 'add_body_class'));

        // Verification results processing
        add_action('tot_webhook_success', array($this->hookHandler, 'default_autoprocess_webhook'), 10, 3);
        add_action('template_redirect', array($this->hookHandler, 'order_lookup_redirect'));


        /**
         * Charitable donation
         */
        $charitySetting = Settings::get_setting('tot_field_woo_charity');
        if($charitySetting && $charitySetting !== 'deactivated') {
            add_action('woocommerce_checkout_update_order_review', array($this->hookHandler, 'donation_update_order_review'));
            add_action('woocommerce_review_order_before_order_total', array($this->hookHandler, 'add_donation_checkbox_before_total'));
            add_action('woocommerce_before_calculate_totals', array($this->hookHandler, 'attach_donation_to_cart_before_creating_order'));
            add_action('woocommerce_checkout_order_processed', array($this->hookHandler, 'store_donation_info_to_order'), 99, 1);
        }
        $this->register_awaiting_verification_order_status();
    }

    public function donation_update_order_review ($posted_data) {

        // to create default value behaviour
        if (strpos($posted_data, 'tot_charity_checkbox=1') !== false ) {
            $_POST['tot_charity_checkbox_temp'] = true;
            WC()->session->set('is_charity_enabled_before', 'yes');
        } else {
            WC()->session->set('is_charity_enabled_before', 'no');
        }

    }

    public function add_donation_checkbox_before_total() {

        $wc = WC();
        $cart = $wc->cart;
        $checkout = $wc->checkout();
        $total = $cart->get_total('');
        $is_charity_enabled_before = WC()->session->get('is_charity_enabled_before');
        $charitySetting = Settings::get_setting('tot_field_woo_charity');
        $donation = $charitySetting == 'roundup' ? round((ceil($total) - $total), 2) : (int) $charitySetting;

        // opt-in/out for first time rendering
        $opt = Settings::get_setting('tot_field_woo_charity_opt') ?: 'in';

        if ($donation) {
            $checkbox = $checkout->get_value('tot_charity_checkbox_temp')
                || (!$is_charity_enabled_before && $opt == 'out') // first time rendering
                || $is_charity_enabled_before == 'yes';
            if ($checkbox) {
                $cart->add_fee(__('Charitable donation', $this->text_domain), $donation);
                new \WC_Cart_Totals( $cart );
            }

            $label = '$' . $donation;
            $charityName = Settings::get_setting('tot_field_woo_charity_name');
            $charityUrl = Settings::get_setting('tot_field_woo_charity_url');
            if ($charityName) {
                $label = sprintf(
                    /* translators: %s: the donation value */
                    esc_html__( 'Please add a donation of %s to my order for', $this->text_domain ),
                    $label
                );

                if ($charityUrl) {
                    $label .= ' <a target="_blank" href="' . $charityUrl . '">' . $charityName . '</a>.';
                } else {
                    $label .= ' ' . $charityName . '.';
                }
            }
            ?>
            <tr class="order-total">
                <th><?php _e('Charitable donation', $this->text_domain); ?></th>
                <td>
                    <?php
                    woocommerce_form_field('tot_charity_checkbox', array(
                        'type' => 'checkbox',
                        'class' => array('tot_charity_checkbox'),
                        'label' => $label,
                        'required' => false
                    ), $checkbox);
                    ?>
                </td>
            </tr>
            <?php
        }
    }

    public function attach_donation_to_cart_before_creating_order($cart) {

        $tot_charity_checkbox = $_POST['tot_charity_checkbox'] ?? '';
        if (!$tot_charity_checkbox) return;

        /** @var \WC_Cart $cart */

        // calculate totals
        new \WC_Cart_Totals( $cart );

        $total = $cart->get_total('');

        $charitySetting = Settings::get_setting('tot_field_woo_charity');
        $donation = $charitySetting == 'roundup' ? round((ceil($total) - $total), 2) : (int) $charitySetting;

        if ($donation) {

            $label = __('Charitable donation', $this->text_domain);
            $charityName = Settings::get_setting('tot_field_woo_charity_name');
            if ($charityName) {
                $label = sprintf(
                    esc_html__( 'Charitable donation for', $this->text_domain ),
                    $label
                );

                $label .= ' ' . $charityName;

            }

            $cart->add_fee($label, $donation);
        }
    }

    public function store_donation_info_to_order($order)
    {
        $tot_charity_checkbox = $_POST['tot_charity_checkbox'] ?? '';
        if (!$tot_charity_checkbox) return;

        $order = tot_wc_get_order($order);
        $order->get_fees();

        $label = __('Charitable donation', $this->text_domain);
        $charityName = Settings::get_setting('tot_field_woo_charity_name');
        $charityUrl = Settings::get_setting('tot_field_woo_charity_url');
        if ($charityName) {
            $label = sprintf(
                esc_html__( 'Charitable donation for', $this->text_domain ),
                $label
            );

            $label .= ' ' . $charityName;
        }

        $donation = 0;
        foreach ($order->get_fees() as $fee) {
            if ($fee->get_name() == $label) {
                $donation = $fee->get_total();
                break;
            }
        }

        if ($donation) {
            $order->update_meta_data('tot_charity_name', $charityName ?: 'not specified');
            $order->update_meta_data( 'tot_charity_url', $charityUrl ?: 'not specified');
            $order->update_meta_data('tot_donation_value', $donation);
            $order->save();
        }
    }

    public function add_body_class($classes)
    {

        if (function_exists('is_order_received_page') && is_order_received_page()) {

            $wcOrderKeyFromQuery = getWcOrderKeyFromQuery();
            if (!isset($wcOrderKeyFromQuery)) {
                return $classes;
            }

            $order_key = sanitize_text_field($wcOrderKeyFromQuery);
            $order_id = wc_get_order_id_by_order_key($order_key);
            $order = tot_wc_get_order($order_id);

            $require_verification_at_checkout = $this->tot_is_verification_on_checkout_enabled();

            if (!$require_verification_at_checkout || $this->should_display_receipt($order)) {
                return $classes;
            }

            array_push($classes, 'tot-hide-receipt');
        }

        return $classes;
    }

    public function handle_order_complete_notice_and_modal($template_name, $template_path, $located, $args)
    {
        // This could be fragile...
        if (('checkout/thankyou.php' !== $template_name)) {
            return;
        }
		$tot_debugger = tot_debugger::inst();
		$tot_debugger->register_new_operation(__FUNCTION__);

		$tot_debugger->add_part_to_operation(__FUNCTION__, 'Running');

        // TODO - should we just use WC->getOrder() here?
        $order = isset($args['order']) ? $args['order'] : '';

        if (!empty($order)) {
            $order = tot_wc_get_order($order);
        } else {
            $order_id = Checkout::get_current_wc_order_id();
            $order = tot_wc_get_order($order_id);
        }

        // make sure order is processed
        $tot_debugger->add_part_to_operation(__FUNCTION__, 'Run process_order in the thank you page');
        $this->process_order($order);

        if (empty($order)) {
	        $tot_debugger->add_part_to_operation(__FUNCTION__, 'empty_order', 'Order is unavailable!!');
	        $tot_debugger->log_operation(__FUNCTION__);
            return;
        }

		if ($this->is_order_placed_before_verification_enabled($order)) {
			$tot_debugger->add_part_to_operation(__FUNCTION__, 'order_placed_before_verification_enabled',
				'Order is placed before the verification is enabled');
			$tot_debugger->log_operation(__FUNCTION__);
			return;
		}

        $order_id = $order->get_id();

        $tot_debugger->add_part_to_operation(__FUNCTION__, 'Run process_order in the thank you page, with error_callback function');
        $this->process_order($order_id, false, array($this, 'handle_verify_person_api_error'));

        $should_receipt_display_verification_prompt = $this->should_receipt_display_verification_prompt($order);

        if (!$should_receipt_display_verification_prompt) {
	        $tot_debugger->add_part_to_operation(__FUNCTION__, 'should_receipt_display_verification_prompt', 'false');
	        $tot_debugger->log_operation(__FUNCTION__);
	        return;
        }

		$tot_debugger->log_operation(__FUNCTION__);
        // We're displaying the popup so there's more to be done.
        $this->add_user_verification_notices($order);
    }

    public function add_user_verification_notices($order)
    {
	    $tot_debugger = tot_debugger::inst();
		$tot_debugger->register_new_operation(__FUNCTION__);

        if ($this->is_order_preapproved($order)) {
			$tot_debugger->add_part_to_operation(__FUNCTION__, 'add_user_verification_notices', 'tot_order_is_whitelisted_message');
            $orderIsWhiteListed = apply_filters('tot_order_is_whitelisted_message', __('Thank you for being a valued customer! Your order has been submitted.', $this->text_domain));
            wc_add_notice(
                '<span class="tot-wc-order-validation">'
                . $orderIsWhiteListed
                . '</span>',
                'notice'
            );
            wc_print_notices();
            return;
        } else if ($this->is_verification_complete($order)) {
	        $tot_debugger->add_part_to_operation(__FUNCTION__, 'add_user_verification_notices', 'tot_order_is_submitted');
	        $orderIsVerified = apply_filters('tot_order_is_completed', 'Your verification is complete.');
            wc_add_notice(
                '<span class="tot-wc-order-validation">'
                . __($orderIsVerified, $this->text_domain)
                . '</span>',
                'notice'
            );
            wc_print_notices();
            return;
        } else if ($this->is_verification_info_submitted($order)) {
	        $tot_debugger->add_part_to_operation(__FUNCTION__,"add_user_verification_notices", "is_verification_info_submitted == true");
            wc_add_notice(
                '<span class="tot-wc-order-validation">'
                . __('Verification has been submitted and is under review', $this->text_domain)
                . '</span>',
                'notice'
            );
            wc_print_notices();

            return;

        } else if ($this->is_order_old($order)) {
	        $tot_debugger->add_part_to_operation(__FUNCTION__,"add_user_verification_notices", "Verification is required.");
	        wc_add_notice(
		        '<span class="tot-wc-order-validation">'
		        . __('Verification was not completed for this order.', $this->text_domain)
		        . '</span>',
		        'error'
	        );
	        wc_print_notices();
		} else {
	        $tot_debugger->add_part_to_operation(__FUNCTION__,"add_user_verification_notices", "Verification is required.");
            wc_add_notice(
                '<span class="tot-wc-order-validation">'
                . '<a data-tot-verification-required="true" data-tot-auto-open-modal="true" href="#tot_get_verified">'
                . __('Verification', $this->text_domain)
                . '</a> '
                . __(' is required before we can ship your order', $this->text_domain)
                . '</span>',
                'error'
            );
            wc_print_notices();
        }

		$tot_debugger->log_operation(__FUNCTION__);
    }

    public function handle_verify_person_api_error($response, $request = null, $url = null, $data = '')
    {
        return \TOT\API_Person::handle_verify_person_api_error($response, $request, $url, $data);
    }

	/**
	 * if verification was enabled when this order is placed
	 * then order would have tot_last_update
	 *
	 * @param \WC_Order $order
	 *
	 * @return bool
	 */
	public function is_order_placed_before_verification_enabled( $order )
	{
		return ! $order->get_meta("tot_last_updated");
	}

    public function is_verification_info_submitted($order)
    {
        $order = tot_wc_get_order($order);
        $gates = $this->get_order_reputation_gates($order, true);
        if (empty($gates)) {
            return false;
        }

        if ($gates->is_positive('isSubmitted')) {
            return true;
        }

        return false;

    }

	/**
	 * Check if the order past 7 days
	 *
	 * @param int $order
	 *
	 * @return bool
	 */
	public function is_order_old( $order ) {
		$order        = tot_wc_get_order( $order );
		$date_created = $order->get_date_created();
		$now          = new \DateTime( "now" );

		if ( $now->diff( $date_created )->format( "%a" ) > 7 ) {
			return true;
		}

		return false;
	}

    public function get_order_whitelist_data($order = null)
    {
        return apply_filters('tot_order_whitelisted_data', false, $order);
    }

    public function register_awaiting_verification_order_status()
    {
        register_post_status(
            'wc-must-verify',
            array(
                'label' => _x('Awaiting Verification', 'Order status', $this->text_domain),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Awaiting Verification <span class="count">(%s)</span>', 'Awaiting Verification <span class="count">(%s)</span>')
            )
        );
        register_post_status(
            'wc-needs-review',
            array(
                'label' => _x('Please Review', 'Order status', $this->text_domain),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list' => true,
                'label_count' => _n_noop('Ready for Review <span class="count">(%s)</span>', 'Ready for Review <span class="count">(%s)</span>')
            )
        );
    }

    public function add_awaiting_verification_to_order_statuses($order_statuses) {
        $new_order_statuses = array();
        // add new order status after processing
        foreach ($order_statuses as $key => $status) {
            if ('wc-processing' === $key) {
                $new_order_statuses['wc-must-verify'] = _x('Awaiting Verification', 'Order status', $this->text_domain);
                $new_order_statuses['wc-needs-review'] = _x('Ready for Review', 'Order status', $this->text_domain);
            }
            $new_order_statuses[$key] = $status;
        }
        return $new_order_statuses;
    }

    public function should_display_receipt($order)
    {

        if (!isset($order)) {
            return true;
        }

        $order = tot_wc_get_order($order);

        if (!$order || is_wp_error($order)) {
            return true;
        }

        if (true !== Admin::is_order_quarantined($order)) {
            return true;
        }

        if (!$this->is_verification_required_for_order($order)) {
            return true;
        }

        if ($this->is_verification_complete($order)) {
            return true;
        }

        if ($this->is_verification_info_submitted($order)) {
            return true;
        }

        return false;

    }


    public function is_verification_complete($order)
    {
        $order = tot_wc_get_order($order);
        $gates = $this->get_order_reputation_gates($order);

        if (empty($gates)) {
            return false;
        }

        if ($gates->is_positive('isCleared')) {
            return true;
        }

        return false;

    }


    public function get_order_reputation_reasons($order, $force_refresh = false)
    {

        $this->get_order_reputation($order, $force_refresh);

        if (is_wp_error($this->order_reputation_result)) {
            tot_display_error($this->order_reputation_result);
            return null;
        }

        if (!isset($this->order_reputation_result->reasons)) {
            return null;
        }

        return new Reasons($this->order_reputation_result->reasons);

    }

    public function get_order_reputation_gates($order, $force_refresh = false)
    {
        $order = tot_wc_get_order($order);
        $this->get_order_reputation($order, $force_refresh);

        if (is_wp_error($this->order_reputation_result)) {
            tot_display_error($this->order_reputation_result);
            return null;
        }

        if (!isset($this->order_reputation_result->gates)) {
            return null;
        }

        return new Reasons($this->order_reputation_result->gates);
    }

    public function get_email_reputation($order, $force_refresh = false)
    {
        if (empty($this->email_reputation_result) || $force_refresh) {
            $current_user_id = get_current_user_id();
            $order = tot_wc_get_order($order);
            $tot_userid = tot_create_appuserid_from_email($current_user_id, $order);
            $tot_user = new User(null, $tot_userid);
            // We don't save the email_reputation since it could be different from the user rep.
            $this->email_reputation_result = $tot_user->get_reputation_reasons($tot_userid);
        };
        return $this->email_reputation_result;
    }

    public function get_order_reputation($order, $force_refresh = false)
    {
        if ((null == $this->order_reputation_result) || (true == $force_refresh)) {
            $order = tot_wc_get_order($order);
            $this->order_reputation_result = tot_get_wc_order_reputation($order);
        }
        return $this->order_reputation_result;
    }

    public function should_receipt_display_verification_prompt($order)
    {
		$tot_debugger = tot_debugger::inst();
		$tot_debugger->register_new_operation(__FUNCTION__);

        if (!$order || is_wp_error($order)) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, "should_receipt_display_verification_prompt:require_verification_at_checkout", "Not displaying verification prompt bc order = empty");
			$tot_debugger->log_operation(__FUNCTION__);
            return false;
        }

        $order_id = $order->get_id();

        if (!isset($order_id) || is_wp_error($order_id)) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, "should_receipt_display_verification_prompt:require_verification_at_checkout", "Not displaying verification prompt bc order_id = empty");
	        $tot_debugger->log_operation(__FUNCTION__);
			return false;
        }

        $require_verification_at_checkout = $this->tot_is_verification_on_checkout_enabled();
        if (!$require_verification_at_checkout) {
	        $tot_debugger->add_part_to_operation(__FUNCTION__, "should_receipt_display_verification_prompt:require_verification_at_checkout", "Not displaying verification prompt bc tot_field_checkout_require = false");
	        $tot_debugger->log_operation(__FUNCTION__);
			return false;
        }

        if (!$this->is_verification_required_for_order($order)) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, "is_verification_required_for_order", "false");
	        $tot_debugger->log_operation(__FUNCTION__);
			return false;
        }

        if (!$this->user_can_verify_order($order)) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, "user_can_verify_order", "false");
	        $tot_debugger->log_operation(__FUNCTION__);
			return false;
        }

        $tot_debugger->add_part_to_operation(__FUNCTION__, "none_of_the_above so displaying verification status on receipt", "true");
	    $tot_debugger->log_operation(__FUNCTION__);
		return true;
    }

    // TODO - move this over to using class-verify.php.
    public function verify($order, $force_refresh = false, $error_callback = null)
    {
	    $tot_debugger = tot_debugger::inst();
	    $tot_debugger->register_new_operation(__FUNCTION__);

        if (!$force_refresh && ($this->verify_result !== null)) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, 'verify', 'Not updated bc we already have $this->verify_result');
            $tot_debugger->log_operation(__FUNCTION__);
			return $this->verify_result;
        }

        $order_id = $order->get_id();

        if (!$order_id) {
            return new WP_Error('tot-no-order-id', 'There was a problem finding this order');
        }

        $is_verification_info_submitted = $this->is_verification_info_submitted($order);
        if (!$force_refresh && $is_verification_info_submitted) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, 'Not updated bc $is_verification_info_submitted', $is_verification_info_submitted);
	        $tot_debugger->log_operation(__FUNCTION__);
	        return;
        }

        // Set that the order has been checked.
        // update_post_meta( $order_id, 'tot_last_updated', current_time( 'mysql' ) );

        $tot_transaction_id = tot_get_transaction_id($order);

		$traceId = tot_get_traceid($order, $order->get_user_id());

        $use_shipping_details = empty($order->get_billing_first_name());
        if ($use_shipping_details) {
            $first_name = $order->get_shipping_first_name();
            $last_name = $order->get_shipping_last_name();
        } else {
            $first_name = $order->get_billing_first_name();
            $last_name = $order->get_billing_last_name();
        }
        $email = $order->get_billing_email();
        $phoneNumber = $order->get_billing_phone();

        $person = [
            'givenName' => $first_name,
            'familyName' => $last_name,
            'email' => $email,
            'phoneNumber' => $phoneNumber
        ];

        // TODO - this need to change to have the reservationToken.
        if (!empty($order->get_billing_city())) {
            $billingLocation = [
                'countryCode' => $order->get_billing_country(),
                "line1" => $order->get_billing_address_1(),
                'line2' => $order->get_billing_address_2(),
                'locality' => $order->get_billing_city(),
                'region' => $order->get_billing_state(),
                'postalCode' => $order->get_billing_postcode()
            ];
            $person['billingLocation'] = $billingLocation;
        }

        if (!empty($order->get_shipping_city())) {
            $shippingLocation = [
                'countryCode' => $order->get_shipping_country(),
                "line1" => $order->get_shipping_address_1(),
                'line2' => $order->get_shipping_address_2(),
                'locality' => $order->get_shipping_city(),
                'region' => $order->get_shipping_state(),
                'postalCode' => $order->get_shipping_postcode()
            ];
            $person['shippingLocation'] = $shippingLocation;
        }

        if (!empty($person['billingLocation'])) {
            $person['location'] = $person['billingLocation'];
        } else {
            $person['location'] = $person['shippingLocation'];
        }

        $verify_person_data = [
            'appTransactionId' => $tot_transaction_id,
	        'traceId' => $traceId,
            // 'appTransactionTags' => ['wine'], // Todo: implement order/onboarding tags once available in core.
            'person' => $person
        ];

// TODO - not supporting force preapprove from client.
//        $order_whitelist_data = $this->get_order_whitelist_data($order_id);
//        if ($order_whitelist_data) {
//            $verify_person_data['person']['preApproved'] = $order_whitelist_data;
//        }

        $orderUserid = $order->get_user_id();
        if (!$orderUserid) {
            $verify_person_data['guest'] = 'true';
        } else {
            $verify_person_data['appUserid'] = tot_user_id($orderUserid, $order, false);
        }


        $totReservationToken = tot_get_time_based_cookie('totReservationToken');
        if (!empty($totReservationToken)) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, 'Binding with appReservationToken', array(
                '$totReservationToken' => $totReservationToken
            ));
	        $verify_person_data['appReservationToken'] = $totReservationToken;
        }

        $verify_person_data = apply_filters('tot_order_verification_data', $verify_person_data, $order);

        $request = new API_Request('api/person', $verify_person_data, 'POST');

        $tot_debugger->add_part_to_operation(__FUNCTION__, 'Verifying with', $verify_person_data);
	    $tot_debugger->log_operation(__FUNCTION__);

	    $this->verify_result = $request->send($error_callback);

        return $this->verify_result;

    }

    public function process_order($order, $force_refresh = false, $error_callback = null)
    {
	    $tot_debugger  = tot_debugger::inst();
        $tot_debugger->register_new_operation(__FUNCTION__);
        $tot_debugger->add_part_to_operation(__FUNCTION__, "Started.");

        if (!isset($order)) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, 'No order!!');
            $tot_debugger->log_operation(__FUNCTION__);
            return;
        }

        $order = tot_wc_get_order($order);

        if (is_wp_error($order)) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, 'order is a wp_error!!');
            $tot_debugger->log_operation(__FUNCTION__);
            return;
        }

		if ($this->is_order_old($order)) {
			$tot_debugger->add_part_to_operation(__FUNCTION__, 'The order passed 7 days So, Don\'t check verification');
			$tot_debugger->log_operation(__FUNCTION__);
			return;
		}
        
        if($this->is_order_processed($order) && is_null($error_callback)) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, 'This order is already processed with no error_callback function');
            $tot_debugger->log_operation(__FUNCTION__);
            return;
        }
        
        // store transcation_id, traceid and carthash
        $tot_debugger->add_part_to_operation(__FUNCTION__, 'Store TransactionId, TraceId for order: ' . $order->get_id());
        tot_store_transaction_id($order);
        tot_store_traceid($order);
	    tot_store_cart_hash($order);

        if($this->user_has_exempted_role()){
            $tot_debugger->add_part_to_operation(__FUNCTION__, 'user_has_exempted_role');
            $tot_debugger->log_operation(__FUNCTION__);
            $order->update_meta_data('tot_not_required', true);
            $order->save();
            return;
        }

        $require_verification_at_checkout = $this->tot_is_verification_on_checkout_enabled();
        if (!$require_verification_at_checkout) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, "process_order", '!$require_verification_at_checkout');
            $tot_debugger->log_operation(__FUNCTION__);
			return;
        }

        if (!$this->is_verification_required_for_order($order)) {
            $tot_debugger->add_part_to_operation(__FUNCTION__, "process_order", '!$this->is_verification_required_for_order($order)');
	        $tot_debugger->log_operation(__FUNCTION__);
			return;
        }

        // Send transaction to TOT for EIDV and other verification steps.
        $verify_result = $this->verify($order, $force_refresh, $error_callback);
        $reasons = $this->get_order_reputation_reasons($order, true);
        $gates = $this->get_order_reputation_gates($order, true);
        $order_state = $this->order_result_verification_state($reasons, $gates, $order);

        // Determine if order should be quarantine and allow site to override. Default to yes.
        $verification_state_name = $order_state['name'];
        $verification_state_message = !empty($order_state['messages']) ? implode(' ', $order_state['messages']) : null;
        $set_quarantine = $verification_state_name === 'pass' ? false : true;
        $set_quarantine = apply_filters('tot_process_order_set_quarantine', $set_quarantine, $order, $verify_result);
        $order->update_meta_data('tot_quarantined', $set_quarantine);
        $order->update_meta_data('tot_last_updated', current_time('mysql'));
        $order->save();
        
        if ($set_quarantine) {
            if (empty($verification_state_message)) {
                $order->add_order_note(__('Awaiting verification.', $this->text_domain));
            } else {
                $order->add_order_note(__('Awaiting verification: ', $this->text_domain) . $verification_state_message);
            }
        } else {
            if (empty($verification_state_message)) {
                $order->add_order_note(__('Order cleared.', $this->text_domain));
            } else {
                $order->add_order_note(__('Order cleared: ', $this->text_domain) . $verification_state_message);
            }
        }

        // Order verification failed, place in quarantine
        if ('failed' === $verification_state_name) {
            $order_status = $order->get_status();
            // TODO use $considerStatusChangeOrNoteForOrder = $this->considerStatusChangeOrNoteForOrder($order, null, 'woo-status-change');
            $okayToUpdateStatus = in_array($order_status,
                apply_filters('tot_woo_on_fail_hold_when_in_states', array('processing', 'completed'), $order, $reasons));


            if ($okayToUpdateStatus) {
                $fail_state = apply_filters('tot_woo_update_fail_state', 'on-hold', $order, 'process_order', $reasons, $verify_result);
                $order->update_status(
                    apply_filters('tot_order_age_verification_failed', $fail_state, 'process_order', $order, $reasons, $verify_result),
                    __('Updating order status from verification result', $this->text_domain)
                );
            }
        }

	    $tot_debugger->log_operation(__FUNCTION__);
    }

    /**
     * @param \WC_Order $order
     * @return bool
     */
    public function is_order_processed($order)
    {
        return $order->get_meta('tot_transaction_id', true)
            && $order->get_meta('_tot_traceid', true);
    }

    public function post_payment_status_check($order)
    {
        $order = tot_wc_get_order($order);
        $is_order_quarantined = Admin::is_order_quarantined($order);
        if ($is_order_quarantined) {
            // In theory the order would already be unquarantined if it could be - so this should not be necessary.
            $considerStatusChangeOrNoteForOrder = $this->considerStatusChangeOrNoteForOrder($order, null, 'woo-status-change');
            if ($considerStatusChangeOrNoteForOrder) {
                $reasons = $this->get_order_reputation_reasons($order);
                $this->processUpdatedReasons($order, $reasons, 'post-payment');
            }
        }
    }

    public function post_payment_cod_status_check($status, $order)
    {
        $this->post_payment_status_check($order);
        return $order->get_status();
    }

    public function user_can_verify_order($order)
    {
        $order = tot_wc_get_order($order);
        $order_user_id = $order instanceof \WC_Order ? $order->get_customer_id() : null;

        if (isset($order_user_id) && !is_wp_error($order_user_id) && (strval($order_user_id) === '0')) {

            // Guest checkout
            return true;

        } else {

            // Member checkout
            if (is_user_logged_in() && $this->order_belongs_to_current_member($order)) {
                return true;
            }

        }

        return false;

    }

    public function order_belongs_to_current_member($order)
    {
        $customer_id = $order->get_customer_id();

        return $customer_id == get_current_user_id();

    }

    /**
     * Same as is_verification_consent_required_for_order BUT also includes
     * preapprovals.
     *
     * @param ?\WC_Order $order
     * @param null $cart
     * @return bool
     */
    public function is_verification_required_for_order($order = null, $cart = null)
    {
        $order = tot_wc_get_order($order);
        $client_requires_verification = $this->is_tot_verification_required_per_client_configuration($order, $cart);
        $client_requires_verification = apply_filters('tot_is_verification_required_for_order', $client_requires_verification, $order ? $order->get_id() : null, $cart);
        $tot_not_required_by_client = empty($client_requires_verification);

        // check if the order already passed verification once before
        if ($order && $order->get_meta('tot_not_required', true)){
            return false;
        } elseif ($order) {
			$order->update_meta_data('tot_not_required', $tot_not_required_by_client ? true : false);
            $order->save();
		}

        return $client_requires_verification;
    }

    public function is_verification_consent_required_for_order($order = null, $cart = null)
    {
        $order = tot_wc_get_order($order);
        $consent_is_required = apply_filters('tot_is_verification_consent_required_for_order', true, $order, $cart);
        return $consent_is_required && !$this->is_order_preapproved($order);
    }

    public function has_product_in_restricted_category($cart_contents = array())
    {
        $restricted_categories = Settings::get_setting('tot_field_checkout_require_categories');
        if (!is_array($cart_contents) || !isset($restricted_categories) ||
            ($restricted_categories == '') || !is_array($restricted_categories)) {
            return false;
        }

	    $tot_debugger = tot_debugger::inst();
	    $tot_debugger->register_new_operation(__FUNCTION__);

        foreach ($cart_contents as $item) {
            $product_id = is_array($item) ? $item['product_id'] : $item;
            $categories = $this->get_product_terms($product_id, 'product_cat');

            if (!is_array($categories)) {
                $tot_debugger->add_part_to_operation(__FUNCTION__, "CATEGORIES NOT FOUND", $categories);
                continue;
            }

            foreach ($categories as $category) {
                $category_id = $category->term_id;
                if (in_array($category_id, $restricted_categories)) {
                    $tot_debugger->add_part_to_operation(__FUNCTION__, "MATCH! " . $category_id, $restricted_categories);
	                $tot_debugger->log(__FUNCTION__);
					return true;
                } else {
                    $tot_debugger->add_part_to_operation(__FUNCTION__, "MISMATCH! " . $category_id, $restricted_categories);
                    // does not match category.
                }
            }
        }

        $tot_debugger->add_part_to_operation(__FUNCTION__, "NOTHING IN CART MATCHES CATEGORY", $cart_contents);
        $tot_debugger->log(__FUNCTION__);
		return false;
    }

    public function has_product_in_restricted_tag($cart_contents = array())
    {
        $restricted_tags = Settings::get_setting('tot_field_checkout_require_tags');
        if (!is_array($cart_contents) || !isset($restricted_tags) ||
            ($restricted_tags == '') || !is_array($restricted_tags)) {
            return false;
        }

	    $tot_debugger = tot_debugger::inst();
	    $tot_debugger->register_new_operation(__FUNCTION__);

        foreach ($cart_contents as $item) {
            $product_id = is_array($item) ? $item['product_id'] : $item;
            $tags = $this->get_product_terms($product_id, 'product_tag');

            $tot_debugger->add_part_to_operation(__FUNCTION__, "USING PRODUCT TAGS for " . $product_id, $tags);

            if (!is_array($tags)) {
                $tot_debugger->add_part_to_operation(__FUNCTION__, "TAGS NOT FOUND", $tags);
                continue;
            }
            foreach ($tags as $tag) {
                $tag_id = $tag->term_id;
                if (in_array($tag_id, $restricted_tags)) {
                    $tot_debugger->add_part_to_operation(__FUNCTION__, "TAG (" . $tag_id . ") IS IN RESTRICTED TAGS", $restricted_tags);
                    $tot_debugger->log(__FUNCTION__);
					return true;
                } else {
                    $tot_debugger->add_part_to_operation(__FUNCTION__, "TAG (" . $tag_id . ") IS NOT IN RESTRICTED TAGS", $restricted_tags);
                }
            }
        }

        $tot_debugger->add_part_to_operation(__FUNCTION__, "NOTHING IN CART MATCHES RESTRICTED TAGS", $cart_contents);
	    $tot_debugger->log(__FUNCTION__);

        return false;
    }

    public function has_required_payment_method($order)
    {
        $required_payment_methods = Settings::get_setting('tot_field_checkout_require_payment_methods');
        if (!isset($required_payment_methods) || ($required_payment_methods == '') || !is_array($required_payment_methods)) {
            // Then no payment methods explicitly required.
            return true;
        }

        if (!isset($order)) {
            $chosen_method = WC()->session->get( 'chosen_payment_method');
            return in_array($chosen_method, $required_payment_methods);
        }

        $method_of_payment = $order->get_payment_method();
        if (!isset($method_of_payment)) {
            return true;
        }

        if (in_array($method_of_payment, $required_payment_methods)) {
            return true;
        }

        return false; // payment method not found.
    }

    public function has_required_shipping_method($order)
    {
        $required_shipping_methods = Settings::get_setting('tot_field_checkout_require_shipping_methods');
        if (!isset($required_shipping_methods) || ($required_shipping_methods == '') || !is_array($required_shipping_methods)) {
            // Then no shipping methods explicitly required.
            return true;
        }

        if (!isset($order)) {
            $wc = \WC();
            $chosen_method = WC()->session->get( 'chosen_shipping_methods');
            if (isset($chosen_method[0])) {
                $chosen_method = explode(':',$chosen_method[0])[0];
            }
            return in_array($chosen_method, $required_shipping_methods);
        }

        $items_shipping = $order->get_items('shipping');
        foreach ($items_shipping as $line_item) {
            $method_id = $line_item->get_method_id();
            if (isset($method_id) && in_array($method_id, $required_shipping_methods)) {
                return true;
            }
        }

        return false; // shipping method not found.
    }

    /**
     * @param \WC_Order $order
     * @return bool
     */
    public function has_required_country($order)
    {
        $keys = tot_get_keys();
        $base = $keys['includedCountries']['base'] ?? '';
        $countries = $keys['includedCountries']['additional'] ?? [];

        if ($order) {
            $country = $order->get_shipping_country();
        } else {
            $country = WC()->customer->get_shipping_country();
        }

        return ( $base == 'none' &&
            in_array(strtolower($country), array_map('strtolower', $countries), true))
            || ($base =='' && empty($countries)); // no settings about restricted countries then return true
    }

    public function user_has_exempted_role(){
        return tot_user_has_exempted_role();
    }

    public function enqueue_javascript()
    {
        wp_enqueue_script(
            'admin-token-of-trust',
            plugins_url('/wc-checkout.js', __FILE__),
            array('jquery'));

        $this->enqueue_tot_checkout();
    }

    public function checkout_field_verification_acknowledgement()
    {
        // tot_log_as_html_comment('checkout_field_verification_acknowledgement', 'start');
        $wc = \WC();

        $is_verification_required_for_order = $this->is_verification_required_for_order(null, $wc->cart);
        // tot_log_as_html_comment('$is_verification_required_for_order', $is_verification_required_for_order);
        if (!$is_verification_required_for_order) {

            // to pass the verfication dynamically
            woocommerce_form_field('tot_verification_acknowledgement', array(
                    'type' => 'checkbox',
                    'class' => array('tot-verification-acknowledgement-field', 'tot-hide-acknowledgement-field'),
                    'required' => true
                )
            , false);
            return;
        }
        $is_verification_consent_required_for_order = $this->is_verification_consent_required_for_order(null, $wc->cart);

        if (!$is_verification_consent_required_for_order) {
            return;
        }

        echo '<div id="tot_verification_acknowledgement_field">';
        echo '<h2>' . __('Verification', $this->text_domain) . '</h2>';
        echo '<p>' . __('This order requires verification using Token of Trust.', $this->text_domain) . '</p>';

        $checkout = $wc->checkout;
        woocommerce_form_field('tot_verification_acknowledgement', array(
            'type' => 'checkbox',
            'class' => array('tot-verification-acknowledgement-field'),
            'label' => __('I agree to share information with Token of Trust', $this->text_domain),
            'required' => true
        ), $checkout->get_value('tot_verification_acknowledgement'));

        echo '</div>';
    }


    public function enqueue_tot_checkout()
    {
        // tot_log_as_html_comment('checkout_field_verification_acknowledgement', 'start');
        $wc = \WC();

        $is_verification_required_for_order = $this->is_verification_required_for_order(null, $wc->cart);
        // tot_log_as_html_comment('$is_verification_required_for_order', $is_verification_required_for_order);

        /**
         * In the checkout page we will use another approach
         * which depends on the customer choices of shipping and payment methods
         * @see function checkout_field_verification_acknowledgement
         */
        if (!$is_verification_required_for_order && !is_checkout()) {
            return;
        }
        $is_verification_consent_required_for_order = $this->is_verification_consent_required_for_order(null, $wc->cart);

        $woo_enable_verification_before_payment_selector = Settings::get_setting('tot_field_woo_enable_verification_before_payment');
        // tot_log_as_html_comment('$woo_enable_verification_before_payment', $woo_enable_verification_before_payment_selector);
        if (!empty($woo_enable_verification_before_payment_selector)) {
            wp_enqueue_script('tot-bind', \tot_checkout_js(),
                array('jquery'), \tot_plugin_get_version());

            $woo_enable_verification_before_payment_selector = Settings::get_setting('tot_field_woo_verification_before_payment_selector');
            $woo_trigger_verification_on_original_button = Settings::get_setting('tot_field_woo_trigger_verification_on_original_button') || false;
            $woo_debounce_payment_btn_click = Settings::get_setting('tot_field_woo_debounce_payment_btn_click') || false;

            $woo_enable_verification_before_payment_selector =
                !empty($woo_enable_verification_before_payment_selector) ?
                    $woo_enable_verification_before_payment_selector :
                    '#place_order';

            // Before we set up tot-bind, I want to see if I should clone or simply use the existing checkout button.
            if ($woo_trigger_verification_on_original_button && $woo_trigger_verification_on_original_button == 1) {
                // You've specifically said you DON'T want to clone the button, so okay Sparky, let's have it your way.
                $bindOptionsArray = array(
                    'bindDataKey' => 'collectedData',
                    'launchVerificationButtonSelector' => $woo_enable_verification_before_payment_selector,
                    'debouncePaymentButtonClick' => $woo_debounce_payment_btn_click
                );
            } else {
                // default button assignment.
                $bindOptionsArray = array(
                    'bindDataKey' => 'collectedData',
                    'replaceButtonSelector' => $woo_enable_verification_before_payment_selector,
                    'launchVerificationButtonSelector' => $woo_enable_verification_before_payment_selector . 'totElement',
                    'debouncePaymentButtonClick' => $woo_debounce_payment_btn_click
                );
            }

            // Okay, NOW do the tot-bind...
            wp_localize_script('tot-bind', 'totOpts',
                array(
                    'totHost' => tot_scrub_prod_domain(tot_origin()),
                    'apiKey' => tot_get_public_key(),
                    'appDomain' => tot_get_setting_prod_domain(),
                    'verifyPerson' => array(
                        'body' => array('action' => 'tot_verify_person'),
                        'url' => admin_url('admin-ajax.php') . '?action=tot_verify_person',
                    ),
                    'autoBindVerificationButtons' => true,
                    'consentIsRequired' => $is_verification_consent_required_for_order,
                    'bindOptions' => $bindOptionsArray
                )
            );
        }
    }

    public function checkout_field_validation()
    {

        $wc = \WC();

        if (!$this->is_verification_consent_required_for_order(null, $wc->cart)) {
            return;
        }

        if (!$this->is_verification_required_for_order(null, $wc->cart)) {
            return;
        }

        // Check if set, if its not set add an error.
        if (!isset($_POST['tot_verification_acknowledgement'])) {
            $verificationRequired = __('<strong>Verification</strong> is required.', $this->text_domain);
            $clickTheCheckbox = __('Click the checkbox to confirm.', $this->text_domain);
            wc_add_notice('<a href="#tot_verification_acknowledgement_field">' .
                $verificationRequired . ' ' . $clickTheCheckbox . '</a>', 'error');
            return;
        }

        $backend_checkout_verification = Settings::get_setting('tot_field_enable_backend_checkout_verification');
        $woo_enable_verification_before_payment_selector = Settings::get_setting('tot_field_woo_enable_verification_before_payment');
        if (!empty($backend_checkout_verification) && !empty($woo_enable_verification_before_payment_selector)) {
            $this->pre_checkout_verification();
        }
    }
    
    private function pre_checkout_verification()
    {
        if ($this->pre_checkout_is_verified_by_reputation()) {
            return;
        }

        // get the verification data to trigger the verification popup
        $this->do_pre_checkout_verification();
    }

    private function pre_checkout_is_verified_by_reputation()
    {
        $appUserId = tot_user_id(get_current_user_id(), $_POST, false);
        $reputation = tot_get_user_reputation($appUserId);
        $gates = $reputation->gates ?? (object) [];
        $gates = new Reasons($gates);

        return $gates->is_positive('isCleared');
    }
    
    private function do_pre_checkout_verification()
    {
        $verify = new \TOT\API_Person();
        $verify->set_details_from_appData($_POST);

        $response = $verify->sendRaw();
        if (is_wp_error($response)) {
            wc_add_notice(
                '<span class="tot-wc-order-validation">'
                . __('There was a problem trying to setup your verification, please try again. ', "token-of-trust")
                . '</span>',
                'error'
            );
            return;
        }

        $response = new API_Response( $response, $verify->request_details, $verify->endpoint_url);

        $body_decoded = $response->body_decoded;
        if (isset($body_decoded->appReservationToken)) {
            // Record the reservationToken on the server side / in a cookie?
            $appReservationToken = $body_decoded->appReservationToken;

            // Set the reservation as a long duration cookie.
            tot_set_time_based_cookie('totReservationToken', $appReservationToken, 60*60*24*89);
        }

        if ($response->is_next_step_interactive()) {
            $modal_type = $response->body_decoded->continuation->modalType ?? '';
            $params = json_encode($response->body_decoded->continuation->params ?? []);
            wc_add_notice(
                "<a href='#tot_generated_get_verified' data-type='$modal_type' data-params='$params'>"
                    . __("Please click here to verify your identity before proceeding.", "token-of-trust")
                . "</a>",
                'error'
            );
        }
    }

    /**
     * TODO: We really should be using gates rather than reasons...
     * @param $reasons
     * @param $order
     * @return array|string[]
     */
    public function order_result_verification_state($reasons, $gates, $order)
    {
        $order = tot_wc_get_order($order);
        $is_order_preapproved = $this->is_order_preapproved($order, $reasons);
        if ($is_order_preapproved === 'appPreApproved') {
            return array(
                'name' => 'pass',
                'code' => 'passed-white-listed',
                'messages' => array(
                    __('Order white-listed. Skipping Token of Trust verification.', $this->text_domain)
                )
            );
        }

        if (empty($reasons) || empty($gates)) {
            return array(
                'name' => 'no-action'
            );
        }

        $govtIdPositiveReview = $reasons->is_positive('govtIdPositiveReview'); //This one is verified by ToT
        $govtIdPositiveAppReview = $reasons->is_positive('govtIdPositiveAppReview'); // This one was verified by the vendor
        $govtIdPendingReview  = $reasons->is_positive('govtIdPendingReview'); // This one is currently pending verification, it may or may not be approved by the vendor.

        $isSubmitted = $gates->is_positive('isSubmitted'); // The request has all documents, but has not been either verified or approved.
        $isRejected = $gates->is_positive('isRejected'); // This one has been explicitly rejected.
        $isCleared = $gates->is_positive('isCleared'); // This one is good to go (either approved or verified)


        $force_accept_on_app_approve = empty(Settings::get_setting('tot_field_dont_force_accept_on_app_approve'));
        if ($force_accept_on_app_approve && $govtIdPositiveAppReview) {
            // WARNING: This overrides any age settings!!
            return array(
                'name' => 'pass',
                'code' => 'passed-vendor-review',
                'messages' => array(
                    __('Order approved by admin review.', $this->text_domain)
                )
            );
        }

        // TODO use api key setting for min age instead of local setting.
        $wp_min_age = Settings::get_setting('tot_field_min_age');
        if (!empty($wp_min_age)) {
            // Then we pass based upon minimum age criteria.
            $process_min_age = $this->process_min_age($reasons, $wp_min_age);
            if (!empty($process_min_age)) {
                return $process_min_age;
            }
        } else {

            // TODO - we need this to depend upon gates.
            if ($govtIdPositiveReview) {
                return array(
                    'name' => 'pass',
                    'code' => 'passed-tot-review',
                    'messages' => array(
                        __('Order verified by Token of Trust.', $this->text_domain)
                    )
                );
            } else if ($govtIdPositiveAppReview) {
                return array(
                    'name' => 'pass',
                    'code' => 'passed-tot-review',
                    'messages' => array(
                        __('Order approved by admin.', $this->text_domain)
                    )
                );
            } else if ($isSubmitted) {
                return array(
                    'name' => 'needs-review',
                    'code' => 'pending-app-review',
                    'messages' => array(
                        __('Customer submitted verification. Order is ready for review.', $this->text_domain)
                    )
                );
            } else if ($isCleared) {
                return array(
                    'name' => 'pass',
                    'code' => 'passed-vendor-review',
                    'messages' => array(
                        __('Order has been cleared.', $this->text_domain)
                    )
                );
            } else if ($isRejected) {
                return array(
                    'name' => 'rejected',
                    'code' => 'rejected',
                    'messages' => array(
                        __('This order has been rejected.', $this->text_domain)
                    )
                );
            }
            // I think "isRejected" goes here.
        }
    
        $order_status = $order->get_status();

        if (in_array($order_status, array('processing', 'completed'))) {
            return array(
                'name' => 'must-verify', // to ensure we process results later if we go through vendor review.
                'code' => 'customer-must-verify',
                'messages' => array(
                    __('Payment processing complete. Order requires verification.', $this->text_domain)
                )
            );
        } else {
            return array(
                'name' => 'must-verify', // to ensure we process results later if we go through vendor review.
                'code' => 'customer-must-verify'
            );
        }
    }

    public function process_min_age($reasons, $wp_min_age)
    {
        $wp_min_age = intval($wp_min_age);
        if (!$reasons->reason_has_value('ageVerified')) {
            return array(
                'name' => 'must-verify'
            );
        }

        if ($reasons->has_insufficient_data('ageVerified')) {
            return array(
                'name' => 'must-verify'
            );
        }

        if (!$reasons->is_positive('ageVerified')) {
            return array(
                'name' => 'failed',
                'code' => 'failed-negative-age-verified-reason',
                'messages' => array(
                    __('Age did not pass verification from the information given.', $this->text_domain)
                )
            );
        }

        if (!isset($reasons->reasons->ageVerified->ageRange->min)) {
            return array(
                'name' => 'failed',
                'code' => 'failed-no-verified-minimum-age',
                'messages' => array(
                    __('Minimum age cannot be determined from the information given.', $this->text_domain)
                )
            );
        }

        // TODO: Migrate to use: "meetsMinimumAgeRequirement"
        $verified_min_age = intval($reasons->reasons->ageVerified->ageRange->min);

        if ($verified_min_age < $wp_min_age) {
            return array(
                'name' => 'failed',
                'code' => 'failed-age-is-under-minimum',
                'messages' => array(
                    __('Age is at least ', $this->text_domain)
                    . $verified_min_age
                    . __(', but required age is ', $this->text_domain)
                    . $wp_min_age
                    . '.'
                )
            );
        }

        if ($verified_min_age >= $wp_min_age) {
            return array(
                'name' => 'pass',
                'messages' => array(
                    __('Age is at least ', $this->text_domain)
                    . $verified_min_age
                    . __(' and required age is ', $this->text_domain)
                    . $wp_min_age
                    . '.'
                )
            );
        }

        return null;
    }

    public function default_autoprocess_webhook($name, $body, $input = null)
    {
//		$tot_debugger = tot_debugger::inst();
//		$tot_debugger->register_new_operation(__FUNCTION__);
        // The body operation will function as the tot_status later down...
        if (isset($body->operation) && isset($body->app_transaction_id)) {
            // So if it exists, get the variable and the order for later.
			$order = $this->get_order_by_guid($body->app_transaction_id);
			if ($order) {
				$order_id = $order->get_id();
				$tot_status = $body->operation;
//				tot_debugger::inst()->add_part_to_operation( '', 'webhook_operation', $body->operation );
//				tot_debugger::inst()->add_part_to_operation( '', 'appTransactionId', $body->app_transaction_id);
//				tot_debugger::inst()->add_part_to_operation( '', 'orderId: ', $order_id );
			}

        } else {
//	        tot_debugger::inst()->add_part_to_operation('','webhook_operation', 'NO OPERATION');
        }

        if (isset($order) && isset($tot_status)) {
            // Tot Status exists, let's update the order meta.
            $order->update_meta_data('tot_status', $tot_status);
            $order->save();
//	        tot_debugger::inst()->add_part_to_operation('','webhook_tot_status', 'THE TOT STATUS SHOULD BE SET TO '.$tot_status);
        }

//		$tot_debugger->log_operation(__FUNCTION__);

        if (
            ('reputation.updated' !== $name)
            && ('reputation.created' !== $name)
            && ('totManualReview.updated' !== $name)
            && ('totReview.updated' !== $name)
            && ('appReview.updated' !== $name)
        ) {
            return;
        }

        $hasTransactionAndReasons = isset($body->appTransactionId) && isset($body->reasons);
        if (empty($hasTransactionAndReasons)) {
            // nothing
        } else {
            // Critical to register_awaiting_verification_order_status so that we can move orders into states.
            $this->register_awaiting_verification_order_status();
            $order = $this->get_order_by_guid($body->appTransactionId);
            $reasons = new Reasons($body->reasons);
            $this->processUpdatedReasons($order, $reasons, $name);
        }

    }

    public function get_order_by_guid($guid)
    {

        $orders = wc_get_orders(array(
            'limit' => 1,
            'meta_key' => 'tot_transaction_id',
            'meta_value' => $guid
        ));

        if (isset($orders) && is_array($orders) && (count($orders) == 1)) {
            return tot_wc_get_order($orders[0]->ID);
        }

        return null;
    }

    function order_lookup_redirect()
    {

        global $wp;
        if (($wp->request == 'token-of-trust/wc/order') && isset($_GET['guid'])) {

            if (!current_user_can('manage_woocommerce')) {
                return;
            }

            $guid = sanitize_text_field($_GET['guid']);
            $order = $this->get_order_by_guid($guid);

            if ($order) {
                $edit_link = $order->get_edit_order_url();
                wp_redirect($edit_link);
                exit;
            }
        }

    }

    /**
     * Proper update of the reasons on the order as well as the 'quarantine status.
     *
     * @param \WC_Order $order
     * @param Reasons $reasons
     * @param $context
     */
    public function processUpdatedReasons($order, $reasons, $context)
    {
        if (empty($order) || empty($reasons)) {
	        tot_debugger::inst()->add_part_to_operation('','debug:webhook', 'aborting: empty($order) || empty($reasons).');
            return null;
        }

        $order_id = $order->get_id();
        $order_status = $order->get_status();

        if (!Admin::is_order_quarantined($order)) {
	        tot_debugger::inst()->add_part_to_operation('','debug:webhook', 'Aborting: Order not quarantined skipping processing reasons.');
            return $order_status;
        }

        $okayToUpdateStatus = $this->considerStatusChangeOrNoteForOrder($order, $reasons, $context);
        $updatedOrderStatus = null;

        $verification_state = apply_filters('tot_order_status', null, $order, $reasons);
        if (empty($verification_state)) {
            $gates = $this->get_order_reputation_gates($order, true);
            $verification_state = $this->order_result_verification_state($reasons, $gates, $order);
        }

        $verification_state_name = $verification_state['name'];
        $verification_state_message = isset($verification_state['messages']) ? implode(' ', $verification_state['messages']) : '';

        $note = null;
        if ('failed' === $verification_state_name) {
            $note = __('Automatically updating status from webhook.', $this->text_domain) . ' ' . $verification_state_message;
            $order->update_meta_data('tot_last_updated', current_time('mysql'));
            $order->save();
            $updatedOrderStatus = apply_filters('tot_order_verification_failed_next_woo_state', 'on-hold', $context, $order, $reasons);
            do_action('tot_order_verification_failed', $order, $reasons);
        } else if ('pass' === $verification_state_name) {
            $order->update_meta_data('tot_last_updated', current_time('mysql'));
            $order->update_meta_data('tot_quarantined', false);
            $order->save();

            $updatedOrderStatus = $this->tot_order_verification_passed_next_woo_state($order_status, $verification_state, $order, $reasons);
            do_action('tot_order_verification_passed', $order, $reasons);
            $wp_min_age = Settings::get_setting('tot_field_min_age');
            if (isset($updatedOrderStatus)) {
                if ($order_status === 'must-verify' || $order_status === 'needs-review') {
                    if (!empty($wp_min_age)) {
                        $note = __('Automatically removing age verification hold because: ', $this->text_domain) . ' ' . $verification_state_message;
                    } else {
                        $note = __('Automatically removing verification hold because: ', $this->text_domain) . ' ' . $verification_state_message;
                    }
                } else {
                    if (!empty($wp_min_age)) {
                        $note = __('Age verified: ', $this->text_domain) . ' ' . $verification_state_message;
                    } else {
                        $note = __('Cleared: ', $this->text_domain) . ' ' . $verification_state_message;
                    }
                }
            }
        } else if ('must-verify' === $verification_state_name || 'needs-review' === $verification_state_name) {
            $updatedOrderStatus = $verification_state_name;
            $okayToAddNote = in_array($order_status, array('pending', 'processing', 'completed', 'must-verify', 'needs-review'));
            if ($okayToAddNote && $updatedOrderStatus !== $order_status) {
                $note = $verification_state_message;
            }
        }
	    tot_debugger::inst()->add_part_to_operation('','debug:webhook', 'Added note: ' . $note);
        $willUpdateOrderStatus = $okayToUpdateStatus && $updatedOrderStatus !== $order_status;
        if ($willUpdateOrderStatus) {
	        tot_debugger::inst()->add_part_to_operation('','debug:webhook', "Updating status from $order_status to $updatedOrderStatus");
            if (empty($note)) {
                $order->update_status($updatedOrderStatus);
            } else {
                $order->update_status($updatedOrderStatus, $note);
            }
        } else {
            if (!empty($note)) {
                $order->add_order_note($note);
            }
        }

        $order_status = $order->get_status();
        return $order_status;
    }

    public function tot_is_verification_on_checkout_enabled()
    {
        $userHasExemptedRole = $this->user_has_exempted_role();

        return tot_live_or_in_trial()
            && !$userHasExemptedRole
            && Settings::get_setting('tot_field_checkout_require');
    }

    public function tot_order_verification_next_woo_state($from_order_status, $verification_state, $order, Reasons $reasons)
    {
        $is_order_quarantined = Admin::is_order_quarantined($order);
        switch ($from_order_status) {
            // If order is in processing or completed
            // AND quarantined it should be in must-verify.
            // Aside: The order can be moved out of quarantine either by review or by admin-action.

            case 'needs-review':
            case 'must-verify':
                if (!$is_order_quarantined) {
                    return apply_filters('tot_order_status_after_positive_review', 'processing', $order, $reasons);
                }
                break;
            case 'processing':
            case 'completed':
                if ($is_order_quarantined) {
                    return 'must-verify';
                }
                break;
            default:
        }
        return $from_order_status;
    }

    public function tot_order_verification_passed_next_woo_state($from_order_status, $verification_state, $order, Reasons $reasons)
    {
        return apply_filters('tot_order_verification_passed_next_woo_state', $this->tot_order_verification_next_woo_state($from_order_status, $verification_state, $order, $reasons), $from_order_status, $verification_state, $order, $reasons);
    }

    public function is_order_preapproved($order, $reasons = null)
    {
        $order = tot_wc_get_order($order);
        $order_whitelist_data = $this->get_order_whitelist_data($order);
        if (!empty($order_whitelist_data)) {
            return true;
        }

        // Currently we're forcing use of "email reputation" for pre-approval.
        $reasons = !empty($reasons) ? $reasons : $this->get_email_reputation($order);
        $has_reasons = !empty($reasons) && !is_wp_error($reasons);
        $orderIsPreapproved = $has_reasons && $reasons->is_positive('appPreApproved');
        if ($orderIsPreapproved) {
            return 'appPreApproved';
        }

        return $orderIsPreapproved;
    }

    /**
     * Fetches the product terms. For variable products tries to use terms for parent.
     * @param $product_id
     * @return mixed
     */
    public function get_product_terms($product_id, $term = 'product_cat')
    {
		$tot_debugger = tot_debugger::inst();

        $terms = get_the_terms($product_id, $term);
        if (!is_array($terms)) {
            $variation = wc_get_product($product_id);
            if (!empty($variation)) {
                $terms = get_the_terms($variation->get_parent_id(), $term);
            }
            if (is_array($terms)) {
                $tot_debugger->add_part_to_operation('', "USING PRODUCT TERMS FOR PARENT PRODUCT", $terms);
            }
        }
        return $terms;
    }

    /**
     * @param $order
     * @param $reasons
     * @param $context
     * @param $order_status
     * @return bool
     */
    public function considerStatusChangeOrNoteForOrder($order, $reasons, $context)
    {
        if (empty($order)) {
            return false;
        }

        $order_status = $order->get_status();
        $totStatesEnabled = Settings::get_setting('tot_field_woo_enable_tot_states');
        $defaultOkStates = empty($totStatesEnabled) ? array('must-verify', 'needs-review') : array('processing', 'completed', 'must-verify', 'needs-review');
        $actualOkStates = apply_filters('tot_update_woo_when_in_states', $defaultOkStates, $context, $order, $reasons);
        $okayToUpdateStatus = in_array($order_status, !empty($actualOkStates) ? $actualOkStates : $defaultOkStates);

        return $okayToUpdateStatus;
    }

    /**
     * @param $order_id
     * @param $cart
     * @return false
     */
    public function is_tot_verification_required_per_client_configuration($order, $cart)
    {
        $subtotal = 0;
        $cart_contents = [];

        if ($order !== null) {
            $order = tot_wc_get_order($order);
            $subtotal = $order->get_subtotal();  // TODO : Should be total?
            foreach ($order->get_items() as $line_item) {
                $product = $line_item->get_product();
                array_push($cart_contents, $product->get_id());
            }
        } else if ($cart !== null) {
            $subtotal = $cart->subtotal;
            $cart_contents = $cart->cart_contents;
        } else {
            return false;
        }

        $require_verification_at_checkout = $this->tot_is_verification_on_checkout_enabled();
        $minimum_cart_value = Settings::get_setting('tot_field_checkout_require_total_amount');
        $cart_amount_is_over_minimum = !isset($minimum_cart_value) ? false : floatval($subtotal) > floatval($minimum_cart_value);

        $has_product_in_restricted_category = $this->has_product_in_restricted_category($cart_contents);
        $has_product_in_restricted_tag = $this->has_product_in_restricted_tag($cart_contents);
        $has_required_payment_method = $this->has_required_payment_method($order);
        $has_required_shipping_method = $this->has_required_shipping_method($order);
        $has_required_country = $this->has_required_country($order);

        $requires_verification =
            ($require_verification_at_checkout && $has_required_payment_method && $has_required_shipping_method && $has_required_country) &&
            ($cart_amount_is_over_minimum || $has_product_in_restricted_category || $has_product_in_restricted_tag);

//         $tot_debugger->add_part_to_operation(__FUNCTION__, "requires_verification: ", array(
//             '$require_verification_at_checkout' => $require_verification_at_checkout,
//             '$has_required_payment_method' => $has_required_payment_method,
//             '$cart_amount_is_over_minimum' => $cart_amount_is_over_minimum,
//             '$has_product_in_restricted_category' => $has_product_in_restricted_category,
//             '$has_product_in_restricted_tag' => $has_product_in_restricted_tag
//         ));

        $client_requires_verification = apply_filters('tot_is_verification_required_for_order', $requires_verification, $order ? $order->get_id() : null, $cart);
        return $client_requires_verification;
    }
}

class Checkout
{

    /** @var Checkout_Hook_Handler */
    protected $hookHandler;
    protected $text_domain;

    /**
     * Tries to find a valid order id (and ensures it is a WC Order).
     *
     * @param null $order_id
     * @return int|null
     */
    public static function get_current_wc_order_id($order_id = null)
    {
        global $post, $theorder;
        $order = null;
		$tot_debugger = tot_debugger::inst();

        // Try to fetch using current post.
        if (empty($order_id) && class_exists('WooCommerce')) {
            $order = $theorder instanceof \WC_Order ? $theorder : tot_wc_get_order($post->ID);
            $order_id = empty($order) ? null : $order->get_id();
            if (!empty($order_id)) {
                $tot_debugger->add_part_to_operation('', 'Used postId to fetch order_id ', $order_id);
            }
        }

        if (empty($order_id)) {
            // try to fetch from the order received page.

            $wcOrderKeyFromQuery = getWcOrderKeyFromQuery();
            $order_key = !empty($wcOrderKeyFromQuery) ? $wcOrderKeyFromQuery : '';
            if (!empty($order_key)) {
                $order_key = sanitize_text_field($order_key);
                $order_id = wc_get_order_id_by_order_key($order_key);
                $order = tot_wc_get_order($order_id);
                $order_id = empty($order) ? null : $order_id;
                if (!empty($order_id)) {
                    $tot_debugger->add_part_to_operation('', 'Used "key" query param to fetch order_id ', $order_id);
                }
            }
        }

        if (empty($order_id)) {
            $tot_debugger->add_part_to_operation('', 'get_current_wc_order_id:error', 'Unable to find active order.');
        }

        return $order_id;
    }

    public function __construct()
    {
        global $tot_plugin_text_domain;
        $this->hookHandler = Checkout_Hook_Handler::getInstance($this);
        $this->text_domain = $tot_plugin_text_domain;
    }

    public function get_name()
    {
        return "Checkout Initializing";
    }
    public function initialize()
    {

    }

    public function handle_verify_person_api_error($response, $request = null, $url = null, $data = '')
    {
    }

    public function tot_set_excise_cookie() {

        // If the url is ?excise={something}, we're gonna set that as a transient value on the woocommerce_cart_hash.
        // Why that particular method? Because it's the cookie that was available to us on the add fees hook.
        if (isset($_GET['excise']) && isset($_COOKIE['woocommerce_cart_hash'])    ) {
            set_transient('excise' . $_COOKIE['woocommerce_cart_hash'], $_GET['excise'], MINUTE_IN_SECONDS * 10);
        }

        if (isset($_COOKIE['woocommerce_cart_hash'])) {
            $exciseCartHash = get_transient('excise' . $_COOKIE['woocommerce_cart_hash']);
        }
    }

    public function is_exciseTaxEnabled(){
        $myKeys = tot_get_keys();

        if (isset($_COOKIE) && isset($_COOKIE['woocommerce_cart_hash'])) {
            $exciseCartHash = get_transient('excise' . $_COOKIE['woocommerce_cart_hash']);
        }

        if (isset($myKeys) && !is_wp_error($myKeys) && isset($myKeys['exciseTaxEnabled']) && $myKeys['exciseTaxEnabled'] == true) {
            // You have excise taxes enabled via API Keys.

            if (isset($exciseCartHash) &&
                    ($exciseCartHash == "off" || $exciseCartHash == "disabled") ) {
                // I'll let you manually turn it off by doing either ?excise=off or ?excise=disabled in the URL...
                $exciseTaxEnabled = false;
            } else {
                // But if you didn't do that, it's on.
                $exciseTaxEnabled = true;
            }
        } else if (isset($exciseCartHash) && $exciseCartHash != "" && $exciseCartHash != "off" && $exciseCartHash != "disabled") {
            // You have ?excise in the url, and it ISN'T ?excise=off, ?excise=disabled, or simply ?excise
            // So we're gonna turn it on for you.
            $exciseTaxEnabled = true;
        } else {
            // Having failed all that, excise taxes are off.
            $exciseTaxEnabled = false;
        }
        return $exciseTaxEnabled;
    }

    /**
     * Prepare products array for endpoint api/exciseTax/calculate
     * @param type $items
     * @return Array
     */
    public function get_transactionLines($items){
        // Let's see what's in the cart...
        $productArray = array();
        foreach($items as $item => $values) {

            if(isset($values['data'])){
                // for cart

                $_product =  wc_get_product( $values['data']->get_id());
                $productQuantity = $values['quantity'];
            } else if(is_object($values) &&
                    $values instanceof \WC_Order_Item_Product){
                $_product = $values->get_product();
                $productQuantity = $values->get_quantity();
            }

            $productPrice = $_product->get_price();

            $productSKU = $_product->get_sku();
            $p = array();
            if ($_product->get_sku() ) {
                $p['sku'] = $_product->get_sku();
            }
// Note: We were sending this as 'id' previously and are skipping sending it at this time for fear that it could
// have interactions / unexpected side-effects with the SKU attribute.
//
//            if ( $_product->get_id() ) {
//                $p['productId'] = $_product->get_id();
//            }
            if ( isset($productQuantity ) ) {
                $p['quantity'] = $productQuantity;
            } else {
                $p['quantity'] = 1;
            }

            if ($productPrice) {
                $p['unitSalesPrice'] = $productPrice;
            }

            if ( isset($values['bundled_items']) && !is_wp_error($values['bundled_items']) ) {
                // We DON'T want to calculate excise taxes on any item that's actually just a bundle of other items.
                tot_debugger::inst()->log('Excluding a Bundle:', $values['bundled_items']);
            } else {
                // But if it isn't an item, go ahead and add that to the list of items to calculate.
                array_push($productArray,$p);
            }

        }
        return $productArray;
    }
    public function tot_calculate_excise_taxes() {

        $exciseTaxEnabled = $this->is_exciseTaxEnabled();

        // With all that done, here's the excise tax stuff, to be run if it's enabled by the criteria above.
        if ($exciseTaxEnabled == true) {

            // Define cart and customer
            $cart = WC()->cart;
            $cartItems = $cart->get_cart();
            $customer = WC()->customer;

            // Get the customer's shipping location.
            $customerCountry = $customer->get_shipping_country();
            $customerStreetAddress = $customer->get_shipping_address_1();
            $customerCity = $customer->get_shipping_city();
            $customerState = $customer->get_shipping_state();
            $customerZipCode = $customer->get_shipping_postcode();

            $productArray = $this->get_transactionLines($cartItems);

            // Prep the API Request...
            $requestArray = array(
                'shippingLocation' => array(
                   "line1" => $customerStreetAddress,
                   "locality" => $customerCity,
                   "regionCode" => $customerState,
                   "countryCode" => $customerCountry,
                   "postalCode" => $customerZipCode
                ),
                'transactionLines' => $productArray,
                'appTransactionId' => tot_get_traceid(), // as requested
                'traceId' => tot_get_traceid()
            );

			if( $this->user_has_wholesale_role()) {
				$requestArray['transactionType'] = 'wholesale';
			} else {
				$requestArray['transactionType'] = 'retail';
			}

            if (!isset($customerState) || $customerState == "" || $customerCity == "") {
                // Without a city and a state, we don't yet have enough information to process what the excise taxes will be for your order.
                // So, we're going to add a $0 fee, and mention that the tax is pending.
                $cart->add_fee( __('Excise Tax Pending', 'token-of-trust'), 0);
            } else {

                // Okay, we have enough information to request excise taxes,
                // So let's send the info to ToT and try to get back a dollar figure.

                // Try getting excise result from cache
	            // & make sure the condition of the user as a wholesale is the same
	            // otherwise make another request
                if (
                    !(
                        ($excise_result = $this->get_cached_exciseTax_result())
                        && isset($excise_result->user_has_wholesale_role)
                        && $excise_result->user_has_wholesale_role == $this->user_has_wholesale_role()
                        && isset($excise_result->city) && $excise_result->city == $customerCity
                        && isset($excise_result->state) && $excise_result->state == $customerState
                        && isset($excise_result->country) && $excise_result->country == $customerCountry
                        && isset($excise_result->postalCode) && $excise_result->postalCode == $customerZipCode
                    )
                ) {

                    // only send request if it's the checkout page or cart page or any ajax event except adding, or removing
                    $wc_ajax_event = isset($_GET['wc-ajax']) ? sanitize_text_field($_GET['wc-ajax']) : '';
                    if (!is_cart() && !is_checkout()
                            && in_array($wc_ajax_event, ['add_to_cart','remove_from_cart'])){
                        return;
                    }
                    $request = new API_Request('api/exciseTax/calculate', $requestArray, 'POST');
                    $excise_result = $request->send();

					tot_debugger::inst()->log('excise_result', $excise_result);

	                $excise_result->user_has_wholesale_role = $this->user_has_wholesale_role();
                    $excise_result->city = $customerCity;
                    $excise_result->state = $customerState;
                    $excise_result->country = $customerCountry;
                    $excise_result->postalCode = $customerZipCode;
	                // cache it
                    $this->cache_exciseTax_result($excise_result);
                }
                if (isset($excise_result) && isset($excise_result->exciseTaxes) && isset($excise_result->exciseTaxes->summary) && isset($excise_result->exciseTaxes->summary->totalTaxes)) {
                   $excise_taxAmount = $excise_result->exciseTaxes->summary->totalTaxes;
                } else {
                    $excise_taxAmount = null;
                }

                if (isset($excise_taxAmount) && $excise_taxAmount != 0) {
                    // Okay, we've got a tax amount (more than $0) from the API service, let's add it as a fee.
                    $surcharge = $excise_taxAmount;
                    $label = $excise_result->exciseTaxes->summary->taxLabel ?? sprintf( __( '%s Excise Tax', 'token-of-trust' ), $customerState );
                    $cart->add_fee( $label, $surcharge);
                } else {
                    // But if that's not the case, we have all the info we're going to have, and we didn't end up with an amount.
                    // So therefore, we're just gonna add a $0 line item and say that you don't get any excise taxes.
                    $surcharge = 0;
                    $cart->add_fee( __('No Excise Tax', 'token-of-trust'), $surcharge);
                }

            }
        }
    }

    public function cache_exciseTax_result($excise_result){
        $wc = \WC();
        $wc->session->set('_exciseTax_result', $excise_result);
    }
    public function get_cached_exciseTax_result(){
        $wc = \WC();
        $excise_result = $wc->session->get('_exciseTax_result');

        return $excise_result;
    }
    public function delete_cached_exciseTax_result(){
        $wc = \WC();
        $wc->session->set('_exciseTax_result', null);
    }

    public function tot_send_taxCollected($order){
        if(! $this->is_exciseTaxEnabled()) {
            return;
        }

        $order = tot_wc_get_order($order);

        // if it was sent before
        if ($order->get_meta('exciseTaxStatus', true) == 'RECONCILED'){
            return;
        }

        // Get the customer's shipping location.
        $customerCountry = $order->get_shipping_country();
        $customerStreetAddress = $order->get_shipping_address_1();
        $customerCity = $order->get_shipping_city();
        $customerState = $order->get_shipping_state();
        $customerZipCode = $order->get_shipping_postcode();

        $productArray = $this->get_transactionLines($order->get_items());

        /* @var Array<WC_Order_Item_Fee> $fees */
        $fees = $order->get_fees();
        if (!is_array($fees)){
            return;
        }
        // extract excise fee
        $taxCollected = 0;
        $excise_result = $this->get_cached_exciseTax_result();
        $label = $excise_result->exciseTaxes->summary->taxLabel ?? sprintf( __( '%s Excise Tax', 'token-of-trust' ), $customerState );

        // make sure the excise tax is collectd by woocommerce
        foreach ($fees as $key => $fee) {
            if (
                is_object($fee)
                && $excise_result->exciseTaxes->summary->totalTaxes == $fee->get_total()
                && $label == $fee->get_name()
            ) {
                $taxCollected = $fee->get_total();
                break;
            }
        }

        // prep array
        $requestArray = array(
            'shippingLocation' => array(
                "line1" => $customerStreetAddress,
                "locality" => $customerCity,
                "regionCode" => $customerState,
                "countryCode" => $customerCountry,
                "postalCode" => $customerZipCode
             ),
            'transactionLines' => $productArray,
            'taxCollected' => $taxCollected,
            'appTransactionId' => tot_get_transaction_id($order),
            'traceId' => tot_get_traceid($order)
        );

        if( $this->user_has_wholesale_role()) {
            $requestArray['transactionType'] = 'wholesale';
        } else {
            $requestArray['transactionType'] = 'retail';
        }

        $request = new API_Request('api/exciseTax/calculate', $requestArray, 'POST');
        $excise_result = $request->send();

        // Store status after success
        if (isset($excise_result->code) && isset($excise_result->status) &&
                $excise_result->code == 'exciseTax:ok' && $excise_result->status == '200'){
            $order->update_meta_data('totTaxCollected', $taxCollected);
            $order->update_meta_data('exciseTaxStatus', 'RECONCILED');
            $order->save();
        }
    }

	/**
	 * @return bool
	 */
	public function user_has_wholesale_role() {

		return tot_user_has_wholesale_role();
	}
}

/** Bridge allows us to create a kill switch. **/
class Checkout_Bridge extends Checkout
{

    private $bridge;

    public function __construct()
    {
        parent::__construct();
        add_action('woocommerce_init', array($this->hookHandler, 'woocommerce_init'));

        if (is_admin()) {
            // fires on admin dashboard
            // to work with plugins like (phone orders)
            add_action('admin_init', array($this->hookHandler, 'initialize'), 10);
        } else {
            add_action('wp', array($this->hookHandler, 'initialize'), 5);
        }

    }

    public function woocommerce_init()
    {
        tot_add_query_params('excise');
        if (tot_debug_mode()) {
            tot_add_query_params('checkout_require');
        }

        // Generate a new cart hash and delete cache of taxCollected whenever a change happened to the cart items even
        add_action( 'woocommerce_cart_item_removed', array($this->hookHandler,'refresh_session'));
        add_action( 'woocommerce_add_to_cart', array($this->hookHandler,'refresh_session'));
        add_action( 'woocommerce_after_cart_item_quantity_update', array($this->hookHandler,'refresh_session'), 10);

        // excise tax before checkout
        add_action('woocommerce_cart_calculate_fees', array($this->hookHandler, 'tot_calculate_excise_taxes'));

        // excise tax after order is created
        add_action('woocommerce_checkout_order_processed', array($this->hookHandler, 'tot_send_taxCollected'), 10, 1);
        add_action('woocommerce_checkout_order_processed', 'tot_store_orderType', 10, 1);
    }

    public function refresh_session() {
        tot_generate_cart_hash();
        $this->delete_cached_exciseTax_result();
    }

    /**
     * Take care to run as late as possible.
     */
    public function initialize()
    {
		$keys = tot_get_keys();
        // Don't initialize if there are no stored keys
		if (is_wp_error($keys) || $keys === false) {
			return;
		}

        tot_check_for_query_cookie('checkout_require');
        $this->tot_set_excise_cookie();

        if (!isset($this->bridge)) {
            $verification_on_checkout = Settings::get_setting('checkout_require');
            if (empty($verification_on_checkout)) {
                $this->bridge = new Checkout_Disabled();
            } elseif (class_exists('TOT\Integrations\WooCommerce\MOCK_Checkout_Enabled_ERRORS')) {
                $this->bridge = MOCK_Checkout_Enabled_ERRORS::getInstance();
            } else {
                $this->bridge = Checkout_Enabled::getInstance();
            }
            $this->bridge->initialize();
        }
    }

    public function handle_verify_person_api_error($response, $request = null, $url = null, $data = '')
    {
        $this->bridge->handle_verify_person_api_error($response, $request, $url, $data);
    }

    public function get_name()
    {
        if (isset($this->bridge)) {
            return $this->bridge->get_name();
        } else {
            return parent::get_name();
        }
    }
}

class Checkout_Disabled extends Checkout
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_name()
    {
        return "Checkout_Disabled";
    }
}

function getWcOrderKeyFromQuery()
{
    foreach (['ctp_order_key', 'key'] as $key) {
        $value = get_query_var($key, NULL);
        if (!empty($value)) {
            return $value;
        }
    }
}
