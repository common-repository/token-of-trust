<?php
namespace TOT\Admin;

class PluginActivation {
    public function __construct($tot_plugin_main_file_path) {
        register_activation_hook( $tot_plugin_main_file_path, array( $this, 'send_activation_action' ) );
        register_deactivation_hook( $tot_plugin_main_file_path, array( $this, 'send_deactivation_action' ) );
    }
    
    public function send_activation_action()
    {
        $url = $this->get_analytics_url('activated_integration');
        $this->send_analytics($url);
    }

    public function send_deactivation_action()
    {
        $url = $this->get_analytics_url('deactivated_integration');
        $this->send_analytics($url);
    }

    public function send_analytics($url)
    {
        
        $response = wp_remote_get( $url, array(
            'timeout' => 1,
        ));
        
        $is_deactivated = strpos($url, 'deactivated_integration') !== false;

        if ( is_wp_error( $response ) ) {
            \TOT\tot_debugger::inst()->log('Error sending '
                . ($is_deactivated ? 'deactivating' : 'activating')
                . ' plugin analytics to: ' . $url, $response->get_error_message(), 'error');
        } else {
            \TOT\tot_debugger::inst()->log('Done sending '
                . ($is_deactivated ? 'deactivating' : 'activating')
                . ' plugin analytics: ' . $url, wp_remote_retrieve_body($response));
        }
    }

    private function get_analytics_url($action)
    {
        return tot_origin()
            . '/api/reportAnalytics/wordpress/'
            . tot_plugin_get_version()
            . '/cust/'
            . $action
            . '/?vendorAppDomain='
            . (tot_get_setting_prod_domain() ?: parse_url(home_url('/'))['host']);
    }
}

