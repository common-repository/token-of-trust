<?php
/**
 *
 * TOT API API_Person Request
 *
 * Sends a message to attempt to verify a person or order (related to a person).
 *
 * References
 *   - TOT API docs
 *     https://docs.google.com/document/d/1xQ9yymU1CVt5BWxNsmkI76S3otrMO1kuW3HruMkcKrk/edit#heading=h.iuu091a3j2tn
 *     https://app.tokenoftrust.com/developer/guide/embed/
 *
 */

namespace TOT;

class API_Person extends API_Request
{

    public $endpoint_url;
    public $request_details;

    public function __construct($data = array(), $method = 'POST', $headers = array())
    {
        parent::__construct('api/person', $data, $method, $headers);
    }

    public function set_details_from_order($order = array(), $method = 'POST', $headers = array())
    {
        $order_id = $order->get_id();

        if (!$order_id) {
            return new WP_Error('tot-no-order-id', 'There was a problem finding this order');
        }

        $tot_transaction_id = $order->get_meta('tot_transaction_id', true);
        if (!$tot_transaction_id) {
            $tot_transaction_id = tot_create_guid();
            $order->update_meta_data('tot_transaction_id', $tot_transaction_id);
            $order->save();
        }

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
        
        $traceId = tot_get_traceid($order, $order->get_user_id());

        $verify_person_data = [
            'appTransactionId' => $tot_transaction_id,
            'person' => $person,
            'traceId' => $traceId
        ];

        $orderUserid = $order->get_user_id();
        if (!$orderUserid) {
            $verify_person_data['guest'] = 'true';
        }
        $verify_person_data['appUserid'] = tot_user_id($orderUserid, $order, false);
        $order_whitelist_data = $this->get_order_whitelist_data($order_id);
        if ($order_whitelist_data) {
            $verify_person_data['person']['preApproved'] = $order_whitelist_data;
        }

        $verify_person_data = apply_filters('tot_verify_person_data', $verify_person_data);
        $this->set_details($verify_person_data, $method, $headers);
    }

    public function set_details_from_appData($appData = array(), $method = 'POST', $headers = array())
    {
        $appData = (array)$appData;

        // Default to using billing information.
        $use_shipping_details = ! $appData['billing_first_name'];
        if ($use_shipping_details) {
            $first_name = $appData['shipping_first_name'];
            $last_name = $appData['shipping_last_name'];
        } else {
            $first_name = $appData['billing_first_name'];
            $last_name = $appData['billing_last_name'];
        }
        $email = $appData['billing_email'];
        $phoneNumber= $appData['billing_phone'];

        $person = [
            'givenName' => $first_name,
            'familyName' => $last_name,
            'email' => $email,
            'phoneNumber' => $phoneNumber
        ];

        // TODO - this need to change to have the reservationToken.
        if (!empty($appData['billing_city'])) {
            $billingLocation = [
                'countryCode' => $appData['billing_country'],
                "line1" => $appData['billing_address_1'],
                'line2' => $appData['billing_address_2'],
                'locality' => $appData['billing_city'],
                'region' => $appData['billing_state'],
                'postalCode' => $appData['billing_postcode']
            ];
            $person['billingLocation'] = $billingLocation;
        }

        if (!empty($appData['shipping_city'])) {
            $shippingLocation = [
            'countryCode' => $appData['shipping_country'],
                "line1" => $appData['shipping_address_1'],
                'line2' => $appData['shipping_address_2'],
                'locality' => $appData['shipping_city'],
                'region' => $appData['shipping_state'],
                'postalCode' => $appData['shipping_postcode']
            ];
            $person['shippingLocation'] = $shippingLocation;
        }

        if (!empty($person['billingLocation'])) {
            $person['location'] = $person['billingLocation'];
        } else {
            $person['location'] = $person['shippingLocation'];
        }

        
        $traceId = tot_get_traceid(false, isset($appData['user_id']) ? $appData['user_id'] : false);
        
        $verify_person_data = [
            'person' => $person,
            'traceId' => $traceId,
            'reservation' => 'true'
        ];

        $appTransactionId = isset($appData['$appTransactionId']) ? $appData['$appTransactionId'] : null;
        if ($appTransactionId) {
            $verify_person_data['appTransactionId'] = $appTransactionId;
        }

        $orderUserid = get_current_user_id();
        if (!$orderUserid) {
            $verify_person_data['guest'] = 'true';
        } else {
            $verify_person_data['appUserid'] = tot_user_id($orderUserid, $appData, false);
        }

        $verify_person_data = apply_filters('tot_verify_person_data', $verify_person_data);
        $this->set_details($verify_person_data, $method, $headers);
    }
    public function set_totReservationToken($totReservationToken)
    {
        $this->data['appReservationToken'] = $totReservationToken;
        $this->set_details($this->data, $this->request_details['method'], $this->request_details['headers']);
    }

    public function get_order_whitelist_data($order_id = null)
    {
        return apply_filters('tot_order_whitelisted_data', false, $order_id);
    }


