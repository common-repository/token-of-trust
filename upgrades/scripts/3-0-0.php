<?php
/**
 * Setup-wizard back compatibility
 * 
 * the setup-wizard will have missing data
 * for old users that has an active website
 */
use TOT\Settings;

$keys = tot_get_keys();
$use_case = is_array($keys) && isset($keys['verificationUseCase']) ? $keys['verificationUseCase'] : null;
//Settings::set_setting('tot_field_where_user_get_verified_card', null);
/**
 * @see admin/setup-wizard/frontend/src/modules/WhereUsersGetVerified/domain/entities/WhereUsersGetVerifiedCardsEnum.ts
 */
if ($use_case === 'age' || $use_case === 'identity') {
    Settings::set_setting('tot_field_where_user_get_verified_card', 'checkout');
} elseif ($use_case === 'account') {
    Settings::set_setting('tot_field_where_user_get_verified_card', 'create-account');
} elseif (Settings::get_setting('tot_field_checkout_require')) {
    Settings::set_setting('tot_field_where_user_get_verified_card', 'checkout');
} elseif (Settings::get_setting('tot_field_verification_gates_enabled')) {
    Settings::set_setting('tot_field_where_user_get_verified_card', 'create-account');
}