<?php
/**
 *
 * Settings Page
 *
 * array['SECTION_NAME']                                             array   Human section page name, can use capital letters and spaces
 * array['SECTION_NAME']['description']                              string  Section description displayed on page
 * array['SECTION_NAME']['fields]                                    array   Array of fields
 * array['SECTION_NAME']['fields][0]                                 array   Each field definition is an array
 * array['SECTION_NAME']['fields][0]['id']                           string  identifier used by wordpress
 * array['SECTION_NAME']['fields][0]['type']                         string  checkbox | text | select | multiselect
 * array['SECTION_NAME']['fields][0]['label']
 * array['SECTION_NAME']['fields][0]['options']                      array
 * array['SECTION_NAME']['fields][0]['options']['prepend']           string  Text to display after the input
 * array['SECTION_NAME']['fields][0]['options']['append']            string  Text to display before the input
 * array['SECTION_NAME']['fields][0]['options']['default_value']     string  Default value stored in the database
 * array['SECTION_NAME']['fields][0]['options']['options']           array   Used for select and multiselect field ['type']
 * array['SECTION_NAME']['fields][0]['options']['options']['label']  string  Text displayed to the user while selecting
 * array['SECTION_NAME']['fields][0]['options']['options']['value']  string  Value stored in the database
 *
 * @param string $page_title Human friendly page name, can use capital letters and spaces
 * @param array $sections (See above)
 *
 */

namespace TOT\Settings;

class Page {

	public $main_page_slug = 'totsettings';
	public $menu_title = '';
	public $menu_key = '';
	public $capability = 'manage_options';
	public $page_title = '';
	public $page_slug = '';
	public $sections = array();

	public function __construct($page_title, $sections=array(), $menu_title=null, $capability='manage_options') {
		$this->menu_title = isset($menu_title) ? $menu_title : $page_title;
		$this->menu_key = $this->slugify_underscore($this->menu_title);
		$this->capability = $capability;

		$this->page_title = $page_title;
		$this->page_slug = 'tot_settings_' . $this->menu_key;

		$this->section_slug = 'tot_section_' . $this->menu_key;
		$this->sections = $sections;

		register_setting('tot_' . $this->menu_key, 'tot_options');
		$this->register_page();
		$this->register_fields();
		$this->check_for_dependencies();
		$this->check_for_save();
	}

	public function is_current_page() {
		global $pagenow;

		if(!isset($_GET['page']) || ($pagenow !== 'admin.php')) {
			return false;
		}

		$page_slug = sanitize_text_field($_GET['page']);

		if( $page_slug !== $this->page_slug ) {
			return false;
		}

		return true;
    }

	public function check_for_save() {

	    if(! $this->is_current_page() || !isset($_POST['action']) || ($_POST['action'] !== 'update')) {
	        return;
        }

		$settings = get_option('tot_options');

		$unsanitized_input = $_POST['tot_options'];
		$sanitized_input = $this->sanitize($unsanitized_input);

		foreach($this->sections as $section_key => $section) {

            $fields = $section['fields'];

            while ($field = array_shift($fields)) {
                $field_key = $this->get_full_key_name($field['id']);

                if($field['type'] === 'checkbox') {
                    $settings[$field_key] = isset($sanitized_input[$field_key]) ? '1' : '';
                }if($field['type'] === 'currency') {
                    $result = $this->validate_currency($field_key, $unsanitized_input[$field_key]);
                    if(is_wp_error($result)) {
                        $settings[$field_key] = '';
                        continue;
                    }
                    $settings[$field_key] = $this->sanitize_currency($field_key, $unsanitized_input[$field_key]);
                }else {
                    $settings[$field_key] = $sanitized_input[$field_key] ?? '';
                }

                // to save extra fields
                if (isset($field['extra_fields']) && !empty($field['extra_fields'])) {
                    array_push($fields, ...$field['extra_fields']);
                }
            }
		}

		update_option('tot_options', $settings, true);
		tot_add_flash_notice('Settings updated', 'success', false);
	}

