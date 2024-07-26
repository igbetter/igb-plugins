<?php
/**
 * Likert field controller
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\controllers;

use FrmAppHelper;
use FrmDb;
use FrmField;
use FrmFieldFactory;
use FrmFieldsHelper;
use FrmProAppHelper;
use FrmProFieldsHelper;
use FrmProGraphsController;
use FrmProStatisticsController;
use FrmStylesController;
use FrmSurveys\helpers\AppHelper;
use FrmSurveys\helpers\FieldsHelper;
use FrmSurveys\models\fields\Likert;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Likert field routing.
 *
 * @since 1.0
 */
class LikertController {

	/**
	 * Track likert error keys.
	 *
	 * @var array {
	 *     array(
	 *         'likert row 1 error key' => false, // This message will be removed.
	 *         'likert row 2 error key' => array(
	 *             'key' => 'likert error key', // The message will be copied to this key.
	 *             'msg'  => 'likert error message', // The replaced message.
	 *         ),
	 *     )
	 * }
	 */
	private static $error_keys = array();

	/**
	 * Init the Likert field code.
	 *
	 * @param string $class The Class name.
	 * @param string $field_type The name of the field type.
	 *
	 * @since 1.0
	 */
	public static function get_field_type_class( $class, $field_type ) {
		if ( 'likert' === $field_type ) {
			return '\FrmSurveys\models\fields\Likert';
		}
		return $class;
	}

	/**
	 * Enable Likert fields in the builder.
	 *
	 * @param array $fields The full field list.
	 *
	 * @since 1.0
	 */
	public static function add_to_available_fields( $fields ) {
		if ( ! isset( $fields['likert'] ) ) {
			return $fields;
		}

		unset( $fields['likert']['addon'] );
		$fields['likert']['icon'] = 'frm_icon_font frm_likert_scale';

		return $fields;
	}

	/**
	 * Add options in the field settings before the accordions.
	 *
	 * @param array $field Field data.
	 * @param array $args  Includes field_obj, display,and values.
	 *
	 * @since 1.0
	 */
	public static function show_options_before( $field, $args ) {
		$field_obj = $args['field_obj'];
		$display   = $args['display'];
		$values    = $args['values'];

		if ( ! $field_obj instanceof Likert ) {
			return;
		}

		include AppHelper::plugin_path() . '/classes/views/fields/backend/likert-row-options.php';
		include AppHelper::plugin_path() . '/classes/views/fields/backend/likert-column-options.php';
	}

	/**
	 * Gets row IDs from a likert field.
	 *
	 * @param array|object|int $field Field ID, field array or object.
	 * @return array
	 */
	public static function get_row_ids( $field ) {
		if ( is_numeric( $field ) ) {
			$field = FrmField::getOne( $field );
			if ( ! $field ) {
				return array();
			}
		}

		$field = (array) $field;
		if ( 'likert' !== $field['type'] ) {
			return array();
		}

		$row_ids = FrmField::get_option( $field, 'rows' );
		if ( ! is_array( $row_ids ) ) {
			$row_ids = array();
		}

		return $row_ids;
	}

	/**
	 * Gets row fields of likert field.
	 *
	 * @param array|object|int $field Field data or ID.
	 * @return object[]
	 */
	public static function get_row_fields( $field ) {
		$fields = FrmField::get_option( $field, 'row_fields' );
		if ( $fields && is_array( $fields ) ) {
			return array_map( array( self::class, 'cast_to_object' ), $fields );
		}

		$ids    = self::get_row_ids( $field );
		$fields = array();
		foreach ( $ids as $id ) {
			$row_id = FrmField::getOne( $id );
			if ( $row_id ) {
				$fields[ $id ] = $row_id;
			}
		}
		return $fields;
	}

	/**
	 * Cast value to object.
	 *
	 * @since 1.0.04
	 *
	 * @param mixed $value target value that is being converted to an object.
	 * @return object
	 */
	private static function cast_to_object( $value ) {
		return (object) $value;
	}

	/**
	 * Gets rows for option.
	 *
	 * @deprecated 1.0.06
	 *
	 * @param array|object|int $field Field data or ID.
	 * @return array
	 */
	public static function get_rows_for_option( $field ) {
		_deprecated_function( __FUNCTION__, '1.0.06' );
		$row_fields = self::get_row_fields( $field );
		$options    = array();

		foreach ( $row_fields as $row_field ) {
			$options[ $row_field->field_key ] = $row_field->name;
			unset( $row_field );
		}

		return $options;
	}

	/**
	 * Checks if given field is a likert field.
	 *
	 * @param array|object $field Field data.
	 * @return bool
	 */
	public static function is_likert_field( $field ) {
		return 'likert' === FrmField::get_field_type( $field );
	}

	/**
	 * Checks if given field is a likert row.
	 *
	 * @param array|object|int $field Field data or ID.
	 * @return int Likert ID or `0`.
	 */
	public static function is_likert_row( $field ) {
		if ( is_numeric( $field ) ) {
			$field = FrmField::getOne( $field );
			if ( ! $field ) {
				return 0;
			}
		}

		return (int) FrmField::get_option( $field, 'likert_id' );
	}

	/**
	 * Check if the field is inside the specified likert field.
	 *
	 * @param array|object $child     Child field data.
	 * @param int          $parent_id Parent field ID.
	 * @return bool
	 */
	private static function is_row_in_likert( $child, $parent_id ) {
		return self::is_likert_row( $child ) === (int) $parent_id;
	}

	/**
	 * Gets column.
	 *
	 * @param array|object $field Field data.
	 * @return array
	 */
	public static function get_columns( $field ) {
		$field   = (array) $field;
		$columns = array();

		if ( ! empty( $field['options'] ) ) {
			$columns = (array) $field['options'];
		}

		if ( ! is_array( reset( $columns ) ) ) {
			foreach ( $columns as $index => $column ) {
				$columns[ $index ] = self::convert_column_option( $column );
			}
		}

		return $columns;
	}

	/**
	 * Deletes row fields when Likert field is deleted.
	 *
	 * @param int $field_id Likert field ID.
	 */
	public static function delete_row_fields( $field_id ) {
		$field = FrmField::getOne( $field_id );
		if ( ! $field ) {
			return;
		}

		if ( 'likert' !== $field->type ) {
			return;
		}

		$row_ids = self::get_row_ids( $field );
		foreach ( $row_ids as $row_id ) {
			FrmField::destroy( $row_id );
		}
	}

