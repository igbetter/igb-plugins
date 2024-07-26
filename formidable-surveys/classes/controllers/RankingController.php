<?php
/**
 * Ranking Field Controller
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\controllers;

use FrmSurveys\helpers\AppHelper;
use FrmSurveys\helpers\RankingGraphData;
use FrmField;
use FrmAppHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Ranking Field Controller.
 *
 * @since 1.1
 */
class RankingController {

	/**
	 * Init the Ranking Field frontend scripts.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public static function init_frontend_scripts() {
		$ranking_field_dependencies = array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-sortable',
			'formidable',
		);
		wp_enqueue_script( 'frm-surveys-ranking', AppHelper::plugin_url() . '/js/frm-surveys-ranking.js', $ranking_field_dependencies, AppHelper::$plug_version, true );
	}

	/**
	 * Init the Ranking Field.
	 *
	 * @since 1.1
	 *
	 * @param string $class The Class name.
	 * @param string $field_type The name of the field type.
	 * @return string
	 */
	public static function get_field_type_class( $class, $field_type ) {
		if ( 'ranking' === $field_type ) {
			return '\FrmSurveys\models\fields\Ranking';
		}
		return $class;
	}

	/**
	 * Enable Ranking Field in the builder.
	 *
	 * @since 1.1
	 *
	 * @param array $fields The full field list.
	 * @return array
	 */
	public static function add_to_available_fields( $fields ) {
		if ( ! isset( $fields['ranking'] ) ) {
			return $fields;
		}

		unset( $fields['ranking']['addon'] );

		// Remove the 'frm_show_upgrade' class.
		$fields['ranking']['icon'] = 'frm_icon_font frm_chart_bar_icon';

		return $fields;
	}

	/**
	 * Make "Images" tab available withing addon. Remove the "Buttons" tab from Ranking Field Options
	 *
	 * @since 1.1
	 *
	 * @param array $options The tabs option.
	 * @return array
	 */
	public static function change_field_display_format_options( $options ) {
		// Make the "Images" tab available in Ranking Options.
		unset( $options['1']['addon'] );
		// Remove the "Buttons" tab from Ranking Options.
		unset( $options['buttons'] );

		return $options;
	}

	/**
	 * Add extra classname to parent div container
	 *
	 * @since 1.1
	 *
	 * @param string $classnames The parent container's available classnames.
	 * @param array  $field The field object.
	 * @return string
	 */
	public static function add_classname_to_parent_container( $classnames, $field ) {
		if ( 'ranking' === $field['type'] ) {
			$classnames .= ' frm-ranking-field-container';
		}
		return $classnames;
	}

	/**
	 * Return the folder of style views.
	 *
	 * @since 1.1
	 *
	 * @return string
	 */
	private static function view_folder() {
		return AppHelper::plugin_path() . '/classes/views/styles';
	}

	/**
	 * Init the Ranking Filed graph data.
	 * Called via add_filter "frm_graph_data" in HooksController.php
	 *
	 * @since 1.1
	 *
	 * @param array $data The default data.
	 * @param array $atts The graph shortcode atts.
	 * @return array
	 */
	public static function graph_data( $data, $atts ) {

		if ( ! isset( $atts['fields'] ) || '' === $atts['fields'] ) {
			return $data;
		}

		$field = reset( $atts['fields'] );
		if ( 'ranking' !== FrmField::get_field_type( $field ) ) {
			return $data;
		}

		$is_table_chart     = 'table' === $atts['type'];
		$ranking_graph_data = new RankingGraphData( $data, $field, $is_table_chart );

		return $ranking_graph_data->get_data();
	}

	/**
	 * Build an array that will contain the active/selected options first and then will continue with available options.
	 *
	 * @since 1.1
	 *
	 * @param array $field The field object.
	 * @return array
	 */
	public static function build_active_options( $field ) {

		$options = $field['options'];
		if ( ! self::has_active_values( $field ) ) {
			return $options;
		}

		$data = self::prepare_active_values_data( $options, self::prepare_values( $field['value'] ) );
		return array_merge( $data['values'], $data['diff'] );
	}