	public function validate_currency($key, $sanitized_input) {
	    if($sanitized_input == null) {
	        return true;
        }
        if(!is_numeric($sanitized_input)) {
	        add_settings_error($key, 'non-currency', 'Please enter a number as a minimum.');
	        return new \WP_Error();
        }
    }

    public function check_for_dependencies() {
	    if(! $this->is_current_page()) {
		    return;
	    }

	    $has_multiselect = false;

	    foreach($this->sections as $section_key => $section) {
		    foreach ( $section['fields'] as $field_index => $field ) {
		        if(!$field['type']) {
		            continue;
                }
                if($field['type'] = 'multiselect') {
	                $has_multiselect = true;
                }
		    }
	    }

	    if($has_multiselect) {
		    add_action('admin_enqueue_scripts', function() {
			    wp_enqueue_script(
            'admin-token-of-trust-select2-js',
                    plugins_url( '../lib/select2/dist/js/select2.js', __FILE__ ),
                    array('jquery')
                );
			    wp_enqueue_style(
				    'admin-token-of-trust-select2-css',
				    plugins_url( '../lib/select2/dist/css/select2.css', __FILE__ )
			    );
            });


        }
    }

	public function sanitize_currency($key, $input) {
	    if($input == null) {
	        return null;
        }
		return floatval(str_replace(',', '', sanitize_text_field($input)));
	}

	public function register_page() {
		if (
			tot_option_has_a_value(tot_get_setting_license_key())
			&& tot_option_has_a_value(tot_get_setting_prod_domain())
			&& tot_live_or_in_trial()
		) {
			add_submenu_page(
				$this->main_page_slug,
				$this->page_title,
				$this->menu_title,
				$this->capability,
				$this->page_slug,
				array($this, 'get_settings_page')
			);
        }

	}

	public function register_fields() {
		foreach($this->sections as $section_key => $section) {
		    $section_slug = $this->slugify_underscore($section_key);
			add_settings_section(
				$section_slug,
				__($section_key, 'token-of-trust'),
				array($this, 'section_intro'),
				$this->page_slug
			);
			foreach($section['fields'] as $field_key => $field ) {

				$full_key_name = $this->get_full_key_name($field['id']);

				$callback = array($this, 'no_op');
				if($field['type'] === 'checkbox') {
                    $callback = array($this, 'render_checkbox_field');
                } elseif( $field['type'] === 'radio') {
                    $callback = array($this, 'render_radio_field');
                } elseif( $field['type'] === 'currency' ) {
					$callback = array($this, 'render_currency_field');
				}elseif( $field['type'] === 'text' ) {
					$callback = array($this, 'render_text_field');
                }elseif( $field['type'] === 'select' ) {
					$callback = array($this, 'render_select_field');
				}elseif( $field['type'] === 'multiselect' ) {
					$callback = array($this, 'render_multiselect_field');
				}elseif( $field['type'] === 'number' ) {
					$callback = array($this, 'render_number_field');
				}

				add_settings_field(
					$full_key_name, // as of WP 4.6 this value is used only internally
					// use $args' label_for to populate the id inside the callback
					__($field['label'], 'token-of-trust'),
					$callback,
					$this->page_slug,
					$section_slug,
					array_merge(
                        array(
						    'label_for' => $full_key_name
					    ),
                        empty($field['options']) ? [] : $field['options'],
                        empty($field['extra_fields']) ? [] : ['extra_fields' =>$field['extra_fields']]
                    )
				);

			}
		}
	}

    public function get_full_key_name ( $id ) {
        return 'tot_field_' . $this->slugify_underscore($id);
    }
	public function slugify_dash( $string ) {
		return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
	}

