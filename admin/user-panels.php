<?php

use TOT\Settings;
use TOT\User;

	add_filter( 'manage_users_columns', 'tot_modify_user_table', 10, 1);
	add_filter( 'manage_users_custom_column', 'tot_modify_user_table_row', 10, 3 );
	add_action( 'show_user_profile', 'tot_custom_user_profile_fields' );
	add_action( 'edit_user_profile', 'tot_custom_user_profile_fields' );

//////////

function tot_modify_user_table( $column ) {
    if(tot_get_option('tot_field_confirm_new_user_emails') ) {
        $column['tot_email'] = 'Email Confirmation';
    }
    $column['tot_status'] = 'ID Verification';
    if(tot_get_option('tot_field_approval') ) {
        $column['tot_approval'] = 'Approval';
    }
    return $column;
}

function tot_function($user_id){
    $f = "tot('modalOpen', 'reportAbuse',{
        appUserid:'" . tot_user_id($user_id) ."',
        appReporterUserid:'" . tot_user_id(get_current_user_id()) . "',
        appCustom1: 'Reporter WordPress user id " . get_current_user_id() . " and reportee WordPress user id " . $user_id . ".'
    });";
    return $f;
}

function tot_modify_user_table_row( $val, $column_name, $user_id ) {
    switch ($column_name) {
        case 'tot_approval':
            return tot_approval_select_menu($user_id);

        case 'tot_status' :

            // This is gonna be the HTML output...
            $html = '';
            if (isset($user_id)) {
                // There should be no reason why you wouldn't have a $user_id in this view, but just in case, this is in an if.
                $html .= '[tot-wp-embed tot-widget="verifiedIndicator" show-admin-buttons="true" tot-show-when-not-verified="true" wp-userid="' . $user_id . '"][/tot-wp-embed]';
                $html .= '<a href="#" class="report_abuse_link" onclick="'.tot_function($user_id).'">Report Abuse</a>';
            }
            return do_shortcode($html);

        case 'tot_email':
            $html = $val;
            if(tot_get_option('tot_field_confirm_new_user_emails') ) {
                if (get_user_meta($user_id, 'tot_email_verification')) {
                    $tot_email_verified = json_decode(get_user_meta($user_id, 'tot_email_verification')[0]);
                    if (strcmp($tot_email_verified->status, 'email_confirmed') == 0) {
                        $html .= "<p class=\"tot-match\" data-tot-username = \"tot-user-email-confirmation-status-" . get_userdata($user_id)->user_login . "\"><i class=\"dashicons dashicons-yes\"></i> Confirmed</p>";
                    } elseif (strcmp($tot_email_verified->status, 'email_sent') == 0) {
                        $html .= "<p class=\"tot-pending\" data-tot-username = \"tot-user-email-confirmation-status-" . get_userdata($user_id)->user_login . "\"><i class=\"dashicons dashicons-email\"></i> Pending</p>";
                    }
                }
            };

            return $html;

        default:
    }
    return $val;
}

function tot_approval_select_menu($user_id) {

    if(!current_user_can('promote_users')) {
        return 'Approval requires higher role';
    }

    $html = '';

    $tot_approval_status = tot_get_user_approval_status($user_id);

    $options = [
        ['', 'Pending'],
        ['approved', 'Approved'],
        ['rejected', 'Rejected'],
    ];

    $html .= '<div data-tot-approval-status-wrap class="tot-approval-status-wrap"><select data-tot-approval-status="' . $user_id . '" data-tot-user-id="' . tot_user_id($user_id) . '">';
    foreach($options as $option) {
        $selected = '';
        if(($tot_approval_status === $option[0]) || (!isset($tot_approval_status) && $option[0] === '')) {
            $selected = ' selected="selected"';
        }
        $html .= '<option value="' . $option[0] . '"' . $selected . '>' . $option[1] . '</option>';
    }
    $html .= '</select><div class="spinner"></div></div>';

    return $html;

}

