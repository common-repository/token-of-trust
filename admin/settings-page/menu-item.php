<?php

add_action('admin_menu', 'tot_options_page');
add_action('admin_enqueue_scripts', 'tot_admin_settings_style');

function tot_admin_settings_style()
{
    wp_enqueue_style('admin-token-of-trust-settings', plugins_url('/tot-settings.css', __FILE__), array(), tot_plugin_get_version());
    if (isset($_GET['page']) && $_GET['page'] == 'totsettings') {
        wp_enqueue_style('admin-token-of-trust-quickstart-css', plugins_url('/common/tot-quickstart.css', __FILE__), array(), tot_plugin_get_version());
        wp_enqueue_style('admin-token-of-trust-quickstart-modal-css', plugins_url('/common/tot-quickstart-modal.css', __FILE__), array(), tot_plugin_get_version());
    }}

function tot_options_page()
{
    add_menu_page(
        'Token of Trust',
        'Token of Trust',
        'manage_options',
        'totsettings',
        'get_started_app_html',
        'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+Cjxzdmcgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDE3NSAxNzUiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkO2NsaXAtcnVsZTpldmVub2RkO3N0cm9rZS1saW5lam9pbjpyb3VuZDtzdHJva2UtbWl0ZXJsaW1pdDoxLjQxNDIxOyI+CiAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLC0xMTEuNSwtMTgyKSI+CiAgICAgICAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMC45OTYxODMsMCwwLDAuOTk2MTgzLDAuMTI2NzM1LDAuNjk0NzUxKSI+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik0xNjUuNzM4LDIzNC43NTlDMTY1LjczOCwyNDYuODI1IDE3NS41MTcsMjU2LjYwNyAxODcuNTg0LDI1Ni42MDdDMTk5LjY0NywyNTYuNjA3IDIwOS40MjgsMjQ2LjgyNSAyMDkuNDI4LDIzNC43NTlDMjA5LjQyOCwyMjIuNjk0IDE5OS42NDcsMjEyLjkxNyAxODcuNTg0LDIxMi45MTdDMTc1LjUxOCwyMTIuOTE3IDE2NS43MzgsMjIyLjY5MyAxNjUuNzM4LDIzNC43NTlaIiBzdHlsZT0iZmlsbC1ydWxlOm5vbnplcm87Ii8+CiAgICAgICAgPC9nPgogICAgICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDAuOTk2MTgzLDAsMCwwLjk5NjE4MywwLjEyNjczNSwwLjY5NDc1MSkiPgogICAgICAgICAgICA8cGF0aCBkPSJNMjg0LjYzLDIyMS4yMTdDMjg0LjYzLDIyMS4yMTcgMjYwLjUyLDIyMi45OCAyMjAuMDEzLDI1MC41MTVDMTk3Ljg5NywyNjUuMzczIDE4Ni43NDQsMjc4LjYzNSAxODYuNzQ0LDI3OC42MzVDMTg2Ljc0NCwyNzguNjM1IDE3MC45MzIsMjYwLjM4NiAxNTIuNDQ0LDI0Ny45NTdMMTM2LjQ0MywyNjkuMTNDMTM2LjQ0MywyNjkuMTMgMTQ5LjgzOCwyNzcuODQzIDE2NC4xMTUsMjkwLjY0NEMxNzcuNjQ0LDMwMi43NzUgMTg3LjE1NiwzMTUuNjM0IDE4Ny4xNTYsMzE1LjYzNEMxODcuMTU2LDMxNS42MzQgMTk5LjE5MSwyOTIuNDQ5IDIyMy41NywyNjguNDI2QzIzMi4yNTIsMjU5Ljg3MyAyNDEuMzA0LDI1Mi41NyAyNDkuNzc0LDI0Ni41MThDMjQ5LjAxNSwyNzYuMzc0IDI0Mi41MzEsMzIwLjY0OSAxODcuMTQsMzQ0LjA4MUMxMjQuNDA2LDMxNy41NDIgMTI0LjQwNSwyNjQuMjY4IDEyNC40MDUsMjM1LjQ4M0wxMjQuNDA1LDIyMi42MzFMMTI0LjM5OCwyMjIuNjMxQzEyNC41NDEsMjE1LjYyOCAxMjYuMTIzLDIxMC44OTQgMTI5LjQ4LDIwNy4zODdDMTMzLjQ0MiwyMDMuMjUyIDE0MC41MTEsMjAwLjIxMiAxNTEuMDg5LDE5OC4wOTFDMTc0LjM5MywxOTMuNDIxIDIwMi42MTMsMTkzLjQwNyAyMjIuOTg1LDE5OC4wNTZDMjQ0LjA4NSwyMDIuODcyIDI0OC45MjksMjExLjQ1OCAyNDkuNzIxLDIyMC40NTRDMjU0LjE1NSwyMTkuMTE2IDI1OC4yMzksMjE4LjA0MyAyNjEuOTYsMjE3LjE4NEMyNjAuODMyLDIwOS43NDcgMjU3LjY5NCwyMDMuNDg5IDI1Mi41ODQsMTk4LjUyNEMyNDYuNTksMTkyLjcwMiAyMzcuODI0LDE4OC41MzcgMjI1Ljc4NSwxODUuNzg5QzIwMy44MTcsMTgwLjc3NCAxNzMuNTI2LDE4MC43NTkgMTQ4LjYxNiwxODUuNzUyQzEzNS4yNDYsMTg4LjQzMyAxMjYuMjc5LDE5Mi41NDEgMTIwLjM5MywxOTguNjgzQzExNC43MjIsMjA0LjYwMSAxMTEuOTkxLDIxMi4yMzggMTExLjgxNywyMjIuNjNMMTExLjgsMjIyLjYzTDExMS44LDIzNS40ODJDMTExLjgsMjU0LjI3MyAxMTIuNTQ5LDI3Ni4xMjggMTIxLjUwMSwyOTcuNzcxQzEyNi42MywzMTAuMTc2IDEzNC4wMiwzMjEuMTk3IDE0My40NjUsMzMwLjUyN0MxNTQuMzI3LDM0MS4yNiAxNjguMjMxLDM1MC4wODEgMTg0Ljc4NywzNTYuNzRMMTg3LjEzOSwzNTcuNjg4TDE4OS40ODksMzU2Ljc0QzIwNi4wNDYsMzUwLjA4MSAyMTkuOTQ5LDM0MS4yNjEgMjMwLjgxMiwzMzAuNTI3QzI0MC4yNTUsMzIxLjE5NyAyNDcuNjQ3LDMxMC4xNzYgMjUyLjc3NiwyOTcuNzcxQzI2MS4zMjUsMjc3LjA5NyAyNjIuMzkzLDI1Ni4yMjggMjYyLjQ3MiwyMzguMDI0QzI3Ni44NDYsMjI5LjA0MiAyODcuMzA2LDIyNC41NDMgMjg3LjMwNiwyMjQuNTQzTDI4NC42MywyMjEuMjE3WiIgc3R5bGU9ImZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+Cg=='
    );

    add_submenu_page('totsettings', 'Get Started', 'Get Started', 'manage_options', 'totsettings', 'get_started_app_html');
//    add_submenu_page('totsettings', 'Dashboard', 'Dashboard', 'manage_options', 'totsettings', 'tot_options_page_html');
    // Add new submenu just below Dashboard
//    add_submenu_page('totsettings', 'Get Started', 'Get Started', 'manage_options', 'totsettings', 'tot_options_page_html');

    if (
		tot_option_has_a_value(tot_get_setting_license_key())
		&& tot_live_or_in_trial()
	) {
        add_submenu_page('totsettings', 'General Settings', 'General Settings', 'manage_options', 'totsettings_general', 'tot_options_page_html');
        add_submenu_page('totsettings', 'License & API', 'License & API', 'manage_options', 'totsettings_license', 'tot_options_page_html');
        add_submenu_page('totsettings', 'Donations', 'Donations', 'manage_options', 'totsettings_donations', 'tot_options_page_html');
    } elseif (
		tot_option_has_a_value(tot_get_setting_license_key())
		&& !tot_live_or_in_trial()
	) {
		// for force refreshing keys only
		add_submenu_page('totsettings', 'License & API', 'License & API', 'manage_options', 'totsettings_license', 'tot_options_page_html');
	} else {
        // register the page but hide it from the menu
        add_submenu_page('', 'License & API', 'License & API', 'manage_options', 'totsettings_license', 'tot_options_page_html');
    }
}

