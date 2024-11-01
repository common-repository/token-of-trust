<?php

namespace TOT\Integrations\WooCommerce;

use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use TOT\Current_User;
use TOT\Settings;

class Admin {

	public function __construct() {
        add_action('widgets_init', array($this, 'initialize'));
        // add_action('plugins_loaded', array($this, 'register_wordpress_hooks_after_load'));
    }

	public function initialize() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        add_action('add_meta_boxes', array($this, 'order_detail_meta_boxes'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_tot_wc_order_unquarantine', array($this, 'order_unquarantine'));
        add_action('wp_ajax_tot_wc_email_reminder', array($this, 'email_reminder'));

        $woo_disable_status_on_orders_page = Settings::get_setting('tot_field_woo_disable_status_on_orders_page');

        if (empty($woo_disable_status_on_orders_page)) {
            // Before HPOS
            add_filter('manage_edit-shop_order_columns', 'show_tot_verified_status', 15);
            add_action('manage_shop_order_posts_custom_column', 'tot_shop_orders_verified_indicator', 50, 2);

            // using HPOS
            add_filter('manage_woocommerce_page_wc-orders_columns', 'show_tot_verified_status', 15);
            add_action('manage_woocommerce_page_wc-orders_custom_column', 'tot_shop_orders_verified_indicator', 50, 2);
        }
    }

	public function order_detail_meta_boxes($screen, $order) {
        if (
            $screen !== 'shop_order' // before HPOS
            && $screen !== 'woocommerce_page_wc-orders' // after HPOS
        ){
            return;
        }

        // https://developer.wordpress.org/reference/functions/add_meta_box/
        add_meta_box(
            'tot_order_detail_verification_details_meta_box',
            __('Identity Verification', 'token-of-trust'),
            array($this, 'render_order_detail_verification_details_meta_box_content'),
            $screen,
            'normal',
            'default'
        );

        if (self::is_order_quarantined($order)) {
            add_meta_box(
                'tot_order_detail_quarantine_meta_box_display',
                __('Order is Awaiting Verification', 'token-of-trust'),
                array($this, 'render_order_detail_quarantine_meta_box_content'),
                $screen,
                'normal',
                'high'
            );
        }

    }

    public static function is_order_quarantined($order=null) {
        global $post, $theorder;

        if (!isset($order)) {
            if (
                !isset($theorder)
                && (!isset($post) || 'shop_order' !== $post->post_type)
            ) {
                return false;
            }
    
            $order = $theorder instanceof \WC_Order ? $theorder : tot_wc_get_order($post);
        } else {
            $order = tot_wc_get_order($order);
        }

        $quarantined = $order instanceof \WC_Abstract_Order
            ? $order->get_meta('tot_quarantined', true)
            : false;
        return tot_option_has_a_value($quarantined);
    }

    public static function is_tot_verification_not_required($order) {
        global $post, $theorder;

        if (!isset($order)) {
            if (
                !isset($theorder)
                && (!isset($post) || 'shop_order' !== $post->post_type)
            ) {
                return false;
            }

            $order = $theorder instanceof \WC_Order ? $theorder : tot_wc_get_order($post);
        } else {
            $order = tot_wc_get_order($order);
        }

        $not_required = $order->get_meta('tot_not_required', true);
        return tot_option_has_a_value($not_required);
    }

    /**
     * @param \WC_Order|\WP_Post $post
     * @return void
     */
	public function render_order_detail_verification_details_meta_box_content($post)
    {
        $order = tot_wc_get_order($post);
        
        $out = $this->receipt_link($order);
        $out .= '<br>';
        $out .= $this->receipt_summary_embed($order);

        // $this->reload_page_listener();

        echo $out;

    }

	public function receipt_summary_embed( $order ) {
        $order = tot_wc_get_order($order);
        $tot_transaction_id = $order->get_meta('tot_transaction_id', true);
        $app_userid = tot_woo_get_appuserid($order);

        if (!$tot_transaction_id && !$app_userid) {
            return '';
        }

        return do_shortcode(
            '[tot-wp-embed'
            . ' tot-widget="reputationSummary"'
            . (
            $tot_transaction_id
                ? ' tot-transaction-id="' . $tot_transaction_id . '"'
                : ' app-userid="' . $app_userid . '"'
            )
            . ' show-admin-buttons="true"][/tot-wp-embed]'
        );

    }

    /*
    public function reload_page_listener() {

        // @codingStandardsIgnoreStart
        ?>
        <script>
            (function () {
                var somethingHappened = false;
                tot('bind', 'modalFormSubmitted', function () {
                    somethingHappened = true;
                });
                tot('bind', 'modalClose', function () {
                    setTimeout(function(){
                        if( ! somethingHappened ) {
                            return;
                        }

                        var promptResponse = confirm(
                            'Do you want to reload the page to see the updated status?'
                            + ' Any in-progress changes will be lost.'
                        );
                        if ( promptResponse ) {
                            window.location.reload();
                        }
                    }, 500);
                });
            })();
        </script>
        <?php
        // @codingStandardsIgnoreEnd
    }
*/
	public function receipt_link( $order ) {

        global $tot_plugin_text_domain;
        $order = tot_wc_get_order($order);

        return '<a href="' . $order->get_checkout_order_received_url() . '" target="_blank">'
            . __('View Receipt', $tot_plugin_text_domain)
            . '</a>';

    }

	public function render_order_detail_quarantine_meta_box_content($post) {
        $order = tot_wc_get_order($post);

        echo '<p>';
        echo __('This order is awaiting verification.', 'token-of-trust');
        echo '</p>';
        echo '<ul class="tot-meta-box-actions">';
        echo '<li><a href="#tot_order_detail_verification_details_meta_box" class="button">' . __('View Details', 'token-of-trust') . '</a></li>';
        // Todo: Verification reminder from admin
        // echo '<li><a href="#tot_order_detail_verification_reminder" class="button" data-order="'.$order_id.'">' . __('Email user reminder', 'token-of-trust') . '</a></li>';
        echo '<li class="tot-meta-box-actions-right">';
        echo '<a href="#tot_remove_quarantine" class="button" data-order="' . $order->get_id() . '">' . __('Remove Verification Hold', 'token-of-trust') . '</a>';
        echo '</li>';
        echo '</ul>';
    }

	public function enqueue_scripts() {
        wp_enqueue_style('token-of-trust-wc-admin-css', plugins_url('/wc-admin.css', __FILE__), array(), tot_plugin_get_version());
        wp_enqueue_script('token-of-trust-wc-admin-js', plugins_url('/wc-admin.js', __FILE__), array('jquery'), tot_plugin_get_version());
    }

	public function order_unquarantine() {

        $order_id = intval($_POST['order_id']);
        $order = tot_wc_get_order($order_id);

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array());
            wp_die();

            return;
        }

        if (!isset($order) || is_wp_error($order) || (false == $order)) {
            wp_send_json_error(array());
            wp_die();

            return;
        }

        $tot_user = Current_User::instance();

        $order->add_order_note(__('Verification Hold manually removed by ', 'token-of-trust') . $tot_user->wordpress_user->data->user_login . ' (ID: ' . $tot_user->wordpress_user->ID . ')');
        $order->update_meta_data('tot_last_updated', current_time('mysql'));
        $order->update_meta_data('tot_quarantined', false);
        $order->update_meta_data('tot_quarantine_manually_removed', true);
        $order->save();

        wp_send_json(array());
        wp_die();

    }

	public function email_reminder() {

        $order_id = intval($_POST['order_id']);
        $order = tot_wc_get_order($order_id);

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array());
            wp_die();

            return;
        }

        if (!isset($order) || is_wp_error($order) || (false == $order)) {
            wp_send_json_error(array());
            wp_die();

            return;
        }

        $to = $order->billing_email;
        $subject = 'Please verify your order';
        $body = '<a href="' . $order->get_checkout_order_received_url() . '">verify</a>';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($to, $subject, $body, $headers);

        wp_send_json(array());
        wp_die();
    }

}