<?php
namespace TOT;

global $wpdb;

$wpdb->query("
UPDATE `wp_options`
SET
    autoload = 'no'
WHERE option_name != 'tot_options' AND
    (option_name LIKE 'tot_%' OR option_name LIKE 'tot-%');
");