<?php

function tot_respond_to_error_with_link($error_key, $error_description, $error_details, $tot_error = null)
{
    $hash = md5($error_key . $error_description . json_encode($error_details));

    $error = array(
        'timestamp' => date("F j, Y, g:i a"),
        'key' => $error_key,
        'description' => $error_description,
        'details' => $error_details
    );

    if (isset($tot_error) && isset($tot_error->content) && isset($tot_error->content->causedBy)) {
        $tot_loop_error = $tot_error->content;
        while (isset($tot_loop_error->causedBy)) {
            $tot_loop_error = $tot_loop_error->causedBy;
        }
        $error['tot-error'] = $tot_loop_error;
    }

    set_transient('tot_error_' . $hash, json_encode($error), MINUTE_IN_SECONDS * 10);

    return new WP_Error($error_key, $error_description . ' <a href="' . admin_url('admin.php') . '?page=totsettings_license&tot-error=' . $hash . '">See details</a>, details will expire in 10 minutes.');
}

function tot_option_has_a_value( $value ) {

    if(is_wp_error($value)) {
        tot_respond_to_error_with_link('tot_field_error', 'There was an error retrieving the field', array(
            'value' => $value
        ));
        return false;
    }

    if (!isset($value) || ($value === false) || ($value === '')) {
        return false;
    }

    return true;
}

/**
 * @param $utm_term
 * @param array{send_plugins: bool, page: string, extra_params: string} $options
 * @return string
 */
function tot_frontend_link_parameters(
	$utm_term,
	array $options = [
		'send_plugins' => false,
		'page' => null,
		'extra_params' => null
	]
) {
    global $wp;
	$send_plugins = $options['send_plugins'] ?? false;
	$page = $options['page'] ?? null;
	$extra_params = $options['extra_params'] ?? null;

    if (is_admin()) {
        $current_url = strpos(basename($_SERVER['REQUEST_URI']), '.php') !== false
            ? admin_url(basename($_SERVER['REQUEST_URI'])) : admin_url();;
    } else {
        $current_url = home_url(add_query_arg($_GET, $wp->request));
    }
    $version = tot_plugin_get_version();

	$plugins = '';
	if ($send_plugins){
		$activated_plugins = get_option('active_plugins');

		$activated_plugins = array_map(function ($plugin) {
			$name = explode("/", $plugin)[0];
			// if it doesn't have folder
			if (strpos('.php', $name)) {
				$name = explode(".php", $plugin)[0];
			}

			return $name;
		},  $activated_plugins);

		$plugins = "plugins=" . implode(",", $activated_plugins);
	}

    if (isset($page)) {
        // If $page is set, update the 'page' query parameter
        $parsed_url = parse_url($current_url);
        parse_str($parsed_url['query'], $query_params);
        $query_params['page'] = $page;
        $new_query_str = http_build_query($query_params);
        $new_url = $parsed_url['scheme'] . "://" . $parsed_url['host'] . $parsed_url['path'] . "?" . $new_query_str;
        $urlencode = urlencode($new_url);
    } else {
        $urlencode = urlencode($current_url);
    }
    return "?conversion_source=wordpress&utm_source=wordpress-v-{$version}&{$plugins}&utm_medium=app&utm_campaign=wordpress"
		."&utm_content=wordpress_plugin&utm_term=" . $utm_term . "&source_url=" . $urlencode
		. (isset($extra_params) ? '&' . $extra_params : '');
}
