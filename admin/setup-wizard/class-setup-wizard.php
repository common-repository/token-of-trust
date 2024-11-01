<?php
namespace TOT\Admin\SetupWizard;
use TOT\Settings;

include 'class-controller.php';

class SetupWizard
{
    static public function init()
    {
        self::saveLicense();
        self::registerController();
        self::enqueueScripts();
    }

    static private function saveLicense()
    {
        if (!isset($_GET['license']) || !isset($_GET['appDomain'])) return;

        $license = sanitize_text_field($_GET['license']);
        $appDomain = sanitize_text_field($_GET['appDomain']);
        $license 
            && $appDomain
            && (
                Settings::set_setting('tot_field_license_key', $license)
                || Settings::set_setting('tot_field_prod_domain', $appDomain)
            );
    }
    
    
    static private function registerController()
    {
        add_action( 'rest_api_init', function () {
            $apiController = new Controller();
            $apiController->register_routes();
        });
    }
    
    static private function enqueueScripts()
    {
        add_action('admin_enqueue_scripts', function ($admin_page) {
            if ('toplevel_page_totsettings' !== $admin_page) {
                return;
            }
            $asset_file = plugin_dir_path(__FILE__) . '/frontend/build/index.asset.php';

            if ( ! file_exists($asset_file) ) {
                return;
            }

            $asset = include $asset_file;
            wp_enqueue_script(
                'tot_js_setup_wizard',
                plugins_url('frontend/build/index.js', __FILE__),
                $asset['dependencies'],
                $asset['version'],
                array(
                    'in_footer' => true
                )
            );

            $link_base = tot_production_origin() . '/hq/register/';
            wp_localize_script('tot_js_setup_wizard', 'tot_setup_wizard', [
                "what_to_verify_links" => [
                    'age' => $link_base . tot_frontend_link_parameters('selectVerifyAge', [
                        'send_plugins' => true,
                        'extra_params' => 'verificationUseCase=age'
                    ]),
                    'identity' => $link_base . tot_frontend_link_parameters('selectVerifyIdentity', [
                        'send_plugins' => true,
                        'extra_params' => 'verificationUseCase=identity'
                    ])
                ]
            ]);
            wp_enqueue_style(
                'tot_css_setup_wizard',
                plugins_url('frontend/build/style-scripts.css', __FILE__),
                [],
                $asset['version']
            );
            wp_enqueue_style(
                'tot_css_setup_wizard2',
                plugins_url('frontend/build/scripts.css', __FILE__),
                [],
                $asset['version']
            );
        });
    }
}

SetupWizard::init();