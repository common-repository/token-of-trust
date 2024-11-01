<?php
/**
 * Plugin Name: Token of Trust
 * Description: With the Token of Trust® ID Verification plugin for WordPress and WooCommerce, you can quickly connect to the most powerful identity verification platform in the world. Easily add a reusable age verification and identity verification process to any WordPress website or WooCommerce checkout to comply with the law and keep your customer's data safe.
 * Version: 3.3.6
 * Author: Token of Trust
 * Author URI: https://tokenoftrust.com
 * Text Domain: token-of-trust
 * Domain Path: /languages/
 * Developer: Token of Trust
 * Developer URI: https://tokenoftrust.com
 *
 * Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
 * WC requires at least: 3.0
 * WC tested up to: 5.7.1
 *
*/

if (!function_exists('get_plugins')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

$tot_plugin_main_file_path = __FILE__;
$tot_plugin_folder_full = dirname(__FILE__);
$tot_plugin_folder_base = plugin_basename($tot_plugin_folder_full);
$tot_plugin_main_file = basename((__FILE__));
$tot_plugin_slug = plugin_basename(__FILE__);
$tot_plugin_text_domain = 'token-of-trust';
$domain_path = '/languages/';

function tot_plugin_get_version()
{
    $plugin_folder = get_plugins('/' . plugin_basename(dirname(__FILE__)));
    $plugin_file = basename((__FILE__));
    return $plugin_folder[$plugin_file]['Version'];
}

require 'env.php';
require 'utils.php';
require 'settings/class-settings.php';
TOT\Settings::init();
require 'shared/shared.php';
require 'upgrades/upgrades.php';

function tot_load_plugin_textdomain() {
    // tot_log_debug('Loading all language files.');
    $plugin_rel_path = basename( dirname( __FILE__ ) ) . '/languages';
    load_plugin_textdomain( 'token-of-trust', false, $plugin_rel_path );
}
add_action('plugins_loaded', 'tot_load_plugin_textdomain');


require 'admin/class-webhooks.php';
( new TOT\Admin\Webhooks() )->register_wordpress_hooks();
require 'admin/class-quickstart.php';
( new TOT\Admin\Quickstart() )->register_wordpress_hooks();
require 'api/client-api-verify-person.php';
( new TOT\Admin\Client_API_VerifyPerson())->register_routes();

require 'settings/class-page.php';
require 'legacy.php';
require 'users/users.php';
require 'admin/admin.php';
require 'site/site.php';
require 'integrations/buddypress/buddypress.php';
require 'integrations/ultimate-member/ultimate-member.php';

// Handling plugin activation
new \TOT\Admin\PluginActivation($tot_plugin_main_file_path);

// Sentry
require 'integrations/sentry/class-sentry.php';
add_action('init', function () {
    \TOT\Integrations\Sentry\Sentry::inst()->setup();
});

// WooCommerce
require 'integrations/woocommerce/wc-admin.php';
require 'integrations/woocommerce/class-checkout-hook-handler.php';
require 'integrations/woocommerce/class-checkout.php';
require 'integrations/woocommerce/class-settings.php';
require 'integrations/woocommerce/class-admin.php';
require 'integrations/woocommerce/class-donations.php';
require 'integrations/woocommerce/class-product-sync.php';
( new TOT\Integrations\WooCommerce\Admin() );
( new TOT\Integrations\WooCommerce\Checkout_Bridge() );
( new TOT\Integrations\WooCommerce\Settings() );
( new TOT\Integrations\WooCommerce\Donations() );
( new TOT\Integrations\WooCommerce\Product_Sync() );

// Developer examples and tests
require 'examples/examples.php';
