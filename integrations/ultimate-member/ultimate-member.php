<?php

$tot_ultimate_member_installed = false;
$tot_ultimate_member_version = '-1';

if(is_plugin_active("ultimate-member/index.php")) {
    $tot_ultimate_member_version = '1';
    $tot_ultimate_member_installed = true;
}else if(is_plugin_active("ultimate-member/ultimate-member.php")) {
    $tot_ultimate_member_version = '2';
    $tot_ultimate_member_installed = true;
}

require("um_profile_page.php");
require("um_account_page.php");
require("um_settings_menu.php");
