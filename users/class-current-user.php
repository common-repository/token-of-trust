<?php

namespace TOT;

class Current_User {

	private static $instance = null;

	private function __construct() {}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new User(get_current_user_id());
		}
		return self::$instance;
	}

}