<?php

namespace TOT\Integrations\WooCommerce;

use TOT\Settings\Page;

class Settings
{
    private $text_domain;

    function __construct()
    {
        global $tot_plugin_text_domain;
        $this->text_domain = $tot_plugin_text_domain;
        add_action('woocommerce_init', array($this, 'register_wordpress_hooks_after_load'));
    }

    public function register_wordpress_hooks_after_load()
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        add_action('admin_menu', array($this, 'create_page'));
        add_action('admin_notices', array($this, 'admin_notice'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function admin_init()
    {
        if (isset($_GET['page']) && (sanitize_text_field($_GET['page']) != 'tot_settings_woocommerce')) {
            return;
        }

        if (!$this->is_beta_enabled()) {
            tot_refresh_keys();
        }
    }

    public function is_beta_enabled()
    {
            return true;
    }

    public function admin_notice()
    {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

	    $prod_domain = tot_get_setting_prod_domain();
		$site_domain = tot_scrub_prod_domain(get_site_url());

		// if the prodDomain is not the same as this website domain
		if ($prod_domain && !strpos($site_domain, 'localhost') && $prod_domain != $site_domain) {

			// if keys aren't updated yet then force refresh
			if (tot_test_keys()) {
				$response = tot_refresh_keys();
			}

			// Display Notice if failed to Connect
			if (!tot_test_keys()) {
				$msg = '';
				if (isset($response) && is_wp_error($response)) {
					$response = $response->get_error_message();
					preg_match('/tot-error=(\w+)/', $response, $matches);
					$msg = isset($matches[1]) ? get_transient( 'tot_error_' . $matches[1]) : '';
				} else {
					$msg = print_r($msg, true);
				}
				$mailto_link = 'mailto:onboarding@tokenoftrust.com?subject=' . $prod_domain . '%20License%20Key%20Needs%20Update&body=Hello%20Token%20of%20Trust%20Support%20Team%2C%0D%0A%0D%0AThis%20is%20not%20an%20emergency%20since%20it%20relates%20to%20a%20development%2Ftest%20site.%20%5Bdelete%20if%20this%20is%20a%20production%20site%5D.%0D%0A%0D%0AI%20received%20a%20warning%20message%20today%20from%20Token%20of%20Trust%20on%20Wordpress%20that%20is%20telling%20me%20that%20%22Token%20of%20Trust%20API%20keys%20are%20not%20licensed%20to%20run%20here%22.%20I%20understand%20that%20for%20security%20reasons%20the%20license%20is%20tied%20to%20the%20server%2Fdomain%20and%20need%20to%20get%20that%20updated%20so%20that%20I%20can%20continue%20using%20Token%20of%20Trust.%20Please%20help.%0D%0A%0D%0ADetails%20are%20below.%0D%0A%0D%0AThank%20you!%0D%0A%0D%0A---%20Technical%20Details%20---%0D%0A%0D%0AappDomain%3A%20' . $prod_domain . '%0D%0ALive%20Site%20Domain%3A%20' . $site_domain . '%0D%0AThe%20TOT%20API%20Response%3A%0D%0A' .  str_replace("%5C","",rawurlencode($msg));
				printf('<div class="notice notice-error is-dismissible"><p>%s (<a href="%s">%s</a>) %s</p></div>'
					, __('You\'ve changed your Wordpress host but your Token of Trust API keys are not licensed to run here. Please', $this->text_domain)
					, $mailto_link
					, __('Click here', $this->text_domain)
					,__('So we can get your license updated', $this->text_domain)
				);
			}

		} else if ($page != 'totsettings' && $page != 'totsettings_quickstart' && (
                !tot_option_has_a_value(tot_get_setting_license_key()) || !tot_option_has_a_value($prod_domain)
                )) {
	        // the license keys are missing
            $origin = tot_production_origin();
            $version = tot_plugin_get_version();
            $img_url = "{$origin}/external_assets/wordpress/{$version}/welcome/logo.svg";
            $img_tag = "<span class=\"tot-notice-icon\"><img src=\"{$img_url}\"/></span>";

            printf('<div class="notice notice-success is-dismissible"><p>%s%s<a href="admin.php?page=totsettings">%s</a>.</p></div>',
                $img_tag,
                __('Thank you for installing Token of Trust! ', $this->text_domain),
                __('Get Started Now', $this->text_domain)
            );

        }

        if ($page != 'tot_settings_woocommerce') {
            return;
        }

        if ($this->is_beta_enabled()) {
            return;
        }
    }

    public function create_page()
    {

        if (!$this->is_beta_enabled()) {
            new Page('WooCommerce', array());
            return;
        }

        $payment_options = array();
        $gateways = WC()->payment_gateways->payment_gateways();
        if (!is_wp_error($gateways)) {
            forEach ($gateways as $method) {
                if(isset( $method->enabled ) && 'yes' === $method->enabled  ) {
                    array_push($payment_options, array(
                        'label' => $method->title,
                        'value' => $method->id
                    ));
                }
            }
            // List disabled AFTER enabled.
            forEach ($gateways as $method) {
                if(!isset( $method->enabled ) || 'yes' !== $method->enabled  ) {
                    array_push($payment_options, array(
                        'label' => $method->title . ' (disabled)',
                        'value' => $method->id
                    ));
                }
            }
        }

        $shipping_options = array();
        $shipping_methods = WC()->shipping->get_shipping_methods();
        // tot_log_as_html_pre("Shipping_Methods", $shipping_methods);
        if (!is_wp_error($shipping_methods)) {
            forEach ($shipping_methods as $method) {
                if(isset( $method->enabled ) && 'yes' === $method->enabled  ) {
                    array_push($shipping_options, array(
                        'label' => $method->method_title,
                        'value' => $method->id
                    ));
                }
            }
            // List disabled AFTER enabled.
            forEach ($shipping_methods as $method) {
                if(!isset( $method->enabled ) || 'yes' !== $method->enabled  ) {
                    array_push($shipping_options, array(
                        'label' => $method->method_title . ' (disabled)',
                        'value' => $method->id
                    ));
                }
            }
        }

        $category_options = array();
        $category_results = get_terms('product_cat', array(
            'orderby' => 'name',
            'order' => 'asc',
            'hide_empty' => false,
        ));
        if (!is_wp_error($category_results)) {
            forEach ($category_results as $category) {
                array_push($category_options, array(
                    'label' => $category->name,
                    'value' => $category->term_id
                ));
            }
        }

        $tag_options = array();
        $tag_results = get_terms('product_tag', array(
            'orderby' => 'name',
            'order' => 'asc',
            'hide_empty' => false,
        ));
        if (!is_wp_error($tag_results)) {
            forEach ($tag_results as $tag) {
                array_push($tag_options, array(
                    'label' => $tag->name,
                    'value' => $tag->term_id
                ));
            }
        }
        $role_options = array();
        $role_results = get_editable_roles();
        if(!empty($role_results)){
            foreach ($role_results as $role){
                array_push($role_options, array(
                    'label' => $role['name'],
                    'value' => strtolower($role['name'])
                ));
            }
        }
        $try_order_verification_url = get_site_url() . '?checkout_require=1';
        $try_order_verifications_anchor = '<a href="' . $try_order_verification_url . '">' . $try_order_verification_url . '</a>';

        new Page('WooCommerce', array(
            __('Order Verification', $this->text_domain) => array(
                'description' => '',
                'fields' => array(
                    array(
                        'id' => 'checkout_require',
                        'type' => 'checkbox',
                        'label' => __('Enable checkout verification', $this->text_domain),
                        'options' => array(
                            'prepend' => array(),
                            'append' => array(
                                __("Enabling this turns on Token of Trust verification on WooCommerce checkouts. If left unchecked Token of Trust will <i>not be enabled</i> during the WooCommerce checkout process and <i>none</i> of the settings below will take effect. ", $this->text_domain)
                                . '<br />'
                                . __('<div style="font-size:80%; line-height:140%; margin-top:1em;"><strong>Advanced:</strong> To test without enabling for your entire site, copy this link, paste into a new incognito window and go through the checkout process. Your incognito session will behave as if checkout verification is active for about 30 minutes: ' . $try_order_verifications_anchor . '</div>', $this->text_domain)
                            )
                        )
                    ),
                    array(
                        'id' => 'woo_enable_verification_before_payment',
                        'type' => 'checkbox',
                        'label' => __('Verify Before Payment', $this->text_domain),
                        'options' => array(
                            'prepend' => array(
                            ),
                            'append' => array(
                                __('<strong style="color:#164c16;">Strongly Recommended</strong>. When checked Token of Trust will attempt to attach to the the checkout process before payment. If checkout verification is enabled and this option is not selected Token of Trust will ask for verification on the Thank You / Receipt page - after payment has been collected.', $this->text_domain)
                                . '<br />'
                                . __('<div style="font-size:80%; line-height:140%; margin-top:1em;"><strong>Advanced:</strong> If this is enabled but Token of Trust does not launch on checkout (possible for custom themes) - take a look at the <a href="#tot_field_woo_verification_before_payment_selector">Verification Before Payment (jQuery) Selector</a> in the Advanced section below.</div>', $this->text_domain)
                            ),
                            'default_value' => true
                        )
                    ),
                ),
            ),
            __('Orders Considered for Verification', $this->text_domain) => array(
                'description' => __('Orders matching ANY of these conditions are considered for verification.', $this->text_domain),
                'fields' => array(
                    array(
                        'id' => 'checkout_require_total_amount',
                        'type' => 'currency',
                        'label' => __('Minimum order amount', $this->text_domain),
                        'options' => array(
                            'prepend' => array(),
                            'append' => array(
                                __("<strong>Example: 4.00</strong> &nbsp; Require verification for orders that are more than this amount (in currency). <code>0</code> will trigger verification on all orders. Leaving this empty means <i>no orders will trigger verification</i>.", $this->text_domain)
                            )
                        )
                    ),
                    array(
                        'id' => 'checkout_require_categories',
                        'type' => 'multiselect',
                        'label' => __('Categories', $this->text_domain),
                        'options' => array(
                            'prepend' => array(),
                            'append' => array(
                                __('Always require verification for orders containing a product with any of these categories.', $this->text_domain)
                            ),
                            'options' => $category_options
                        )
                    ),
                    array(
                        'id' => 'checkout_require_tags',
                        'type' => 'multiselect',
                        'label' => __('Tags', $this->text_domain),
                        'options' => array(
                            'prepend' => array(),
                            'append' => array(
                                __('Always require verification for orders containing a product with any of these tags.', $this->text_domain)
                            ),
                            'options' => $tag_options
                        )
                    )
                )
            ),
            __('Order Verification Requirements', $this->text_domain) => array(
                'description' => __('Leave these fields alone to verify all "Orders Considered for Verification" above. Add criteria below to limit considered orders to only those meeting these requirements.', $this->text_domain),
                'fields' => array(
                    array(
                        'id' => 'checkout_require_payment_methods',
                        'type' => 'multiselect',
                        'label' => __('Payment Methods Requiring Verification', $this->text_domain),
                        'options' => array(
                            'prepend' => array(),
                            'append' => array(
                                __('Require verification for orders using these payment methods. Leave empty to accept all current and future payment methods.', $this->text_domain)
                            ),
                            'options' => $payment_options
                        )
                    ),
                    array(
                        'id' => 'checkout_require_shipping_methods',
                        'type' => 'multiselect',
                        'label' => __('Shipping Methods Requiring Verification', $this->text_domain),
                        'options' => array(
                            'prepend' => array(),
                            'append' => array(
                                __('Require verification for orders using these shipping methods. Leave empty to accept all current and future shipping methods.', $this->text_domain)
                            ),
                            'options' => $shipping_options
                        )
                    ),
                )
            ),

            __('Advanced', $this->text_domain) => array(
                'description' => 'Only modify these options if you\'ve been directed to do so by Token of Trust support.',
                'fields' => array(
                    array(
                        'id' => 'roles_pass_checkout_verification',
                        'type' => 'multiselect',
                        'label' => __('Roles to bypass Token of Trust Verification', $this->text_domain),
                        'options' => array(
                            'prepend' => array(),
                            'append' => array(
                                __('When no roles are specified all roles will be required to get verified.', $this->text_domain)
                            ),
                            'options' => $role_options
                        )
                    ),
	                array(
		                'id' => 'roles_as_wholesalers',
		                'type' => 'multiselect',
		                'label' => __('Roles that should be treated as ecommerce wholesalers', $this->text_domain),
		                'options' => array(
			                'prepend' => array(),
			                'append' => array(
				                __('When no roles are specified all roles will be treated as retailers.', $this->text_domain)
			                ),
			                'options' => $role_options
		                )
	                ),
                    array(
                        'id' => 'woo_verification_before_payment_selector',
                        'type' => 'text',
                        'label' => __('Verification Before Payment (jQuery) Selector', $this->text_domain),
                        'options' => array(
                            'prepend' => array(
                            ),
                            'append' => array(
                                __('The (jQuery style) selector to use to find the elements to bind verification to. The default selector is <strong>#place_order</strong>.', $this->text_domain),
                                __('<div style="font-size:80%; line-height:140%; margin-top:1em;">Developers and integrators can test their value from the console via <code style="font-size:8px;">$(selectorValue)</code>.</div>')
                            ),
                            'default_value' => '#place_order'
                        )
                    ),
                    array(
                        'id' => 'woo_trigger_verification_on_original_button',
                        'type' => 'checkbox',
                        'label' => __('Trigger Verification On Original Button', $this->text_domain),
                        'options' => array(
                            'prepend' => array(),
                            'append' => array(
                                __("By default, Token of Trust will clone your store's checkout button in order to implement the verification system. Check this to instead leave the button alone, and trigger the verification system on click of the original button (specified above)", $this->text_domain),
                                __('<div style="font-size:80%; line-height:140%; margin-top:1em;">This feature is meant to help Token of Trust play nicely with other plugins. If you are having issues with the Verification modal, try changing this value.</div>')
                            )
                        )

                    ),
                    array(
                        'id' => 'woo_disable_status_on_orders_page',
                        'type' => 'checkbox',
                        'label' => __('Disable "Verification Status"', $this->text_domain),
                        'options' => array(
                            'prepend' => array(
                            ),
                            'append' => array(
                                __('Checking this will remove the <strong>Verification Status</strong> column from the WooCommerce Orders page.', $this->text_domain)
                            )
                        )

                    ),
                    array(
                        'id' => 'woo_enable_tot_states',
                        'type' => 'checkbox',
                        'label' => __('Enable "Awaiting Verification" and "Ready for Review".', $this->text_domain),
                        'options' => array(
                            'prepend' => array(
                            ),
                            'append' => array(
                                __('Allows TOT to move orders into custom WooCommerce Statuses "Awaiting Verification" and "Ready for Review".', $this->text_domain)
                                . '<br />'
                                . __('<div style="font-size:80%; line-height:140%; margin-top:1em;"><strong style="color:#aa1111">Warning:</strong> this feature can result in unintended behaviors related to your payment gateway as well as incompatibilities with other features and plugins that depend upon WooCommerce Status.', $this->text_domain)
                            )
                        )
                    ),
                    array(
                        'id' => 'dont_force_accept_on_app_approve',
                        'type' => 'checkbox',
                        'label' => __("Admin Review Can't Override", $this->text_domain),
                        'options' => array(
                            'prepend' => array(),
                            'append' => array(
                                __('Checking this means that documents (e.g. govt ID) are approved but other rules (e.g. age) are still verified independently to determine if the order can automatically move out of "Awaiting Verification".', $this->text_domain)
                            )
                        )
                    ),
                    array(
                        'id' => 'woo_debounce_payment_btn_click',
                        'type' => 'checkbox',
                        'label' => __('Debounce Payment Button', $this->text_domain),
                        'options' => array(
                            'prepend' => array(
                            ),
                            'append' => array(
                                __("Some payment gateway plugins are vulnerable multiple end-user clicks. Although it is not a Token of Trust problem - this debounce option will stop the 2nd click. Note do not enable unless you're directed to do so because some plugins seem to depend upon this behavior.", $this->text_domain)
                            ),
                            'default_value' => false
                        )

                    ),
                    array(
                        'id' => 'woo_charity',
                        'type' => 'radio',
                        'label' => __('Round up for Charity', $this->text_domain),
                        'options' => array(
                            'prepend' => [],
                            'append' => [],
                            'options' => [
                                ['label' => __('Deactivated.', $this->text_domain), 'value' => 'deactivated'],
                                ['label' => __('Round up.', $this->text_domain), 'value' => 'roundup'],
                                ['label' => __('Add Flat amount to order', $this->text_domain), 'default_value' => 5]
                            ],
                            'default_value' => 'deactivated',
                        ),
                        'extra_fields' => array(
                            array(
                                'id' => 'woo_charity_name',
                                'type' => 'text',
                                'label' => __('Name of charity:', $this->text_domain),
                                'options' => array(
                                    'prepend' => array(),
                                    'append' => array(),
                                    'default_value' => '',
                                )
                            ),
                            array(
                                'id' => 'woo_charity_url',
                                'type' => 'text',
                                'label' => __('URL of charity:', $this->text_domain),
                                'options' => array(
                                    'prepend' => array(),
                                    'append' => array(),
                                    'default_value' => ''
                                )
                            ),
                        )
                    ),
                    array(
                        'id' => 'woo_charity_opt',
                        'type' => 'radio',
                        'label' => __('Default to Opt into Charity', $this->text_domain),
                        'options' => array(
                            'prepend' => [],
                            'append' => [],
                            'options' => [
                                [
                                    'label' => __('opt-in - causes the donation to be UNCHECKED when the checkout page is initially rendered.', $this->text_domain),
                                    'value' => 'in'
                                ],
                                [
                                    'label' => __('opt-out - causes the donation to be CHECKED when the checkout page is initially rendered.', $this->text_domain),
                                    'value' => 'out'
                                ],
                            ],
                            'default_value' => 'in',
                        ),
                    ),
					array(
						'id' => 'woo_enable_product_sync',
						'type' => 'checkbox',
						'label' => __('Enable Product syncing', $this->text_domain),
						'options' => array(
							'prepend' => array(),
							'append' => array(
								__("Allows TOT to sync products automatically to <a target='_blank' href='" . tot_origin() . "/hq/excise-tax/products'>the Excise Tax Products Dashboard</a>", $this->text_domain),
							)
						)

					),
                    array(
                        'id' => 'enable_backend_checkout_verification',
                        'type' => 'checkbox',
                        'label' => __('Enable Server-side confirmation of verification before checkout', $this->text_domain),
                        'options' => array(
                            'prepend' => array(),
                            'append' => array(
                                __('<strong style="color:#164c16;">Recommended</strong>. Server side confirmation of cleared status ensuring that verification requirement cannot be bypassed by the client side prior to payment.', $this->text_domain),
                            )
                        )
                    ),
                ),
            ),
        ));
    }

}