	/**
	 * Shows Likert options (Rows or Columns).
	 *
	 * @param array $args Arguments.
	 */
	public static function show_likert_opts( $args ) {
		$default_args = array(
			'singular_name'        => __( 'Item', 'formidable-surveys' ),
			'plural_name'          => __( 'Items', 'formidable-surveys' ),
			'add_label'            => __( 'Add Item', 'formidable-surveys' ),
			'base_name'            => 'likert_opts',
			'base_id'              => 'frm-add-items-' . FrmProAppHelper::get_rand( 3 ),
			'items'                => array(),
			'likert_id'            => 0,
			'remove_key_from_name' => false,
			'option_type'          => 'likert_row',
		);

		$args = wp_parse_args( $args, $default_args );
		?>
		<div
			class="frm_likert_opts_container frm_grid_container"
			data-base-name="<?php echo esc_attr( $args['base_name'] ); ?>"
			data-base-id="<?php echo esc_attr( $args['base_id'] ); ?>"
			data-likert-id="<?php echo esc_attr( $args['likert_id'] ); ?>"
			data-option-type="<?php echo esc_attr( $args['option_type'] ); ?>"
		>
			<span class="frm_likert_bulk_edit_opts frm-bulk-edit-link">
				<a href="#" class="frm-bulk-edit-link" data-option-type="<?php echo esc_attr( $args['option_type'] ); ?>">
					<?php
					// translators: Plural name.
					printf( esc_html__( 'Bulk Edit %s', 'formidable-surveys' ), esc_html( $args['plural_name'] ) );
					?>
				</a>
			</span>

			<ul class="frm_likert_opts" id="<?php echo esc_attr( $args['base_id'] ); ?>_options">
				<?php
				foreach ( $args['items'] as $item_key => $item_title ) {
					if ( is_object( $item_title ) ) {
						$single_args = array(
							'item_id'    => $item_title->id,
							'item_key'   => $item_title->field_key,
							'item_title' => $item_title->name,
						);
					} else {
						$single_args = array(
							'item_id'    => '',
							'item_key'   => $item_key,
							'item_title' => $item_title,
						);
					}

					$single_args = $args + $single_args;
					self::show_likert_single_opt( $single_args );
				}
				?>
			</ul>

			<div class="frm6 frm_form_field frm_add_opt_container">
				<a href="#" class="frm_likert_add_opt frm-small-add">
					<span class="frm_icon_font frm_add_tag"></span>
					<?php echo esc_html( $args['add_label'] ); ?>
				</a>
			</div>

			<?php if ( 'likert_row' === $args['option_type'] ) : ?>
				<input type="hidden" name="deleted_<?php echo esc_attr( $args['base_name'] ); ?>" id="<?php echo esc_attr( $args['base_id'] ); ?>_deleted_keys" />
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Include a likert option in the form builder.
	 *
	 * @param array $args Includes item_key, item_title.
	 *
	 * @since 1.0
	 */
	public static function show_likert_single_opt( $args ) {
		?>
		<li class="frm_likert_opt frm_single_option" data-key="<?php echo esc_attr( $args['item_key'] ); ?>" data-id="<?php echo esc_attr( $args['item_id'] ); ?>">
			<?php FrmAppHelper::icon_by_class( 'frmfont frm_drag_icon frm-drag' ); ?>

			<?php
			if ( 'likert_column' === $args['option_type'] ) {
				self::show_likert_single_column_opt_inputs( $args );
			} else {
				self::show_likert_single_row_opt_inputs( $args );
			}
			?>
		</li>
		<?php
	}

	/**
	 * Shows Likert single row option inputs.
	 *
	 * @since 1.0.03
	 *
	 * @param array $args See {@see LikertController::show_likert_opts()}.
	 */
	protected static function show_likert_single_row_opt_inputs( $args ) {
		?>
		<input
			type="text"
			name="<?php echo esc_attr( $args['base_name'] ); ?>[<?php echo esc_attr( $args['item_key'] ); ?>]"
			value="<?php echo esc_attr( $args['item_title'] ); ?>"
			data-likert-id="<?php echo esc_attr( $args['likert_id'] ); ?>"
		/>

		<a href="#" class="frm_icon_font frm_remove_tag frm_likert_delete_opt" data-skip-frm-js="1"></a>
		<?php
	}

	/**
	 * Shows Likert single column option inputs.
	 *
	 * @since 1.0.03
	 *
	 * @param array $args See {@see LikertController::show_likert_opts()}.
	 */
	protected static function show_likert_single_column_opt_inputs( $args ) {
		$option = self::convert_column_option( $args['item_title'] );
		?>
		<input
			type="text"
			name="<?php echo esc_attr( $args['base_name'] ); ?>[<?php echo esc_attr( $args['item_key'] ); ?>][label]"
			value="<?php echo esc_attr( $option['label'] ); ?>"
			data-likert-id="<?php echo esc_attr( $args['likert_id'] ); ?>"
			class="frm_likert_opt_label"
		/>

		<a href="#" class="frm_icon_font frm_remove_tag frm_likert_delete_opt" data-skip-frm-js="1"></a>

		<span class="frm_likert_opt_value_wrapper frm-with-right-icon">
			<input
				type="text"
				name="<?php echo esc_attr( $args['base_name'] ); ?>[<?php echo esc_attr( $args['item_key'] ); ?>][value]"
				value="<?php echo esc_attr( $option['value'] ); ?>"
				data-likert-id="<?php echo esc_attr( $args['likert_id'] ); ?>"
				class="frm_likert_opt_value"
			/>

			<?php FrmAppHelper::icon_by_class( 'frmfont frm_save_icon' ); ?>
		</span>
		<?php
	}

	/**
	 * Converts column options from array( 'Option' ) to array( 'label' => 'Option', 'value' => 'Option' ).
	 *
	 * @since 1.0.03
	 *
	 * @param array|string $option Column option.
	 * @return array
	 */
	protected static function convert_column_option( $option ) {
		if ( is_array( $option ) && isset( $option['label'] ) && isset( $option['value'] ) ) {
			return $option;
		}

		if ( is_string( $option ) ) {
			return array(
				'label' => $option,
				'value' => $option,
			);
		}

		return array(
			'label' => '',
			'value' => '',
		);
	}

	/**
	 * Print likert option template.
	 */
	public static function print_likert_opt_tmpl() {
		?>
		<script type="text/html" id="tmpl-frm_likert_column_opt">
			<?php
			self::show_likert_single_opt(
				array(
					'option_type' => 'likert_column',
					'item_key'    => '{{ data.key }}',
					'item_id'     => '{{ data.id }}',
					'item_title'  => array(
						'label' => '{{ data.title }}',
						'value' => '{{ data.value }}',
					),
					'base_name'   => '{{ data.baseName }}',
					'likert_id'   => '{{ data.likertId }}',
				)
			);
			?>
		</script>

		<script type="text/html" id="tmpl-frm_likert_row_opt">
			<?php
			self::show_likert_single_opt(
				array(
					'option_type' => 'likert_row',
					'item_key'    => '{{ data.key }}',
					'item_id'     => '{{ data.id }}',
					'item_title'  => '{{ data.title }}',
					'base_name'   => '{{ data.baseName }}',
					'likert_id'   => '{{ data.likertId }}',
				)
			);
			?>
		</script>
		<?php
	}

	/**
	 * Saves columns and rows.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $values  Posted values.
	 */
	public static function on_update_form( $form_id, $values ) {
		// Get all Likert fields.
		$likert_fields = FrmField::get_all_types_in_form( $form_id, 'likert', '', 'include' );
		if ( ! $likert_fields ) {
			return;
		}

		foreach ( $likert_fields as $likert_field ) {
			if ( ! isset( $values[ 'deleted_rows_' . $likert_field->id ] ) ) {
				// This field was not clicked so its options are not rendered.
				continue;
			}

			$columns = array();
			$rows    = array();

			// Loop through $_POST once to get rows and columns.
			foreach ( $values as $key => $value ) {
				if ( 'deleted_rows_' . $likert_field->id === $key ) {
					// Delete row fields.
					if ( empty( $value ) ) {
						continue;
					}

					$value = json_decode( $value, true );
					if ( ! $value ) {
						continue;
					}

					$value = array_unique( $value );
					self::delete_rows( $value );
					continue;
				}

				if ( 'rows_' . $likert_field->id === $key ) {
					$rows = self::process_rows_before_saving( (int) $likert_field->id, $value );
					continue;
				}

				if ( 'columns_' . $likert_field->id === $key ) {
					$columns = self::process_columns_before_saving( $value, $likert_field );
				}
			}//end foreach

			self::save_rows_and_columns( $rows, $columns, $likert_field );
		}//end foreach
	}

	/**
	 * Processes Likert rows before saving.
	 *
	 * @since 1.0.11
	 *
	 * @param int   $likert_id Likert field ID.
	 * @param array $rows      Array of row names.
	 * @return array
	 */
	protected static function process_rows_before_saving( $likert_id, $rows ) {
		$existing_rows    = self::get_fresh_likert_rows( $likert_id );
		$return_rows      = array();
		$tracked_new_rows = array();
		foreach ( $rows as $key => $name ) {
			$existing_key = array_search( $name, $existing_rows, true );
			if ( false !== $existing_key ) {
				$return_rows[ $existing_key ] = $name;
				continue;
			}

			// If one of new row name exists, remove it, or if there are 2 new rows with the same name, remove the latter one.
			if ( in_array( $name, $tracked_new_rows, true ) ) {
				continue;
			}

			$return_rows[ $key ] = $name;
			$tracked_new_rows[]  = $name;
		}

		return $return_rows;
	}

	/**
	 * Gets fresh Likert rows without cache.
	 *
	 * @since 1.0.11
	 *
	 * @param int $likert_id Likert ID.
	 * @return array
	 */
	protected static function get_fresh_likert_rows( $likert_id ) {
		global $wpdb;

		$field_options = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT field_options FROM ' . $wpdb->prefix . 'frm_fields WHERE id = %d',
				$likert_id
			)
		);
		if ( ! $field_options ) {
			return array();
		}

