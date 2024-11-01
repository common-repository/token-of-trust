<?php

namespace TOT;

class User {

	public $wordpress_user;
	public $wordpress_user_id;
	public $tot_user_id;
	private $reputation;

	public function __construct( $wp_id , $app_userid = null) {

		if (!empty($wp_id)) {
            $this->wordpress_user = \get_user_by('id', $wp_id);
            if (empty($this->wordpress_user)) {
                $this->wordpress_user_id = $wp_id;
            }
        }

		if (!empty($app_userid)) {
            $this->tot_user_id = $app_userid;
        } else {
		    if (!empty($this->wordpress_user_id)) {
                $this->tot_user_id = \tot_user_id($wp_id);
            }
        }
	}

    public function get_reputation_reasons($tot_userid = null)
    {
        $results = $this->get_reputation($tot_userid);

        if ( is_wp_error( $results ) ) {
            tot_display_error($results);
            return $results;
        }
        if (empty($results)) {
            return NULL;
        } else {
            $reasons = $results->reasons;
            // tot_log_as_html_pre('reasons', $results);
            return new Reasons($reasons);
        }
    }

    public function get_reputation_gates($tot_userid = null)
    {
        $results = $this->get_reputation($tot_userid);

        if ( is_wp_error( $results ) ) {
            tot_display_error($results);
            return $results;
        }
        if (empty($results)) {
            return NULL;
        } else {
            $gates = $results->gates;
            return new Reasons($gates);
        }
    }

    public function get_reputation($tot_userid = null) {

		if( isset($this->reputation) ) {
			return $this->reputation;
		}

		$tot_userid = !empty($tot_userid) ? $tot_userid : $this->tot_user_id;

		if( empty($tot_userid) ) {
            return null;
		}

        $results = tot_get_user_reputation($tot_userid);

        if ( !is_wp_error( $results ) ) {
            $this->reputation = $results;
        }

		return $results;
	}

	public function is_active_tot_user() {

		$reputation = $this->get_reputation();

		return isset($reputation->connection->status) && ($reputation->connection->status === "active");

	}

	public function get_connection() {

		$endpoint_path = 'api/connection/' . $this->tot_user_id;
		$data = array(
			'appUserid' => $this->tot_user_id
		);
		$connection_request = new API_Request($endpoint_path, $data, 'GET');
		return $connection_request->send();

	}

	public function set_connection() {

		if(!$this->wordpress_user) {
			return null;
		}

		$endpoint_path = 'api/connection/' . $this->tot_user_id;
		$data = array(
			'appUserid' => $this->tot_user_id,
			'appData' => $this->user_connection_data()
		);
		$connection_request = new API_Request($endpoint_path, $data);

		if(is_wp_error($connection_request)) {
			do_action('tot_set_connection_failed', $connection_request);
			return $connection_request;
		}

		$response = $connection_request->send();

		if(is_wp_error($response)) {
			do_action('tot_set_connection_failed', $response, $connection_request->request_details, $connection_request->endpoint_url);
		}else {
			do_action('tot_set_connection_success', $response, $connection_request->request_details, $connection_request->endpoint_url);
		}

		return $response;

	}

	public function user_connection_data() {
		if(!$this->wordpress_user) {
			return null;
		}

		$givenName  = $this->wordpress_user->first_name;
		$familyName = $this->wordpress_user->last_name;
		$nickname   = $this->wordpress_user->display_name;
		$email      = $this->wordpress_user->user_email;

		$appData = array(
			'givenName' => array(
				'current' => $givenName
			)
		);

		if (\tot_get_option('tot_field_auto_identify')) {
			if(isset($email)) {
				$appData['email'] = $email;
			}
			$appData['familyName'] = array(
				'current' => $familyName
			);
		}

		return apply_filters('tot_set_connection_app_data', $appData, $this->wordpress_user_id);
	}

}
