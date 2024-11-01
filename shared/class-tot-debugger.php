<?php

namespace TOT;
use TOT\Integrations\Sentry\Sentry;

/**
 * Used to filter, store and display logs
 */
class tot_debugger {
	/**
	 * @var tot_debugger
	 */
	private static $instance;

	/**
	 * New logs from current run
	 * which will be stored at database at the end
	 * @var Array
	 */
	private $new_logs = [];

	/**
	 * Storing all new heads to
	 * prevent repeats at the same run
	 * @var Array
	 */
	private $new_heads = [];

	/**
	 * the log  of some operations need to be collected through several lines
	 * This array used to collect these operations before saving them
	 * @var array
	 */
	private $operations = [];

	private function __construct() {

		// if the page almost end but there are still some operations need saving
		add_action( 'shutdown', array( $this, 'store_all_operations' ), 10 );

		// store all new logs to the database
		add_action( 'shutdown', array( $this, 'store_logs_to_db' ), 12 );
	}

	/**
	 * Singleton pattern
	 * @return tot_debugger
	 */
	public static function inst() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Note: If the head is repeated at the same run it will not log it to prevent repetition
	 *
	 * @param string $head A description of that log
	 * @param mixed $log The log message
	 * @param string $type 'info', 'error', 'success' or 'warning'
	 *
	 * @return void
	 */
	public function log( $head, $log = '', $type = "info" ) {

		if ($type == 'error') {
			Sentry::inst()->captureError($head, $log);
		}

		// Don't store if the debug mode is not active
		if ( ! tot_debug_mode() && ! \TOT\Settings::get_param_or_cookie( 'debug_mode' ) ) {
			return;
		}

		if ( ! in_array( $type, [ 'info', 'error', 'success', 'warning' ] ) ) {
			$type = 'info';
		}

        // Go ahead and send the entry to sentry if tot_debug_mode...
        // * @method captureInfo(string $head, string $log)
        // * @method captureWarning(string $head, string $log)
        // * @method captureError(string $head, string $log)
        if ($type != 'error') {
            switch ($type) {
                case 'warning':
                    Sentry::inst()->captureWarning($head, $log);
                    break;
                default:
                    // Handle unknown log types or add a default log type
                    Sentry::inst()->captureInfo($head, $log);
                    break;
            }
        }

        // Don't accept repeat log at the same run
		$new_heads = $this->new_heads;
		if ( array_key_exists( $head, $new_heads ) ) {
			return;
		}

		$new_log = array(
			'timestamp' => current_time( 'mysql' ),
			'body'      => print_r($log, true),
			'type'      => $type
		);

		if ( ! empty( $head ) ) {
			$new_log['head']   = $head;
			$this->new_heads[] = $head;
		}

		$this->new_logs[] = $new_log;
	}

	/**
	 * Storing new logs to database
	 * It will be triggered before php execution ends
	 *
	 * @return void
	 */
	public function store_logs_to_db() {
		// get old logs
		$db_logs  = get_option( "tot_logs", array() );
		$new_logs = $this->new_logs;

		foreach ( $new_logs as $new_log ) {
			array_unshift( $db_logs, $new_log );
		}

		$db_logs = array_slice( $db_logs, 0, apply_filters( 'tot_logs_max_length', 200 ) );
		update_option( "tot_logs", $db_logs, false);

		$this->new_logs  = [];
		$this->new_heads = [];
	}

	/**
	 * Save all operations to database
	 * @return void
	 */
	public function store_all_operations() {
		$operations_keys = array_keys( $this->operations );
		foreach ( $operations_keys as $key ) {
			$this->log_operation( $key );
		}
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	public function register_new_operation( $key ) {
		$this->operations[ $key ] = '';
	}

	/**
	 * @param string $key
	 * @param string $head
	 * @param string $part
	 *
	 * @return void
	 */
	public function add_part_to_operation( $key, $head, $part = '' ) {

		if ( ! isset( $this->operations[ $key ] ) && !empty($key) ) {
			$this->register_new_operation( $key );
		}

		// if key is empty then add this part to the last operation
		if ( empty( $key ) && ! empty( $this->operations ) ) {
			$key = array_keys( $this->operations )[ count( $this->operations ) - 1 ];;
		}

		$this->operations[ $key ] .= '<b>' . $head . '</b>' . ( $part ? ': ' : '' ) . print_r($part, true) . PHP_EOL;
	}

	/**
	 * log the operation, After collecting all information it.
	 *
	 * @param $key
	 *
	 * @return void
	 */
	public function log_operation( $key ) {
		if ( ! isset( $this->operations[ $key ] ) ) {
			return;
		}

		$this->log( $key, $this->operations[ $key ] );
		unset( $this->operations[ $key ] );
	}
}
