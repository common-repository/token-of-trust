<?php

namespace TOT\Integrations\WooCommerce;

use TOT\Settings;
use TOT\tot_debugger;
use TOT\API_Product;

class Product_Sync
{
	/**
	 * @var array[]. product attributes, global attrs for select inputs and local for text inputs
	 */
	private $attrs = [
		[
			'label' => 'Product Type',
			'id' => 'product-type',
			'options' => [
				'vape',
                'Exempt from Vape Taxes'
			],
			'type' => 'string',
		],
		[
			'label' => 'Volume (mL)',
			'id' => 'volumeInMl',
			'placeholder' => 'e.g., 1000',
			'type' => 'number',
			'tip' => 'The amount of liquid in the product or refill.',
			'class' => 'depend-on-vape',
		],
		[
			'label' => 'Product Brand',
			'id' => 'brand',
			'placeholder' => '',
			'type' => 'string',
			'tip' => 'The product\'s brand.',
			'class' => 'depend-on-vape',
		],
		[
			'label' => 'Wholesale Cost',
			'id' => 'wholesaleCost',
			'placeholder' => '',
			'type' => 'number',
			'tip' => 'What the wholesaler pays the manufacturer for the product. If this is the manufacturer then this is the cost of materials to produce the product.',
			'class' => 'depend-on-vape',
		],
		[
			'label' => 'Wholesale Price',
			'id' => 'wholesalePrice',
			'placeholder' => '',
			'type' => 'number',
			'tip' => 'What the wholesaler charges the retailer.',
			'class' => 'depend-on-vape',
		],
		[
			'label' => 'MSRP',
			'id' => 'msrp',
			'placeholder' => '',
			'type' => 'number',
			'tip' => 'The manufacturers suggested retail price.',
			'tot-id' => 'msrp',
			'class' => 'depend-on-vape',
		],
		[
			'label' => 'Retail Price',
			'id' => 'retailPrice',
			'placeholder' => '',
			'type' => 'number',
			'tip' => 'The actual retail price without consideration of sales, etc.',
			'class' => 'depend-on-vape',
		],
		[
			'label' => 'System Type',
			'id' => 'vapeSystemType',
			'options' => [
                "",
				"isLiquid",
				"isPart",
				"isOpen",
				"closedSingleUse",
				"closedRefillable",
				"closedCartridge",
				"batteryOnly",
				"pack"
			],
			'type' => 'string',
			'tip' => 'There are two primary types of delivery systems used
                        to heat electronic cigarette liquid to produce a vapor
                        that is then inhaled.
                        <br>1. The “closed system,” which consists of a
                        single-use, disposable vapor product prefilled
                        with electronic cigarette liquid or a vapor
                        product and “pods” or “cartridges” that are
                        prefilled, sealed by the manufacturer and not
                        intended to be refilled.
                        <br>2. The “open system,” which consists of any
                        electronic nicotine delivery system or vapor
                        product that is intended to be refillable.',
			'class' => 'depend-on-vape',
		],
		[
			'label' => 'Has Nicotine',
			'id' => 'hasNicotine',
			'options' => [
                '',
				'yes',
				'no',
			],
			'type' => 'bool',
			'tip' => 'Does the product have nicotine or not? In some jurisdictions products that don\'t have nicotine are not taxable.',
			'class' => 'depend-on-vape',
		],
	];

    /**
     * @var ?WC_Product 
     */
    private $current_product = null;

    /**
     * @var ?array product data from TOT
     */
    private $tot_product = null;

	public function __construct()
	{
		add_action('widgets_init', array($this, 'initialize'));
	}

