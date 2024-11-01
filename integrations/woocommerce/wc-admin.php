<?php
/** @var $tot_plugin_main_file_path */
use TOT\Integrations\WooCommerce\Admin;

function show_tot_verified_status($columns)
{
    global $tot_plugin_text_domain;
    $new_columns = (is_array($columns)) ? $columns : array();
    $new_columns['tot_v_status'] = __('Verification Status', $tot_plugin_text_domain);
    return $new_columns;
}

function tot_woo_get_transaction_id($order) {
    $tot_transaction_id = $order->get_meta('tot_transaction_id', true);
    if ( ! $tot_transaction_id ) {
        return null;
    }
    return $tot_transaction_id;
}

/**
 * @param WC_Order
 *
 * @return string|null
 */
function tot_woo_get_appuserid($order) {
    if (! $order instanceof WC_Abstract_Order) {
        return null;
    }

    return tot_create_appuserid_from_email(
        $order->get_user_id(),
        $order
    );
}

function tot_shop_orders_verified_indicator($column, $post_id)
{
    if ($column === 'tot_v_status') {
        $order = new WC_Order($post_id);
        $app_userid = tot_woo_get_appuserid($order);
        $transaction_id = tot_woo_get_transaction_id($order);

        if (!empty($app_userid) || !empty($order)) {
            $order_id = $order->get_id();
            if (isset($order_id)) {
                $not_required = Admin::is_tot_verification_not_required($order);

                if (!empty($not_required)) {
                    echo "<p>Not Required</p>";
                } else {
                    echo do_shortcode('[tot-wp-embed tot-widget="verifiedIndicator" tot-is-admin-view="true" tot-show-when-not-verified="true"  tot-show-pending="true"
                                '. (
                                    $transaction_id
                                        ? 'tot-transaction-id="' . $transaction_id . '"'
                                        : 'app-userid="' . $app_userid . '"'
                                ) .'
                            ][/tot-wp-embed]
                        ');
                }
            }
        }
    }
}

/**
 * @param WC_Abstract_Order|WP_Post|false|null $order
 * @return WC_Abstract_Order|WC_Order|false|null
 */
function tot_wc_get_order($order = false) {
    if (is_numeric($order)) {
        $order = wc_get_order($order);
    } elseif ($order instanceof WP_Post) {
        $order = wc_get_order($order->ID);
    } elseif (
        $order instanceof WC_Order
        || $order === false
        || $order === null
    ) {
        //
    }

    return $order;
}

// mark compatible with HPOS.
add_action('before_woocommerce_init', function() use ($tot_plugin_main_file_path) {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $tot_plugin_main_file_path, true );
    }
});

/*
//function tot_reload_page_listener() {
//
//    // @codingStandardsIgnoreStart
//    ?>
<!--    <script>-->
<!--        (function () {-->
<!--            var somethingHappened = false;-->
<!--            tot('bind', 'modalFormSubmitted', function () {-->
<!--                somethingHappened = true;-->
<!--            });-->
<!--            tot('bind', 'modalClose', function () {-->
<!--                setTimeout(function(){-->
<!--                    if( ! somethingHappened ) {-->
<!--                        return;-->
<!--                    }-->
<!---->
<!--                    var promptResponse = confirm(-->
<!--                        'Do you want to reload the page to see the updated status?'-->
<!--                        + ' Any in-progress changes will be lost.'-->
<!--                    );-->
<!--                    if ( promptResponse ) {-->
<!--                        window.location.reload();-->
<!--                    }-->
<!--                }, 500);-->
<!--            });-->
<!--        })();-->
<!--    </script>-->
<!--    --><?php
//    // @codingStandardsIgnoreEnd
//}
//
*/
