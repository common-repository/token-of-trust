<?php
/**
 *
 * Settings
 *
 */

namespace TOT;

/**
 * WARNING : use of tot_log_as_html_comment doesn't work well here and can cause HTML corruption!!
 */

class Settings {

	public static $key = 'tot_options';
    public static $prefix = 'tot_field_';
    
    public static $allowed_webhook_actions = [
        'apikey.updated'
    ];

    public static function init(){
        // register hooks related to settings
        self::register_hooks();
    }

    public static function register_hooks(){
        // update settings using webhook
        add_action('tot_webhook_success', [self::class, 'tot_webhook_settings'], 10, 2);
    }
    
    public static function tot_webhook_settings($webhook_name, $webhook_body) {
        if (!is_object($webhook_body)) {
            return;
        }

        // convert to an array
        $body = json_decode(json_encode($webhook_body), true);
        
        // take actions
        self::do_actions($body);
    }

    private static function do_actions($webhook_body)
    {
        $name = $webhook_body['name'] ?? false;
        if ( !$name || !in_array($name, self::$allowed_webhook_actions) ) {
            return;
        }

        // Do
        if ('apikey.updated' == $name) {
            $tot_debugger = tot_debugger::inst();
            $tot_debugger->register_new_operation('Webhook Setting Action is initiated');
            $tot_debugger->add_part_to_operation('', 'action apikey.updated is triggered');
            tot_refresh_keys();
        }
    }
    
	private function __construct() {}

	public static function get_setting($key) {
        $cookie = self::get_param_or_cookie($key);
        if (isset($cookie)) {
            // tot_log_as_html_comment("Found Param or Cookie '$key'", $cookie);
            return $cookie;
        }

        $do_get_setting = self::do_get_setting($key);
        // tot_log_as_html_comment("Found Option '$key'", $do_get_setting);
        return $do_get_setting;
    }

    /**
     * Strips 'tot_field_' off if present and looks for a query param and cookie of remaining name.
     *
     * @param $key
     * @return object
     */
    public static function get_param_or_cookie($key) {
        $key = self::stripTotFieldKey($key);
//        tot_log_as_html_comment('TOT - searching via get_query_var for ', $key);
        $query_var = get_query_var($key, NULL);
        if (isset($query_var)) {
//             tot_log_as_html_comment('TOT override - found via get_query_var', array(
//                $key => $query_var
//            ));
            return $query_var;
        }
        $cookieValue = ($key && isset($_COOKIE[$key])) ? $_COOKIE[$key] : NULL;
        // tot_log_as_html_comment('TOT - searching via get_query_var for ', $key);
        if (isset($cookieValue)) {
//             tot_log_as_html_comment('TOT override - found cookie value', array(
//                $key => $cookieValue
//            ));
            return $cookieValue;
        }
        return NULL;
    }



    public static function set_setting($key, $value) {
        $key = Settings::expandTotFieldKey($key);
        $options = get_option(self::$key);

		if(!isset($options) || !is_array($options)) {
			$options = array();
		}

		$options[$key] = $value;
		update_option('tot_options', $options, true);
        wp_cache_delete("tot_options", 'options');

//         tot_log_as_html_comment('TOT - set_setting', array(
//            $key => $value
//        ));
    }

    /**
     * @param $key
     * @return false|mixed|string
     */
    public static function stripTotFieldKey($key) {
        $prefixPos = strpos($key, self::$prefix);
        if ($prefixPos === 0) {
            // Strip prefix so cookie names are consistent with variable names.
            $key = substr($key, strlen(self::$prefix));
        }
        return $key;
    }

    /**
     * @param $key
     * @return false|mixed|string
     */
    public static function expandTotFieldKey($key) {
        $prefixPos = strpos($key, self::$prefix);
        if ($prefixPos !== 0) {
            $key = self::$prefix . $key;
        }
        return $key;
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function do_get_setting($key)
    {
        $key = Settings::expandTotFieldKey($key);

        // Add prefix if it's not present (to maintain back compatibility).
        $options = get_option(self::$key);
        if (isset($key) && $options && isset($options) && array_key_exists($key, $options)) {
            return $options[$key];
        }
    }
}