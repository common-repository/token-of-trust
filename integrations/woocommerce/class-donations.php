<?php
namespace TOT\Integrations\WooCommerce;
class Donations {

	private static $tables = [];

	public static function get_tables()
	{
        $tables = self::get_default_tables_var();
		$order_statuses = wc_get_order_statuses();
		unset(
			$order_statuses['wc-refunded'],
			$order_statuses['wc-pending'],
			$order_statuses['wc-cancelled'],
			$order_statuses['wc-failed']
		);

        $args = [
            'status' => array_keys($order_statuses),
            'limit' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'     => 'tot_donation_value',
                    'value'   => 0,
                    'compare' => '>',
                )
            )
        ];
        
		foreach ($tables as $key => $table) {

			// no need to fetch the data again
			if ( is_array($table['orders']) ) {
				continue;
			}

			if ($table['date_created']) {
				$args['date_created'] = $table['date_created'];
			} else {
                unset($args['date_created']);
            }

			$table['orders'] = wc_get_orders($args);
			$table['sum'] = array_reduce($table['orders'], function ($total, $order)
			{
				$total += $order->get_meta('tot_donation_value', true);
				return $total;
			}, 0);

			$tables[$key] = $table;
		}

		return self::$tables = $tables;
	}

    private static function get_default_tables_var()
    {
        return [
            [
                "label" => "All months",
                "date_created" => null,
                "orders" => null,
                "sum" => 0
            ],
            [
                "label" => "The current month",
                "date_created" => strtotime('1 month ago'),
                "orders" => null,
                "sum" => 0
            ],
            [
                "label" => "Last month",
                "date_created" => strtotime('2 months ago'),
                "orders" => null,
                "sum" => 0
            ],
            [
                "label" => "Two months ago",
                "date_created" => strtotime('3 months ago'),
                "orders" => null,
                "sum" => 0
            ]
        ];
    }

	public function __construct() {
		add_action('init', array($this, 'handle_export_table'));
	}

	public function handle_export_table() {
		if (isset($_GET['tot_export_table']) && is_numeric($_GET['tot_export_table']))
		{
			$this->export_table_as_csv();
		}
	}
	public function export_table_as_csv() {
		$table_no = $_GET['tot_export_table'];
		$tables = self::get_tables();
		if (!isset($tables[$table_no])) {
			return;
		}
		$table = $tables[$table_no];

		// Define CSV filename
		$filename = 'tot_donations_' .  time() . '.csv';

        // Set CSV headers
        $header_row = ['Date', 'Order number', 'Charitable amount', 'Order Url'];

        // Create a temporary file
        $temp_file_path = tempnam(sys_get_temp_dir(), 'TMP');
        $temp_file = fopen($temp_file_path, 'w');

        // Check if the file was successfully created
        if ($temp_file === false) {
            die('Failed to create temporary file');
        }

        // Write CSV headers to the temporary file
        fputcsv($temp_file, $header_row);

        // Write table data rows to the temporary file
        foreach ($table['orders'] as $order) {
            /** @var \WC_Order $order */
            fputcsv($temp_file, [
                $order->get_date_created(),
                $order->get_id(),
                '$' . $order->get_meta( 'tot_donation_value', true),
                $order->get_edit_order_url()
            ]);
        }

        // Close the file
        fclose($temp_file);

        // Set appropriate headers for file download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);

        // Output the contents of the temporary file
        readfile($temp_file_path);

        // Delete the temporary file
        unlink($temp_file_path);

        exit();
	}
}