function get_started_app_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    printf(
        '<div id="root">%s</div>',
        "Loading..."
    );
}

function tot_options_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $forcedLicenseRefresh = isset($_GET['tot-force-license-refresh']);
    if ($forcedLicenseRefresh) {
        tot_refresh_keys();
        tot_add_flash_notice('License Keys Updated', 'success', false);
        $goback = wp_get_referer();
        wp_redirect($goback);
        exit;
    }

    if (isset($_GET['tot-error'])) {

        require('view-debug-error.php');

    }elseif (isset($_GET['tot-webhooks'])) {

             require('view-webhooks.php');

    }elseif (isset($_GET['tot-logs'])) {

             require('view-logs.php');

    }elseif (strpos($_GET['page'], 'totsettings_general') !== false) {

        require('view-general-settings.php');

    }elseif (strpos($_GET['page'], 'totsettings_license') !== false) {

        if (isset($_GET['tot-subpage']) && ($_GET['tot-subpage'] === 'live')) {

            require('view-live-mode.php');

        }else {
	        $page = 'admin.php?page=totsettings_license';
            require('view-settings.php');
            require ('view-change-license-alert.php');
        }

    } elseif (strpos($_GET['page'], 'totsettings_donations') !== false) {
        require('view-donations.php');
    } else {
        require('view-quickstart.php');
		require ('view-change-license-alert.php');
    }

}