	public function slugify_underscore( $string ) {
		return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $string)));
	}

	public function get_settings_page() {
		$page_slug = sanitize_text_field($_GET['page']);

		?>
		<div class="wrap tot-settings-page">
			<?php settings_errors(); ?>
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="?page=<?php echo $page_slug; ?>" method="post">
                <div class="tot-left-col">
                    <?php
						echo settings_fields($page_slug);
						echo do_settings_sections($page_slug);
						if(count($this->sections) > 0) {
							echo submit_button( 'Save Settings' );
						}
					?>
				</div>
			</form>
		</div>
		<?php
	}

	public function no_op() {}

	public function sanitize($input) {
		$new_input = array();
		foreach ( $input as $key => $val ) {
		    if(is_array($val)) {
			    $new_input[ $key ] = $this->sanitize( $val );
            }else {
			    $new_input[ $key ] = sanitize_text_field( $val );
		    }
		}
		return $new_input;
    }

    public function section_intro($arg) {

	    if(!isset($arg['title']) || empty( $arg['callback'][0]->sections[$arg['title']]['description'] )) {
	        return;
        }

	    $description = $arg['callback'][0]->sections[$arg['title']]['description'];

	    if ( is_array( $description ) ) {
		    foreach ( $description as $paragraph ) {
			    ?>
                <p>
				    <?php echo $paragraph; ?>
                </p>
			    <?php
		    }
	    }else {
		    ?>
            <p>
			    <?php echo $description; ?>
            </p>
		    <?php
        }
    }

	public function field_header($paragraphs) {
		if ( isset( $paragraphs ) ) {
			foreach ( $paragraphs as $paragraph ) {
				?>
				<p>
					<?php echo $paragraph; ?>
				</p>
				<?php
			}
		}
	}

	public function field_footer($paragraphs) {
		if ( isset( $paragraphs ) ) {
			foreach ( $paragraphs as $paragraph ) {
				?>
				<p class="description">
					<?php echo $paragraph; ?>
				</p>
				<?php
			}
		}
	}

	public function render_checkbox_field($args) {
		$options = get_option( 'tot_options' );
		$this->field_header($args['prepend']);
		?>
		<input type="checkbox"
               class="tot_field_checkbox"
               id="<?php echo esc_attr( $args['label_for'] ); ?>"
		       name="tot_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		       value="1" <?php checked( isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : "", "1", true ); ?> />
		<?php
		$this->field_footer($args['append']);
	}

	public function render_currency_field($args) {
		$options = get_option( 'tot_options' );
		$this->field_header($args['prepend']);

		//Todo: $args['default_value'] should be saved to DB, not just prepopulated
        $value = '';
        if( isset($options[$args['label_for']]) ) {
            $value = $options[$args['label_for']];
        }elseif( isset($args['default_value']) ) {
            $value = $args['default_value'];
        }
		?>
        <div class="tot_field_group">
            <?php
            if(isset($args['prefix'])) {
                echo '<span class="tot_prefix">' . $args['prefix'] . '</span>';
            }?>
            <input type="number"
                   step="any"
                   class="tot_field_currency"
                   value="<?php echo $value; ?>"
                   id="<?php echo esc_attr($args['label_for']); ?>"
                   name="tot_options[<?php echo esc_attr($args['label_for']); ?>]"/>
        </div>
		<?php
		$this->field_footer($args['append']);
	}

	public function render_multiselect_field($args) {
		$options = get_option( 'tot_options' );
		$this->field_header($args['prepend']);
		?>
        <select class="tot_field_multiselect"
                multiple
                id="<?php echo esc_attr($args['label_for']); ?>"
                name="tot_options[<?php echo esc_attr($args['label_for']); ?>][]">
			<?php
			foreach ($args['options'] as $opt) {
				$selected = '';
				if(isset($options) && isset($options[$args['label_for']]) && $options[$args['label_for']] !== '') {
					foreach ( $options[ $args['label_for'] ] as $selection ) {
						$result = selected( $selection, $opt['value'], false );
						if ( $result ) {
							$selected = $result;
							break;
						}
					}
				}
				echo '<option value="' . $opt['value'] . '" ' . $selected . '>' . $opt['label'] . '</option>';
			} ?>
        </select>
		<?php
		$this->field_footer($args['append']);
	}

	public function render_select_field($args) {
		$options = get_option( 'tot_options' );
		$this->field_header($args['prepend']);
		?>
        <select class="tot_field_select"
                id="<?php echo esc_attr($args['label_for']); ?>"
                name="tot_options[<?php echo esc_attr($args['label_for']); ?>]">
			<?php
			foreach ($args['options'] as $opt) {
				$selected = '';
				if(isset($options) && isset($options[$args['label_for']])) {
					$selected = selected($options[$args['label_for']], $opt['value'], false);
				}
				echo '<option value="' . $opt['value'] . '" ' . $selected . '>' . $opt['label'] . '</option>';
			} ?>
        </select>
		<?php
		$this->field_footer($args['append']);
	}

	public function render_number_field($args) {
		$options = get_option( 'tot_options' );
		$this->field_header($args['prepend']);
		$value = '';
		if( isset($options[$args['label_for']]) ) {
			$value = $options[$args['label_for']];
		}elseif( isset($args['default_value']) ) {
			$value = $args['default_value'];
		}
		?>
        <div class="tot_field_group">
            <?php
            if(isset($args['prefix'])) {
                echo '<span class="tot_prefix">' . $args['prefix'] . '</span>';
            }?>
            <input type="number"
                   class="tot_field_number"
                   value="<?php echo $value; ?>"
                   id="<?php echo esc_attr($args['label_for']); ?>"
                   name="tot_options[<?php echo esc_attr($args['label_for']); ?>]"/>
        </div>
		<?php
		$this->field_footer($args['append']);
	}

	public function render_text_field($args) {
		$options = get_option( 'tot_options' );
		$this->field_header($args['prepend']);
		$value = '';
		if( isset($options[$args['label_for']]) ) {
			$value = $options[$args['label_for']];
		}elseif( isset($args['default_value']) ) {
			$value = $args['default_value'];
		}
		?>
        <input type="text"
               class="tot_field_text"
               value="<?php echo $value; ?>"
               id="<?php echo esc_attr($args['label_for']); ?>"
               name="tot_options[<?php echo esc_attr($args['label_for']); ?>]"/>
		<?php
		$this->field_footer($args['append']);
	}

    public function render_radio_field($args) {
        $options = get_option( 'tot_options' );
        $this->field_header($args['prepend']);

        $isChecked = false;
        foreach ($args['options'] as $option) {
            $label = $option['label'];
            $value = $option['value'] ?? null;
            // to allow custom input
            $default_value = $option['default_value'] ?? null;
            $stored_value = $options[ $args['label_for'] ] ?? $args['default_value'];
            $id = esc_attr($args['label_for']) . '-' . $value . $default_value;

            $checked = false;
            if (!$isChecked && $value === $stored_value) {
                $checked = true;
                $isChecked = true;
            } elseif (!$isChecked && $default_value) {
                $checked = true;
                $value = $stored_value;
            }
        ?>
            <p>
                <input type="radio"
                       class="tot_field_radio <?php echo esc_attr( $args['label_for'] ); ?>"
                       id="<?php echo $id; ?>"
                       name="tot_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
                       value="<?php echo $value ?: $default_value; ?>"
                        <?php checked( $checked ); ?> />
                <label for="<?php echo $id; ?>">
                    <?php echo $label; ?>
                    <?php if ($default_value) : ?>
                    <input type="number"
                           min="0"
                           class="tot_field_number_to_radio"
                           value="<?php echo is_numeric($stored_value) ? $stored_value : $default_value; ?>"
                           data-for="<?php echo $id; ?>"
                           /> &nbsp;.
                    <?php endif; ?>
                </label>
            </p>
        <?php
        }

        if (isset($args['extra_fields'])) {
            $this->render_extra_fields($args['label_for'], $args['extra_fields']);
        }
        $this->field_footer($args['append']);
    }

    public function render_extra_fields($to, $fields) {

        ?>
        <div class="extra-fields extra-fields-to-<?php echo $to; ?>">
        <?php foreach ($fields as $field) :?>
            <p>
                <label>
                    <?php echo $field['label']; ?>
                </label>

                <?php
                // for now no need to check fields' type
                $this->render_text_field([
                    'label_for' => $this->get_full_key_name($field['id']),
                    'options' => $field['options'],
                    'prepend' => $field['options']['prepend'] ?? [],
                    'append' => $field['options']['append'] ?? []
                ]);
                ?>
            </p>
        <?php endforeach; ?>
        </div>
    <?php
    }

}