function tot_custom_user_profile_fields( $user ) {

    $widget = $user->ID === get_current_user_id() ? 'accountConnector' : 'reputationSummary';

    echo '<h2><img height="15" width="15" src="' . plugins_url( '../shared/assets/icon-color.svg', __FILE__ ) . '"/> Token of Trust</h2>';
    if (!tot_debug_mode()) {
        echo '<p>Debug mode is <strong>off</strong>. More ToT User details can be seen if you turn <a href="admin.php?page=totsettings_license">Debug Mode</a> on.';
    }


	echo '<table class="form-table">';

        if(tot_get_option('tot_field_approval') ) {
            echo "<tr><th><label>Approval Status</label></th><td>";
            echo tot_approval_select_menu($user->ID);
            echo "</td></tr>";
        }
        if(tot_get_option('tot_field_confirm_new_user_emails') ) {
            if(get_user_meta($user->ID, 'tot_email_verification')) {
                $tot_email_verified = json_decode(get_user_meta($user->ID, 'tot_email_verification')[0]);
                if (strcmp($tot_email_verified->status, 'email_confirmed') == 0) {
                    echo "<tr><th><label>Email Confirmation Status</label></th> <td>Email Confirmed (Confirmed on " . date("Y-m-d", $tot_email_verified->last_updated) . ")</td></tr>";
                } elseif (strcmp($tot_email_verified->status, 'email_sent') == 0) {
                    echo "<tr><th><label>Email Confirmation Status</label></th> <td>Email Confirmation Pending (Sent on " . date("Y-m-d", $tot_email_verified->last_updated) . ")</td></tr>";
                }
            }
        };
        echo '<tr>';
			echo '<th><label>Verification Status</label></th>';
			echo '<td><div style="max-width:400px">' . do_shortcode('[tot-wp-embed tot-widget="' . $widget . '" wp-userid="' . $user->ID . '"][/tot-wp-embed]') . '</div></td>';
		echo '</tr>';

        if (tot_debug_mode()) {

            // Display ToT User Id (if Debug Mode is active)

            echo '<tr>';
                echo '<th>
                        <label>ToT User ID</label><br />
                        <sub style="font-size:80%;">Shown because <a href="admin.php?page=totsettings_license">Debug Mode</a> is on.</sub>
                      </th>';
                echo '<td id="tot-user-id"><code>' . tot_user_id($user->ID) . '</code></td>';
            echo '</tr>';

            // Display ToT Gates (if Debug mode is active)

            $tot_user = new User($user->ID);
            $totUserObject = tot_user_id($user->ID);
            $reasons = $tot_user->get_reputation_reasons($totUserObject);
            $gates = $tot_user->get_reputation_gates($totUserObject);
            if ( isset($gates) && isset($gates->reasons) ) {
                $gatesOutput = $gates->reasons;
            } else if ( isset($reasons) ) {
              $gatesOutput = $reasons;
            } else if ( isset($gates) ) {
                $gatesOutput = $gates;
            } else {
                $gatesOutput = array( "error" => "no gates");
            }

            echo "<tr>";
                echo '<th>
                        <label>ToT Gates</label><br/>
                        <sub style="font-size:80%;">Shown because <a href="admin.php?page=totsettings_license">Debug Mode</a> is on.</sub>
                      </th>';
                echo "<td>
                        <pre>" . print_r($gatesOutput, true) . "</pre>
                      </td>";
            echo '</tr>';
        }

        echo '<tr>';
			echo '<th>
			        <label>Report abuse</label>
                  </th>';
			echo '<td><div style="max-width:200px">' . do_shortcode('[tot-wp-embed tot-widget="reportAbuse" wp-reported-userid="' . $user->ID . '"][/tot-wp-embed]') . '</div></td>';
		echo '</tr>';
	echo '</table>';

	echo "<hr />";

}