		FrmAppHelper::unserialize_or_decode( $field_options );
		if ( ! is_array( $field_options ) || empty( $field_options['rows'] ) ) {
			return array();
		}

		$row_fields = FrmDb::get_results( 'frm_fields', array( 'id' => $field_options['rows'] ), 'field_key, name' );
		$rows       = wp_list_pluck( $row_fields, 'name', 'field_key' );

		return $rows;
	}

	/**
	 * Processes Likert columns before saving.
	 *
	 * @since 1.0.03
	 *
	 * @param array  $columns      Columns data retrieved from $_POST.
	 * @param object $likert_field Likert field.
	 * @return array
	 */
	protected static function process_columns_before_saving( $columns, $likert_field ) {
		$new_columns    = array();
		$separate_value = FrmField::get_option( $likert_field, 'separate_value' );

		foreach ( $columns as $column ) {
			if ( ! isset( $column['label'] ) || ! isset( $column['value'] ) ) {
				continue;
			}

			if ( $separate_value ) {
				$new_columns[] = array(
					'label' => $column['label'],
					'value' => $column['value'],
				);
			} else {
				$new_columns[] = array(
					'label' => $column['label'],
					'value' => $column['label'],
				);
			}
		}

		return $new_columns;
	}

	/**
	 * Deletes rows by field keys.
	 *
	 * @param array $keys Array of field keys.
	 */
	protected static function delete_rows( $keys ) {
		foreach ( $keys as $key ) {
			FrmField::destroy( FrmField::get_id_by_key( $key ) );
		}
	}

	/**
	 * Updates or create rows.
	 *
	 * @param array  $rows         List of rows with key is field key or number and value is the row.
	 * @param array  $columns      List of columns.
	 * @param object $likert_field Likert field data.
	 */
	protected static function save_rows_and_columns( $rows, $columns, $likert_field ) {
		$order          = 0;
		$row_ids        = array();
		$row_type       = FrmField::get_option( $likert_field, 'multi_selection' ) ? 'checkbox' : 'radio';
		$separate_value = FrmField::get_option( $likert_field, 'separate_value' );
		$blank_message  = FrmField::get_option( $likert_field, 'blank' );

		foreach ( $rows as $key => $title ) {
			$order++;

			if ( ! is_numeric( $key ) ) {
				// Update row field.
				$field = FrmField::getOne( $key );
				if ( ! $field ) {
					continue;
				}

				$field_options = $field->field_options;

				$field_options['separate_value'] = $separate_value;
				$field_options['blank']          = $blank_message;

				FrmField::update(
					$field->id,
					array(
						'name'          => $title,
						'field_order'   => $order,
						'options'       => $columns,
						'type'          => $row_type,
						'required'      => $likert_field->required,
						'form_id'       => $likert_field->form_id,
						'field_options' => $field_options,
					)
				);

				$row_ids[] = $field->id;

				unset( $field );

				continue;
			}//end if

			// Create row field.
			$form_id      = $likert_field->form_id;
			$field_values = FrmFieldsHelper::setup_new_vars( $row_type, $form_id );

			$field_values['name']                            = $title;
			$field_values['options']                         = $columns;
			$field_values['field_order']                     = $order;
			$field_values['required']                        = $likert_field->required;
			$field_values['field_options']['likert_id']      = $likert_field->id;
			$field_values['field_options']['separate_value'] = $separate_value;
			$field_values['field_options']['blank']          = $blank_message;

			$row_id = FrmField::create( $field_values );
			if ( $row_id ) {
				$row_ids[] = $row_id;
			}
		}//end foreach

		$field_options         = $likert_field->field_options;
		$field_options['rows'] = $row_ids;

		FrmField::update(
			$likert_field->id,
			array(
				'options'       => $columns,
				'field_options' => $field_options,
			)
		);
	}

	/**
	 * Removes row fields from form and add row_fields to the Likert fields.
	 *
	 * @param array $fields Array of fields.
	 * @return array
	 */
	public static function remove_row_fields_from_form( $fields ) {
		$row_field_types = array( 'radio', 'checkbox' );

		// Not using field ID as array key.
		if ( isset( $fields[0] ) ) {
			return self::remove_fields_from_array( $fields );
		}

		// Field ID as array key.
		foreach ( $fields as $id => $field ) {
			$field_type = is_object( $field ) ? $field->type : $field['type'];
			if ( ! in_array( $field_type, $row_field_types, true ) ) {
				// When using page break, we should keep row fields are converted to hidden.
				continue;
			}

			$likert_id = self::is_likert_row( $field );
			if ( ! $likert_id || empty( $fields[ $likert_id ] ) ) {
				// Skip if does not found the Likert field.
				continue;
			}

			if ( is_object( $field ) ) {
				if ( ! isset( $fields[ $likert_id ]->field_options['row_fields'] ) ) {
					$fields[ $likert_id ]->field_options['row_fields'] = array();
				}
				$fields[ $likert_id ]->field_options['row_fields'][ $id ] = $field;
			} else {
				if ( ! isset( $fields[ $likert_id ]['field_options']['row_fields'] ) ) {
					$fields[ $likert_id ]['field_options']['row_fields'] = array();
				}
				$fields[ $likert_id ]['field_options']['row_fields'][ $id ] = $field;
			}

			unset( $fields[ $id ] );
			unset( $field );
		}//end foreach

		return $fields;
	}

	/**
	 * Remove row fields when array key is not a field id.
	 *
	 * @param array $fields Form fields.
	 *
	 * @since 1.01
	 */
	private static function remove_fields_from_array( $fields ) {
		$row_field_types = array( 'radio', 'checkbox' );

		// An array with keys are the field IDs and values are index in the $fields.
		$mappings = array();
		foreach ( $fields as $index => $field ) {
			if ( is_object( $field ) ) {
				$mappings[ $field->id ] = $index;
			} else {
				$mappings[ $field['id'] ] = $index;
			}
		}

		foreach ( $fields as $index => $field ) {
			$field_type = is_object( $field ) ? $field->type : $field['type'];
			if ( ! in_array( $field_type, $row_field_types, true ) ) {
				// When using page break, we should keep row fields are converted to hidden.
				continue;
			}

			$likert_id = self::is_likert_row( $field );
			if ( ! $likert_id || ! isset( $mappings[ $likert_id ] ) ) {
				// Skip if does not found the Likert field.
				continue;
			}

			if ( is_object( $field ) ) {
				if ( ! isset( $fields[ $mappings[ $likert_id ] ]->field_options['row_fields'] ) ) {
					$fields[ $mappings[ $likert_id ] ]->field_options['row_fields'] = array();
				}
				$fields[ $mappings[ $likert_id ] ]->field_options['row_fields'][ $field->id ] = $field;
			} else {
				if ( ! isset( $fields[ $mappings[ $likert_id ] ]['field_options']['row_fields'] ) ) {
					$fields[ $mappings[ $likert_id ] ]['field_options']['row_fields'] = array();
				}
				$fields[ $mappings[ $likert_id ] ]['field_options']['row_fields'][ $field['id'] ] = $field;
			}

			unset( $fields[ $index ], $field );
		}//end foreach

		return $fields;
	}

	/**
	 * Changes the order of child fields, and remove the likert field to avoid duplication.
	 *
	 * @param array $fields Array of fields.
	 * @return array
	 */
	public static function change_field_order_no_likert( $fields ) {
		return self::change_field_order( $fields, false );
	}

	/**
	 * Changes the order of child fields, and include the likert field too.
	 *
	 * @param array $fields Array of fields.
	 * @return array
	 */
	public static function change_field_order_keep_likert( $fields ) {
		return self::change_field_order( $fields );
	}

	/**
	 * Changes the order of fields in some places we want to keep the row fields.
	 *
	 * @param array $fields      Array of fields.
	 * @param bool  $keep_parent Keep the likert field or not.
	 * @return array
	 */
	public static function change_field_order( $fields, $keep_parent = true ) {
		$return_fields = array();

		$likert_data  = self::get_likert_fields_and_rows_from_fields( $fields );
		if ( ! $likert_data ) {
			return $fields;
		}

		foreach ( $fields as $field ) {
			$likert_id = self::is_likert_row( $field );
			if ( $likert_id ) {
				if ( empty( $likert_data[ $likert_id ]['field'] ) ) {
					// The Likert field doesn't exist, treat as normal field.
					$return_fields[] = $field;
				}
				continue;
			}

			if ( ! self::is_likert_field( $field ) ) {
				$return_fields[] = $field;
				continue;
			}

			if ( $keep_parent ) {
				$return_fields[] = $field;
			}

			$return_fields = array_merge( $return_fields, $likert_data[ $field->id ]['rows'] );
			unset( $field );
		}//end foreach

		return $return_fields;
	}

	/**
	 * Gets the data of Likert fields and rows from the list of fields.
	 *
	 * @since 1.0.10
	 *
	 * @param object[] $fields Array of field objects.
	 * @return array           An array with key is the Likert ID, value is an array contains `field` and `rows`.
	 */
	private static function get_likert_fields_and_rows_from_fields( $fields ) {
		$likert_data  = array();
		$default_data = array(
			'field' => '',
			'rows'  => array(),
		);

		foreach ( $fields as $field ) {
			$likert_id = self::is_likert_row( $field );
			if ( $likert_id ) {
				if ( ! isset( $likert_data[ $likert_id ] ) ) {
					$likert_data[ $likert_id ] = $default_data;
				}

				$likert_data[ $likert_id ]['rows'][ $field->id ] = $field;
				continue;
			}

			if ( self::is_likert_field( $field ) ) {
				if ( ! isset( $likert_data[ $field->id ] ) ) {
					$likert_data[ $field->id ] = $default_data;
				}

				$likert_data[ $field->id ]['field'] = $field;
			}
		}

		return $likert_data;
	}

	/**
	 * Shows the likert field heading.
	 *
	 * @param array|object|int $field Field ID or field data.
	 */
	public static function show_heading( $field ) {
		$columns = self::get_columns( $field );
		?>
		<div class="frm_likert__heading form-field">
			<div class="frm_primary_label"></div>

			<div class="frm_opt_container">
				<?php foreach ( $columns as $column ) : ?>
					<div class="frm_likert__column">
						<?php echo esc_html( $column['label'] ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Modifies Likert validation messages.
	 *
	 * @param array $errors Errors added so far.
	 * @param array $values Values submitted in the form.
	 * @param array $args   Includes posted_fields.
	 *
	 * @since 1.0
	 * @since 1.0.05 This method just remove error messages of rows in hidden Likert.
	 */
	public static function validate_entry( $errors, $values, $args ) {
		foreach ( self::$error_keys as $row_key => $data ) {
			if ( ! isset( $errors[ $row_key ] ) ) {
				unset( self::$error_keys[ $row_key ] );
				continue;
			}

			if ( $data ) {
				$errors[ $data['key'] ] = $data['msg'];
			}

			unset( $errors[ $row_key ] );
		}

		return $errors;
	}

	/**
	 * Validates likert field.
	 *
	 * @since 1.0.05
	 *
	 * @param array  $errors Error messages.
	 * @param object $field Field data.
	 * @param mixed  $value Value to validate.
	 * @param array  $args  The arguments.
	 * @return array
	 */
	public static function validate_field_entry( $errors, $field, $value, $args ) {
		$row_ids = self::get_row_ids( $field );
		if ( ! $row_ids ) {
			return $errors;
		}

		$suffix = '';
		// In repeater or embedded form.
		if ( ! empty( $args['parent_field_id'] ) && isset( $args['key_pointer'] ) ) {
			$suffix = '-' . $args['parent_field_id'] . '-' . $args['key_pointer'];
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$is_visible = FrmProFieldsHelper::is_field_visible_to_user( $field ) && ! FrmProFieldsHelper::is_field_hidden( $field, wp_unslash( $_POST ) );

		// Track the error keys.
		$likert_error_key = 'field' . $field->id . $suffix;
		foreach ( $row_ids as $row_id ) {
			$error_key = 'field' . $row_id . $suffix;

			if ( ! $is_visible ) {
				// This error will be removed later.
				self::$error_keys[ $error_key ] = false;
			} else {
				// This error will be replaced.
				self::$error_keys[ $error_key ] = array(
					'key' => $likert_error_key,
					'msg' => FrmFieldsHelper::get_error_msg( $field, 'blank' ),
				);
			}
		}

		return $errors;
	}

	/**
	 * Modifies row field classes.
	 *
	 * @since 1.1.1
	 *
	 * @param string $classes Field classes.
	 * @param object $field   Field ID.
	 * @param array  $args    Contains `field_id` which is the error field key without `field`.
	 * @return string
	 */
	public static function row_field_classes( $classes, $field, $args ) {
		foreach ( self::$error_keys as $key => $data ) {
			if ( $data && 'field' . $args['field_id'] === $key ) {
				// If there was error of this field before, add error class.
				$classes .= ' frm_blank_field';
			}
		}
		return $classes;
	}

	/**
	 * Adds likert to the table graph types.
	 *
	 * @param array $types Table graph types.
	 * @return array
	 */
	public static function table_graph_types( $types ) {
		$types[] = 'likert';
		return $types;
	}

	/**
	 * Modifies Google chart options.
	 *
	 * @param array $options Chart options.
	 * @param array $args    Arguments. Contains `atts` and `type`.
	 * @return array
	 */
	public static function google_chart_options( $options, $args ) {
		if ( 'table' !== $args['type'] ) {
			return $options;
		}

		if ( empty( $args['atts']['fields'] ) ) {
			return $options;
		}

		$field = reset( $args['atts']['fields'] );
		if ( 'likert' !== $field->type ) {
			return $options;
		}

		$options['allowHtml'] = true;
		return $options;
	}

	/**
	 * Modifies graph data.
	 *
	 * @param array $data Graph data.
	 * @param array $atts Graph atts.
	 * @return array
	 */
	public static function graph_data( $data, $atts ) {
		if ( ! $data || empty( $atts['fields'] ) ) {
			return $data;
		}

		$field = reset( $atts['fields'] );
		if ( 'likert' !== $field->type ) {
			return $data;
		}

		// The first cell in the first row is empty.
		$new_data   = array( array( '' ) );

		$row_fields = self::get_row_fields( $field );
		$total      = self::get_total_from_graph_data( $data, $atts );

		$columns   = self::get_columns( $field );
		if ( ! $field->required ) {
			$columns[] = array(
				'label' => esc_html__( 'None', 'formidable-surveys' ),
				'value' => '',
			);
		}

		$counts  = array();

		// Build the first row and the count array.
		foreach ( $columns as $column ) {
			$new_data[0][]              = $column['label'];
			$counts[ $column['value'] ] = 0;
		}

		foreach ( $row_fields as $row_field ) {
			$new_data[] = self::build_graph_row_data( compact( 'row_field', 'counts', 'total', 'atts' ) );
			unset( $row_field );
		}

		return $new_data;
	}

	/**
	 * Gets total entries from the graph data.
	 *
	 * @param array $data Graph data.
	 * @param array $atts Graph atts.
	 * @return int
	 */
	protected static function get_total_from_graph_data( $data, $atts ) {
		switch ( $atts['type'] ) {
			case 'histogram':
				$total = count( $data ) - 1;
				break;

			default:
				$total = $data[1][1];
		}

		return $total;
	}

	/**
	 * Builds data for graph row.
	 *
	 * @param array $args {
	 *     The arguments.
	 *
	 *     @type array|object $row_field Row field data.
	 *     @type int          $total     Total of submission.
	 *     @type array        $counts    Array with keys are column id and values are theirs count.
	 *     @type array        $atts      Graph atts.
	 * }
	 * @return array
	 */
	protected static function build_graph_row_data( $args ) {
		$counts     = $args['counts'];
		$total      = $args['total'];
		$row_field  = $args['row_field'];
		$graph_atts = $args['atts'];
		$not_chosen = $total;

		$values = FrmProGraphsController::get_meta_values_for_single_field( $row_field, $graph_atts );
		foreach ( $values as $value ) {
			if ( ! isset( $counts[ $value ] ) ) {
				continue;
			}

			$counts[ $value ]++;
		}

		$row_data = array( $row_field->name );
		foreach ( $counts as $count ) {
			$row_data[] = self::get_graph_cell_data( $count, $total, $args );

			$not_chosen = $not_chosen - $count;
		}

		// Some rows are not chosen when submitting form.
		if ( $not_chosen > 0 ) {
			$row_data[ count( $row_data ) - 1 ] = self::get_graph_cell_data( $not_chosen, $total, $args );
		}

		return $row_data;
	}

	/**
	 * Gets graph cell data.
	 *
	 * @param int   $value The value.
	 * @param int   $total The total.
	 * @param array $args  The arguments of {@see LikertController::build_graph_row_data()}.
	 * @return array
	 */
	protected static function get_graph_cell_data( $value, $total, $args ) {
		$percentage = self::format_graph_percentage_value( $value / $total * 100 ) . '%';
		$cell = array(
			'v' => $value,
			'f' => $value . ' (' . $percentage . ')',
		);

		if ( is_admin() ) {
			$cell['f'] = '<strong>' . $value . '</strong><br>' . $percentage;
		}
		return $cell;
	}

	/**
	 * Formats percentage value to display in graph.
	 *
	 * @param float $value The percentage value.
	 * @return string
	 */
	protected static function format_graph_percentage_value( $value ) {
		return round( $value, 1 );
	}

	/**
	 * AJAX loads likert field display. Used for live updating in the backend.
	 */
	public static function load_field_display() {
		check_ajax_referer( 'frm_surveys_ajax' );

		if ( empty( $_POST['field_id'] ) ) {
			wp_send_json_error( __( 'Field ID is empty', 'formidable-surveys' ) );
		}

		$field = FrmField::getOne( intval( $_POST['field_id'] ) );
		if ( ! $field ) {
			wp_send_json_error( __( 'Field not exist', 'formidable-surveys' ) );
		}

		$rows            = FrmAppHelper::get_post_param( 'rows', array() );
		$columns         = FrmAppHelper::get_post_param( 'columns', array() );
		$multi_selection = FrmAppHelper::get_post_param( 'multi_selection', '', 'intval' );
		$inline_column   = FrmAppHelper::get_post_param( 'inline_column', '', 'intval' );

		// Create fake fields.
		$row_fields = array();
		$row_ids    = array();
		foreach ( $rows as $index => $row ) {
			if ( is_array( $row ) ) {
				$row_id   = $row['id'];
				$row_key  = $row['key'];
				$row_name = $row['name'];
			} else {
				$row_id   = 'fid-' . $index;
				$row_key  = 'fkey-' . mt_rand( 100, 999 );
				$row_name = $row;
			}
			$row_field = array(
				'type'          => $multi_selection ? 'checkbox' : 'radio',
				'name'          => $row_name,
				'options'       => $columns,
				'id'            => $row_id,
				'field_key'     => $row_key,
				'description'   => '',
				'default_value' => '',
				'field_order'   => $field->field_order,
				'required'      => $field->required,
				'field_options' => array(),
				'form_id'       => $field->form_id,
			);

			$row_fields[] = (object) $row_field;
			$row_ids[]    = $row_id;
		}//end foreach

		// Add fake data to field.
		$field->options                          = $columns;
		$field->field_options['row_fields']      = $row_fields;
		$field->field_options['rows']            = $row_ids;
		$field->field_options['multi_selection'] = $multi_selection;
		$field->field_options['inline_column']   = $inline_column;

		$field_obj = FrmFieldFactory::get_field_object( $field );

		unset( $field );

		ob_start();
		$field_obj->show_on_form_builder();
		wp_send_json_success( ob_get_clean() );
	}

	/**
	 * Excludes fields from the conditional logic options.
	 *
	 * @param bool  $present Is `true` if field is present in the conditional logic options.
	 * @param array $args    The arguments.
	 * @return bool
	 */
	public static function exclude_from_logic_options( $present, array $args ) {
		$logic_field = $args['logic_field'];
		if ( self::is_likert_field( $logic_field ) ) {
			return false;
		}

		$current_field = $args['current_field'];
		if ( self::is_likert_field( $current_field ) && self::is_row_in_likert( $logic_field, $current_field['id'] ) ) {
			// Do not show row fields from Likert conditional logic options.
			return false;
		}

		return $present;
	}

	/**
	 * Gets CSS variables.
	 *
	 * @param array $field Field data.
	 * @return string
	 */
	public static function get_css_variables( $field ) {
		$variables = array();

		$variables['--columns-count'] = count( self::get_columns( $field ) );

		/**
		 * Allows modifying CSS variable of likert field.
		 *
		 * @param array $variables Array of CSS variables.
		 * @param array $field Field data.
		 */
		$variables = apply_filters( 'frm_surveys_likert_css_variables', $variables, $field );

		$result = '';
		foreach ( $variables as $key => $value ) {
			$result .= sprintf( '%s:%s;', esc_attr( $key ), esc_attr( $value ) );
		}

		return $result;
	}

	/**
	 * Gets the min_width value, used for responsive.
	 *
	 * @param object|array $field Field data..
	 * @return float
	 */
	public static function get_min_width( $field ) {
		$columns_count = count( self::get_columns( $field ) );
		$form_id       = is_object( $field ) ? $field->form_id : $field['form_id'];
		$style         = FrmStylesController::get_form_style( $form_id );
		$label_width   = str_replace( 'px', '', $style->post_content['width'] );

		if ( ! is_numeric( $label_width ) ) {
			$label_width = 150;
		}

		// Each 60px for each column, 20px for gap.
		$min_width     = $label_width + 80 * $columns_count;

		// In case something other than px is used, make sure the field stays likert in large spaces.
		$min_width = min( $min_width, 800 );

		/**
		 * Allows changing the responsive breakpoint of Likert field.
		 *
		 * @since 1.0.06
		 *
		 * @param float $min_width The minimum width (in px) to Likert field displays as desktop version.
		 * @param array $args {
		 *     The custom arguments.
		 *
		 *     @type object|array $field The field object.
		 * }
		 */
		return apply_filters( 'frm_surveys_likert_responsive_breakpoint', $min_width, compact( 'field' ) );
	}

	/**
	 * Creates default rows and columns.
	 *
	 * @param array $field Field data before creating.
	 * @return array
	 */
	public static function create_default_rows_and_columns( $field ) {
		if ( 'likert' !== $field['type'] ) {
			return $field;
		}

		$bulk_options = array();
		FrmFieldsHelper::get_bulk_prefilled_opts( $bulk_options );
		$columns = $bulk_options[ __( 'Agreement', 'formidable' ) ]; // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

		unset( $bulk_options );

		// Create default row.
		$form_id    = $field['form_id'];
		$row_values = FrmFieldsHelper::setup_new_vars( 'radio', $form_id );

		$row_values['name']    = __( 'Question', 'formidable-surveys' ) . ' 1';
		$row_values['options'] = $columns;

		// Make this not be empty, it won't show in the form in case likert field creation failed.
		$row_values['field_options']['likert_id'] = -1;

		$row_id = FrmField::create( $row_values );

		$row_values['name'] = __( 'Question', 'formidable-surveys' ) . ' 2';
		$row_id2 = FrmField::create( $row_values );

		if ( $row_id ) {
			$field['field_options']['rows'] = array( $row_id, $row_id2 );
		}

		// Create default columns.
		$field['options'] = $columns;

		return $field;
	}

	/**
	 * Updates default rows after creating likert field.
	 *
	 * @param array $field   Field data.
	 * @param int   $form_id Form ID.
	 */
	public static function update_default_rows_after_creating( $field, $form_id ) {
		if ( 'likert' !== $field['type'] ) {
			return;
		}

		$row_ids = FrmField::get_option( $field, 'rows' );
		foreach ( $row_ids as $row_id ) {
			$row_field = FrmField::getOne( $row_id );
			$field_options = $row_field->field_options;
			$field_options['likert_id'] = $field['id'];

			FrmField::update( $row_id, compact( 'field_options' ) );
		}
	}

	/**
	 * Updates likert_id of Likert rows after duplicating form.
	 *
	 * @param int $form_id New form ID.
	 */
	public static function after_duplicate_form( $form_id ) {
		global $frm_duplicate_ids;

		$query = array(
			'field_options LIKE' => 's:9:"likert_id";',
			'form_id'            => $form_id,
			'type'               => array( 'radio', 'checkbox' ),
		);

		// Keys are likert IDs and values are array of row IDs.
		$likert_row_map = array();
		$results        = FrmDb::get_results( 'frm_fields', $query, 'id, field_options' );

		// Update likert_id or rows.
		foreach ( $results as $result ) {
			FrmProAppHelper::unserialize_or_decode( $result->field_options );

			if ( ! isset( $result->field_options['likert_id'] ) ) {
				continue;
			}

			if ( isset( $frm_duplicate_ids[ $result->field_options['likert_id'] ] ) ) {
				$new_likert_id = $frm_duplicate_ids[ $result->field_options['likert_id'] ];

				$result->field_options['likert_id'] = $new_likert_id;

				if ( ! isset( $likert_row_map[ $new_likert_id ] ) ) {
					$likert_row_map[ $new_likert_id ] = array();
				}
				$likert_row_map[ $new_likert_id ][] = $result->id;

				FrmField::update( $result->id, array( 'field_options' => $result->field_options ) );
			}

			unset( $result );
		}//end foreach

		// Update rows of likert.
		foreach ( $likert_row_map as $likert_id => $row_ids ) {
			$field_options = FrmField::get_type( $likert_id, 'field_options' );
			FrmProAppHelper::unserialize_or_decode( $field_options );

			$field_options['rows'] = $row_ids;

			FrmField::update( $likert_id, compact( 'field_options' ) );
		}
	}

	/**
	 * Prints Likert responsive js.
	 */
	public static function print_responsive_js() {
		global $frm_vars;

		if ( ! empty( $frm_vars['printed_likert_responsive_js'] ) ) {
			return;
		}
		$frm_vars['printed_likert_responsive_js'] = true;

		include AppHelper::plugin_path() . '/classes/views/fields/frontend/likert-js.php';
	}

	/**
	 * Updates likert rows order to fix the pagination issue.
	 *
	 * @param object[] $fields The fields get from database.
	 * @return object[]
	 */
	public static function update_likert_rows_order( $fields ) {
		foreach ( $fields as $field ) {
			if ( 'likert' !== $field->type ) {
				continue;
			}

			$row_ids = self::get_row_ids( $field );

			foreach ( $fields as $index => $field2 ) {
				if ( in_array( $field2->id, $row_ids ) ) {
					$fields[ $index ]->field_order = $field->field_order;
				}

				unset( $field2 );
			}

			unset( $field );
		}

		return $fields;
	}

	/**
	 * Creates rows and update likert field after duplicating.
	 *
	 * @param array $args The arguments. Includes `field_id`, `values`, `copy_field`, `form_id`.
	 */
	public static function after_duplicate_field( $args ) {
		if ( 'likert' !== $args['values']['type'] ) {
			return;
		}

		// Save to global for use on later hook.
		global $frm_vars;
		$frm_vars['likert_id'] = $args['field_id'];

		$field_id    = $args['field_id'];
		$form_id     = $args['form_id'];
		$row_ids     = self::get_row_ids( $args['values'] );
		$new_row_ids = array();

		foreach ( $row_ids as $row_id ) {
			// Duplicate row field.
			$new_row_id = self::duplicate_row( compact( 'row_id', 'field_id', 'form_id' ) );
			if ( $new_row_id ) {
				$new_row_ids[] = $new_row_id;
			}
		}

		unset( $frm_vars['likert_id'] );

		$values                          = $args['values'];
		$values['field_options']['rows'] = $new_row_ids;

		FrmField::update( $field_id, array( 'field_options' => $values['field_options'] ) );
	}

	/**
	 * Duplicates a row.
	 *
	 * @param array $args Includes:
	 *          int $row_id   Row ID.
	 *          int $field_id Likert ID.
	 *          int $form_id  Form ID.
	 *
	 * @return int|false     New row data.
	 */
	public static function duplicate_row( $args ) {
		if ( is_callable( 'FrmField::duplicate_single_field' ) ) {
			add_filter( 'frm_prepare_single_field_for_duplication', array( __CLASS__, 'add_likert_id_to_row' ) );
			$new_field = FrmField::duplicate_single_field( $args['row_id'], $args['form_id'] );
			remove_filter( 'frm_prepare_single_field_for_duplication', array( __CLASS__, 'add_likert_id_to_row' ) );
			return is_array( $new_field ) ? $new_field['field_id'] : false;
		}

		// FrmField::duplicate_single_field() was added in FF 5.0.05.
		// The code below this line can be removed.

		$row_id     = $args['row_id'];
		$copy_field = FrmField::getOne( $row_id );
		if ( ! $copy_field ) {
			return false;
		}

		do_action( 'frm_duplicate_field', $copy_field, $copy_field->form_id );
		do_action( 'frm_duplicate_field_' . $copy_field->type, $copy_field, $copy_field->form_id );

		$values = array(
			'id' => $copy_field->id,
		);
		FrmFieldsHelper::fill_field( $values, $copy_field, $copy_field->form_id );
		$values['field_options']['likert_id'] = $args['field_id'];

		$values = apply_filters( 'frm_prepare_single_field_for_duplication', $values );

		return FrmField::create( $values );
	}

	/**
	 * This is triggered when a single likert field is duplicated.
	 * Triggered by frm_prepare_single_field_for_duplication.
	 *
	 * @param array $values The field values to save in new field.
	 *
	 * @since 1.0
	 */
	public static function add_likert_id_to_row( $values ) {
		global $frm_vars;
		if ( ! empty( $frm_vars['likert_id'] ) ) {
			$values['field_options']['likert_id'] = $frm_vars['likert_id'];
		}
		return $values;
	}

	/**
	 * Removes orphaned likert rows.
	 *
	 * @since 1.0.02
	 *
	 * @param array $fields List of fields.
	 * @return array
	 */
	public static function remove_orphaned_rows( $fields ) {
		$field_ids = wp_list_pluck( $fields, 'id' );

		foreach ( $fields as $index => $field ) {
			$likert_id = self::is_likert_row( $field );

			// Is a likert row but likert does not exist.
			if ( $likert_id && ! in_array( $likert_id, $field_ids ) ) {
				unset( $fields[ $index ] );
			}

			unset( $field );
		}

		return $fields;
	}

	/**
	 * Shows row in frontend.
	 *
	 * @since 1.0.05
	 *
	 * @param object|array $field Row field data.
	 * @param array        $args  The arguments.
	 */
	public static function show_frontend_row( $field, $args ) {
		$field = (array) $field;

		// Fix error when update an entry with empty likert.
		if ( ! isset( $field['label'] ) ) {
			$field['label'] = '';
		}
		if ( ! isset( $field['value'] ) ) {
			$field['value'] = '';
		}

		$field_obj = FrmFieldFactory::get_field_type( $field['type'], $field );

		self::process_frontend_row_args( $args, $field );

		$field_obj->show_field( $args );
	}

	/**
	 * Processes args before showing frontend row.
	 *
	 * @since 1.0.05
	 *
	 * @param array $args  The args.
	 * @param array $field Row field.
	 */
	protected static function process_frontend_row_args( &$args, $field ) {
		if ( ! empty( $args['section_id'] ) && isset( $args['field_plus_id'] ) ) {
			// If inside a Repeater, change some args.
			$args['field_id']   = $field['id'] . '-' . $args['section_id'] . $args['field_plus_id'];
			$args['field_name'] = preg_replace( '/(\[[0-9]+\])$/', '[' . $field['id'] . ']', $args['field_name'] );
			$args['html_id']    = str_replace( $args['likert_field']['field_key'], $field['field_key'], $args['html_id'] );
		} else {
			// If not inside a Repeater, unset some args, they will be generated automatically when show field.
			unset( $args['field_name'], $args['field_id'], $args['html_id'] );
		}
	}

	/**
	 * Excludes Likert fields from CSV export headings.
	 *
	 * @since 1.0.06
	 *
	 * @param array $field_headings CSV headings of the given field.
	 * @param array $args           Contains `field`, `context`, and `meta`.
	 * @return array
	 */
	public static function exclude_likert_from_csv_headings( $field_headings, $args ) {
		if ( empty( $args['field'] ) ) {
			return $field_headings;
		}

		if ( self::is_likert_field( $args['field'] ) && isset( $field_headings[ $args['field']->id ] ) ) {
			unset( $field_headings[ $args['field']->id ] );
		}

		return $field_headings;
	}

	/**
	 * Sets fixed meta values for Likert fields in CSV import.
	 *
	 * @since 1.0.06
	 *
	 * @param array $fixed_meta_values Fixed meta values.
	 * @param array $args              Contains `form_id`.
	 * @return array
	 */
	public static function set_csv_import_fixed_meta_values( $fixed_meta_values, $args ) {
		if ( empty( $args['form_id'] ) ) {
			return $fixed_meta_values;
		}

		$likert_ids = FrmDb::get_col(
			'frm_fields',
			array(
				'type'    => 'likert',
				'form_id' => $args['form_id'],
			)
		);

		foreach ( $likert_ids as $likert_id ) {
			$fixed_meta_values[ $likert_id ] = 1;
		}

		self::maybe_add_fixed_values_for_child_likert( $fixed_meta_values, $args );

		return $fixed_meta_values;
	}

	/**
	 * Maybe add fixed meta values for Likert inside Embed Form or Repeater.
	 *
	 * @since 1.0.06
	 *
	 * @param array $fixed_meta_values Fixed meta values.
	 * @param array $args              Contains `form_id`.
	 */
	private static function maybe_add_fixed_values_for_child_likert( &$fixed_meta_values, $args ) {
		$parents = FrmDb::get_results(
			'frm_fields',
			array(
				'type'    => array( 'form', 'divider' ),
				'form_id' => $args['form_id'],
			),
			'id,field_options'
		);

		if ( ! $parents ) {
			return;
		}

		/*
		 * Build a mapping array with key as child form ID and value as the parent field ID.
		 */
		$child_form_mapping = array();
		foreach ( $parents as $parent ) {
			$parent->field_options = unserialize( $parent->field_options );
			if ( empty( $parent->field_options['form_select'] ) ) {
				continue;
			}

			$child_form_mapping[ $parent->field_options['form_select'] ] = $parent->id;
			unset( $parent );
		}

		$likerts = FrmDb::get_results(
			'frm_fields',
			array(
				'type'    => 'likert',
				'form_id' => array_keys( $child_form_mapping ),
			),
			'id,form_id'
		);

		if ( ! $likerts ) {
			return;
		}

		foreach ( $likerts as $likert ) {
			$fixed_meta_values[ $likert->id . '_' . $child_form_mapping[ $likert->form_id ] ] = 1;
		}
	}

	/**
	 * Prepares fields for CSV export and import.
	 *
	 * @since 1.0.06
	 *
	 * @param object[] $fields Array of field object.
	 * @return object[]
	 */
	public static function prepare_fields_for_csv_export_import( $fields ) {
		$has_likert = false;

		$mapping = array();
		foreach ( $fields as $field ) {
			$mapping[ $field->id ] = $field;
			if ( self::is_likert_field( $field ) ) {
				$has_likert = true;
			}
			unset( $field );
		}

		if ( ! $has_likert ) {
			return $fields;
		}

		$fields = self::change_field_order( $fields, false );

		foreach ( $fields as $field ) {
			$likert_id = self::is_likert_row( $field );
			if ( $likert_id && isset( $mapping[ $likert_id ] ) ) {
				$field->name = $mapping[ $likert_id ]->name . ' - ' . $field->name;

				if ( ! empty( $mapping[ $likert_id ]->field_options['in_section'] ) ) {
					$field->field_options['in_section'] = $mapping[ $likert_id ]->field_options['in_section'];
				}
			}
		}

		return $fields;
	}

	/**
	 * Prepares field for import.
	 *
	 * @since 1.0.06
	 *
	 * @param object $field Field object.
	 * @return object
	 */
	public static function prepare_field_for_import( $field ) {
		$likert_id = self::is_likert_row( $field );
		if ( ! $likert_id ) {
			return $field;
		}

		$likert_field = \FrmProXMLHelper::get_field( $likert_id );
		if ( ! empty( $likert_field->field_options['in_section'] ) ) {
			$field->field_options['in_section'] = $likert_field->field_options['in_section'];
		}

		return $field;
	}
}