    /**
     *        $data = [
     *            'appTransactionId' => $tot_transaction_id, // optional - if there's a specific transaction.
     *            'appUserid' => $tot_transaction_id,        // optional - if there's a specific user.
     *            'person' => [
     *                'givenName' => $first_name,
     *                'familyName' => $last_name,
     *                'email' => $email,
     *                'location' => [
     *                    'countryCode' => $country,
     *                    "line1" => $address_1,
     *                    'line2' => $address_2,
     *                    'locality' => $city,
     *                    'region' => $state,
     *                    'postalCode' => $postcode
     *                ]
     *            ]
     *        ];
     *
     * @param array $data
     * @param string $method
     * @param array $headers
     */
    public function set_details($data = array(), $method = 'POST', $headers = array())
    {
        $data = apply_filters('tot_verify_person_data', $data);
        return $this->do_set_details($data, $method, $headers);
    }

    public function do_set_details($data = array(), $method = 'POST', $headers = array())
    {
        return parent::set_details($data, $method, $headers);
    }

    public static function handle_verify_person_api_error($response, $request = null, $url = null, $data = '')
    {
        $tot_debugger = tot_debugger::inst();
        $tot_debugger->register_new_operation(__FUNCTION__);


	    $tot_debugger->add_part_to_operation('','handle_verify_person_api_error', $response);

        $appDomain = tot_get_setting_prod_domain();
        $contextString = 'When executing verify person.';

        if (isset($response->body_decoded->continuation)
            && isset($response->body_decoded->continuation->modalType)
            && 'continue' === $response->body_decoded->continuation->modalType) {

            // @codingStandardsIgnoreStart
            ?>
            <script>
                (function ($) {

                    // This is required to ensure the modal type and params for 'openModal' within tot-get-verified.js
                    function setupTotModal() {
                        var totModalType = '<?php echo esc_js($response->body_decoded->continuation->modalType) ?>';
                        var totModalParams = <?php echo json_encode($response->body_decoded->continuation->params)?>;
                        window.totModalType = totModalType;
                        window.totModalParams = totModalParams;
                    }

                    setupTotModal();

                    var somethingHappened = false;
                    tot('bind', 'modalFormSubmitted', function () {
                        somethingHappened = true;
                    });
                    tot('bind', 'modalClose', function (evt) {
                        if (somethingHappened) {
                            console.warn('Closing modal. ', evt);
                            window.location.reload();
                            var elements = document.querySelectorAll('.tot-wc-order-validation');
                            for (var i = 0; i < elements.length; i++) {
                                elements[i].innerHTML = 'Reloading verification, this may take a minute.';
                            }
                        }
                    });
                })(jQuery);
            </script>
            <?php
            // @codingStandardsIgnoreEnd
        } else {
            if ($response->error_with_response()) {
                // Assemble a mailto...

                $mailToLink = 'mailto:support+wordpress@tokenoftrust.com?'
                    . 'subject=' . "Error when Verifying Person"
                    . '&body=PLEASE DELETE THIS BLOCK AFTER TAKING A MOMENT TO PROVIDE THE DETAILS BELOW. %0D%0A%0D%0A'
                    . '----------------------------------------%0D%0A%0D%0A'
                    . "Hello Token of Trust Support Team,%0D%0A%0D%0AI'm contacting you because I encountered an error while trying to checkout from " . $appDomain . '.%0D%0A%0D%0A'
                    . '# PLEASE PROVIDE DETAILS RELATED TO WHAT YOU WERE DOING #%0D%0A%0D%0A'
                    . 'Thank you!%0D%0A%0D%0A'
                    . '-------------Application Data---------------------%0D%0A%0D%0A'
                    . '%0D%0AAppDomain: ' . $appDomain . '%0D%0A'
                    . 'Context : ' . $contextString . '%0D%0A'
                    . 'Request Url: ' . $url . '%0D%0A%0D%0A'
// Don't send sensitive personal data via email.
//                . 'App Data: ' . urlencode(json_encode($data, JSON_UNESCAPED_UNICODE)) . '%0D%0A%0D%0A'
                    . 'Error Response:%0D%0A' . urlencode(json_encode($response->body_decoded, JSON_UNESCAPED_UNICODE));

	            $tot_debugger->add_part_to_operation('','Assembling mailto link', $mailToLink);

                wc_add_notice(
                    '<span class="tot-wc-order-validation">'
                    . __('There was a problem trying to setup your verification. ', "token-of-trust")
                    . '<a href="' . $mailToLink . '">'
                    . __('Click here ', "token-of-trust")
                    . '</a> '
                    . __(' to send a quick note to Token of Trust so we can help resolve the issue.', "token-of-trust")
                    . '</span>',
                    'error'
                );

                // wc_print_notices();

                // @codingStandardsIgnoreStart
                ?>
                <script>
                    /** tot-debug - to make the problem accessible to support **/
                    (function ($) {
                        var errorString = JSON.parse('<?php echo json_encode($response->body_decoded); ?>');
                        var contextString = "<?php echo esc_js($contextString); ?>";
                        var appDomain = "<?php echo esc_js($appDomain); ?>";
                        console.error("Problem while trying to verify person: ", contextString, appDomain, errorString);
                    }(jQuery));
                </script>
                <?php
            }
        }
        $tot_debugger->log_operation(__FUNCTION__);
    }
}