	/**
	 * Determine if there are active field values.
	 *
	 * @since 1.1
	 *
	 * @param array $field The field object.
	 * @return bool
	 */
	private static function has_active_values( $field ) {
		// When the form is saved, the "order" array is excluded from the "value" array.
		// If the "order" exists in the "value" array, it indicates redirection due to an empty error validation guard. In such cases, there won't be active values.
		if ( empty( $field['value'] ) || ( isset( $field['value']['order'] ) ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Transform the values array in case if options are a multidimensional array with each item containing label, image & value keys.
	 *
	 * @since 1.1
	 *
	 * @param array $options The options array. It might be a array or a multidimensional array.
	 * @param array $active_values The field active values array.
	 * @return array
	 */
	private static function prepare_active_values_data( $options, $active_values ) {
		$data = array();
		// Array containing options absent in the $active_values array.
		$diff_data = array();

		if ( isset( $active_values['order'] ) ) {
			$active_values = array();
		}

		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				return array(
					'values' => $active_values,
					'diff'   => array_diff( $options, $active_values ),
				);
			};

			$has_value_key = array_search( self::prepare_values( $option['value'] ), $active_values, true );
			if ( false !== $has_value_key ) {
				$data[ $has_value_key ] = $option;
				continue;
			};

			$diff_data[] = $option;
		}
		ksort( $data );
		return array(
			'values' => $data,
			'diff'   => $diff_data,
		);
	}

	/**
	 * Get the field option value.
	 *
	 * @since 1.1
	 *
	 * @param mixed $option The field option value(string) or field option data (array).
	 * @return string
	 */
	private static function get_option_value( $option ) {
		return is_array( $option ) ? $option['value'] : $option;
	}

	/**
	 * Prepare values data.
	 *
	 * @since 1.1
	 *
	 * @param string|array $values The options value which may be an array or string.
	 * @return array
	 */
	private static function prepare_values( $values ) {
		if ( ! is_array( $values ) && ! empty( $values ) ) {
			return array_map( 'trim', explode( ',', $values ) );
		}
		return $values;
	}

	/**
	 * Check if current option is active or not.
	 *
	 * @since 1.1
	 * @param array  $field The field object.
	 * @param string $option The option name.
	 *
	 * @return bool
	 */
	public static function is_option_active( $field, $option ) {
		if ( ! self::has_active_values( $field ) ) {
			return false;
		}

		return in_array( self::get_option_value( $option ), self::prepare_values( $field['value'] ), true );
	}

	/**
	 * Get the active position of an option.
	 *
	 * @since 1.1
	 * @param array  $field The field object.
	 * @param string $option The option name.
	 *
	 * @return int|null
	 */
	public static function get_active_position( $field, $option ) {
		if ( ! self::is_option_active( $field, $option ) ) {
			return null;
		}
		return (int) array_search( self::get_option_value( $option ), self::prepare_values( $field['value'] ), true );
	}

	/**
	 * Flag this ranking fields support images in options.
	 *
	 * @param bool  $supports_image_options True if the field type supports image options.
	 * @param array $field                  The target field data.
	 * @return bool
	 */
	public static function field_type_support_image_options( $supports_image_options, $field ) {
		if ( 'ranking' === FrmField::get_field_type( $field ) ) {
			$supports_image_options = true;
		}
		return $supports_image_options;
	}

	/**
	 * Include tables for ranking field types on the reports page.
	 *
	 * @param array $types The field types that do display a table on the reports page.
	 * @return array
	 */
	public static function reports_page_table_types( $types ) {
		$types[] = 'ranking';
		return $types;
	}

	/**
	 * Exclude ranking fields from the conditional logic of other fields.
	 *
	 * @since 1.1
	 *
	 * @param bool  $present Is `true` if field is present in the conditional logic options.
	 * @param array $args    The arguments.
	 * @return bool
	 */
	public static function exclude_from_logic_options( $present, array $args ) {
		if ( 'ranking' === FrmField::get_field_type( $args['logic_field'] ) ) {
			$present = false;
		}
		return $present;
	}

	/**
	 * Get drag SVG icon. For backend it's using icons.svg file. For frontend a separate SVG icon is used.
	 *
	 * @since 1.1
	 *
	 * @return string
	 */
	public static function get_drag_icon() {
		if ( FrmAppHelper::is_form_builder_page() ) {
			return FrmAppHelper::icon_by_class( 'frmfont frm_drag_icon frm-drag', array( 'echo' => false ) );
		}
		$svg = file_get_contents( AppHelper::plugin_path() . '/images/frm_drag_icon.svg' );
		if ( false === $svg ) {
			return '';
		}
		return preg_replace( '/(<svg[^>]+)(>)/i', '$1 class="frmsvg frm_drag_icon frm-drag " $2', $svg );
	}

	/**
	 * Enable the Bulk Edit Options for Ranking Field.
	 *
	 * @since 1.1
	 *
	 * @param array $field_types The field types eligible for Edit Bulk Options.
	 * @return array
	 */
	public static function enable_bulk_edit( $field_types ) {
		$field_types[] = 'ranking';
		return $field_types;
	}
}