	public function initialize()
	{
		if (!class_exists('WooCommerce') || !Settings::get_setting('tot_field_woo_enable_product_sync')) {
			return;
		}

		// Add custom attributes panel to the product data section
		add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_type_attribute_fields'));
		add_action('woocommerce_admin_process_product_object', array($this, 'save_product_type_attribute_field'));

		// Export
		add_filter('woocommerce_product_export_column_names', array($this, 'add_wc_export_columns'));
		add_filter('woocommerce_product_export_product_default_columns', array($this, 'add_wc_export_columns'));
		// get attribute values for export
		foreach ($this->attrs as $attr) {
			add_filter('woocommerce_product_export_product_column_' . $attr['id'], function ($value, $product) use ($attr) {
				return $this->get_attr_value($attr, $product) ;
			}, 10, 2);
		}

        // Import
		add_filter( 'woocommerce_csv_product_import_mapping_options', array($this, 'add_wc_export_columns') );
		add_filter( 'woocommerce_product_import_pre_insert_product_object', array($this, 'wc_process_import'), 10, 2 );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array($this, 'add_wc_columns_to_mapping_screen') );
	}

	private function get_attr_value($attr)
	{
        $tot_product = $this->tot_product;

		return is_array($tot_product) && isset($tot_product[$attr['id']])
            ? $tot_product[$attr['id']]
            : null;
	}

	public function add_product_type_attribute_fields()
	{
        global $product_object, $post_id;

        $product = $product_object instanceof \WC_Product ? $product_object : wc_get_product($post_id);
        $this->tot_product = $this->map_data_from_TOT(API_Product::send_get_requests($product));
		$attrs = $this->attrs;
		?>
        <div class="options_group">

			<?php
			foreach ($attrs as $attr):
				$value = $this->get_attr_value($attr);
				$id = ($attr['id'] ?? sanitize_title($attr['label']));
				?>
                <p class="form-field <?php echo $attr['class'] ?? ''; ?>" id="<?php echo $id; ?>_attribute_wrapper"
                   style="<?php echo $attr['style'] ?? ''; ?>">
                    <label for="<?php echo $id; ?>_attribute"><?php echo $attr['label'] ?></label>

					<?php if (isset($attr['options'])): ?>
                        <select id="<?php echo $id; ?>_attribute" name="<?php echo $id; ?>_attribute"
                                class="select short">
							<?php foreach ($attr['options'] as $option): ?>
                                <option <?php if ($option === ''): ?> disabled <?php endif; ?> value="<?php echo $option; ?>" <?php selected($value, $option); ?>>
									<?php echo $option; ?>
                                </option>
							<?php endforeach; ?>
                        </select>
					<?php else: ?>
                        <input class="text short" <?php echo @$attr['type'] == 'number' ? 'type="number" min="0"' : 'type="text"'; ?>
                               id="<?php echo $id; ?>_attribute" name="<?php echo $id; ?>_attribute"
                               value="<?php echo $value; ?>" placeholder="<?php echo $attr['placeholder'] ?? ''; ?>">
					<?php endif; ?>
					<?php if (isset($attr['tip'])): echo wc_help_tip($attr['tip'], true); endif; ?>
                </p>
			<?php endforeach; ?>
        </div>
		<?php
	}

    public function map_data_from_TOT($response)
    {
       $result = [];
       foreach ($response as $key => $value)
       {
           if ($key == 'isExempt')
           {
               $result['product-type'] = $value === false ? 'vape' : 'Exempt from Vape Taxes';
           } elseif ($key == 'productBrand') {
               $result['brand'] = $value;
           } else {
               $result[$key] = is_bool($value) 
                   ? ($value ? 'yes' : 'no')
                   : $value;
           }
       }

       return $result;
    }

	/**
	 * @param \WC_Product $product
	 * @return void
	 */
	public function save_product_type_attribute_field($product, $is_import = false)
	{
        $this->current_product = $product;

		add_settings_error('my_option', 'my_option_updated', 'Successfully saved', 'success');
		$attrs = $this->attrs;
		$first_id = $attrs[0]['id'];
		if (!isset($_POST[$first_id . '_attribute']) && !$is_import) {
			return;
		}

        $api_data = [];
		foreach ($attrs as $attr) {
			$id = $attr['id'] ?? sanitize_title($attr['label']);
			$value = isset($_POST[$id . '_attribute']) ? sanitize_text_field($_POST[$id . '_attribute']) : null;

			if (!$value) {
				continue;
			}

			// for syncing
            if ($id == "product-type") {
				$api_data['isExempt'] = $_POST[$first_id . '_attribute'] !== 'vape';
			} else {
                $api_data[$attr['totId'] ?? $id] = $attr['type'] == 'bool' ? $value == 'yes' : $value;
			}
		}

		$api_data['name'] = $product->get_name();
		$api_data['sku'] = $product->get_sku();
		$api_data['packQuantity'] = 1;

        API_Product::send_update_requests($product, $api_data);
	}

	/**
	 * Add the custom column to the exporter and the exporter column menu.
	 *
	 * @param array $columns
	 * @return array $columns
	 */
	public function add_wc_export_columns($columns)
	{

		// column slug => column name
		foreach ($this->attrs as $attr) {
			$columns[$attr['id']] = $attr['label'];
		}
		return $columns;
	}

	/**
	 * Add automatic mapping support for 'Custom Columns'.
	 *
	 * @param array $columns
	 * @return array $columns
	 */
	function add_wc_columns_to_mapping_screen( $columns ) {

		// potential column name => column slug
		foreach ($this->attrs as $attr) {
			$columns[$attr['label']] = $attr['id'];
			$columns[strtolower($attr['label'])] = $attr['id'];
			$columns[strtoupper($attr['label'])] = $attr['id'];
		}
		return $columns;

	}


	/**
	 * Process the data read from the CSV file.
	 * This just saves the value in meta data, but you can do anything you want here with the data.
	 *
	 * @param WC_Product $product - Product being imported or updated.
	 * @param array $data - CSV data read for the product.
	 * @return WC_Product $object
	 */
	public function wc_process_import( $product, $data ) {

        $has_tot_field = false;
		foreach ($this->attrs as $attr) {
			if ( ! empty( $data[$attr['id']] ) ) {
				$has_tot_field = true;
				$_POST[$attr['id'] . '_attribute'] = $data[$attr['id']];
			}
		}

		// has at least one of tot fields
        if ($has_tot_field) {
			$this->save_product_type_attribute_field($product, true);
		}

		return $product;
	}

}