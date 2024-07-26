<?php
/**
 * NPS field controller
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\controllers;

use FrmProStatisticsController;
use FrmSurveys\helpers\AppHelper;
use FrmField;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * NPS field routing.
 *
 * @since 1.0
 */
class NPSController {

	/**
	 * Init the NPS field code.
	 *
	 * @param string $class The Class name.
	 * @param string $field_type The name of the field type.
	 *
	 * @since 1.0
	 */
	public static function get_field_type_class( $class, $field_type ) {
		if ( 'nps' === $field_type ) {
			return '\FrmSurveys\models\fields\NPS';
		}
		return $class;
	}

	/**
	 * Enable NPS fields in the builder.
	 *
	 * @param array $fields The full field list.
	 *
	 * @since 1.0
	 */
	public static function add_to_available_fields( $fields ) {
		if ( ! isset( $fields['nps'] ) ) {
			return $fields;
		}

		unset( $fields['nps']['addon'] );

		// Remove the 'frm_show_upgrade' class.
		$fields['nps']['icon'] = 'frm_icon_font frm_nps';

		return $fields;
	}

	/**
	 * Adjust the stats value returned for NPS fields.
	 *
	 * @param int|string $stat The value to show.
	 * @param array      $atts The shortcode paramters.
	 *
	 * @since 1.0
	 */
	public static function get_stat_from_meta_values( $stat, $atts ) {
		if ( ! in_array( $atts['type'], array( 'nps', 'detractors', 'passives', 'promoters' ), true ) ) {
			return $stat;
		}

		$nps_data = self::calculate_nps_data( $atts['meta_values'] );
		return $nps_data[ $atts['type'] ];
	}

	/**
	 * Calculates the NPS data.
	 *
	 * @param array $meta_values Array of meta values.
	 * @return array Array of `detractors`, `passives`, `promoters` and `nps`.
	 */
	public static function calculate_nps_data( $meta_values ) {
		$result = array(
			'detractors' => 0,
			'passives'   => 0,
			'promoters'  => 0,
			'nps'        => 0,
		);

		if ( ! $meta_values || ! is_array( $meta_values ) ) {
			return $result;
		}

		$total = count( $meta_values );

		foreach ( $meta_values as $meta_value ) {
			if ( $meta_value >= 9 ) {
				$result['promoters']++;
				continue;
			}

			if ( $meta_value <= 6 ) {
				$result['detractors']++;
				continue;
			}

			$result['passives']++;
		}

		$result['detractors'] = self::format_percentage( $result['detractors'] / $total );
		$result['passives']   = self::format_percentage( $result['passives'] / $total );
		$result['promoters']  = self::format_percentage( $result['promoters'] / $total );
		$result['nps']        = $result['promoters'] - $result['detractors'];

		return $result;
	}

	/**
	 * Converts value to percentage.
	 *
	 * @param float $value The value.
	 * @return float The value without `%`.
	 */
	protected static function format_percentage( $value ) {
		return round( $value * 10000 ) / 100;
	}

	/**
	 * Adds NPS field to the list of number fields.
	 *
	 * @param array $fields List of number fields.
	 * @return array
	 */
	public static function add_to_number_fields( $fields ) {
		$fields[] = 'nps';
		return $fields;
	}

	/**
	 * Changes input type for logic rules.
	 *
	 * @see \FrmProFieldsHelper::get_the_input_type_for_logic_rules()
	 *
	 * @return string
	 */
	public static function change_input_type_for_logic_rules() {
		return 'radio';
	}

	/**
	 * Shows NPS score in report page.
	 *
	 * @param array $boxes The boxes to show with a field.
	 * @param array $args The arguments. Contains `field`.
	 */
	public static function show_nps_score_in_report( $boxes, $args ) {
		$field = $args['field'];

		if ( 'nps' !== $field->type ) {
			return $boxes;
		}

		$filter_atts      = isset( $args['filter_atts'] ) ? $args['filter_atts'] : array();
		$nps_report_boxes = array(
			'detractors' => __( 'Detractors (0-6)', 'formidable-surveys' ),
			'passives'   => __( 'Passives (7-8)', 'formidable-surveys' ),
			'promoters'  => __( 'Promoters (9-10)', 'formidable-surveys' ),
			'nps'        => __( 'Net Promoter Score', 'formidable-surveys' ),
		);

		foreach ( $nps_report_boxes as $key => $label ) {
			$score = FrmProStatisticsController::stats_shortcode(
				$filter_atts + array(
					'id'   => $field->id,
					'type' => $key,
				)
			);

			if ( 'nps' !== $key ) {
				$score .= '%';
			}

			$boxes[] = array(
				'label' => $label,
				'stat'  => $score,
			);
		}

		return $boxes;
	}

	/**
	 * Modifies graph data.
	 *
	 * @param array $data Graph data.
	 * @param array $atts Graph atts.
	 * @return array
	 */
	public static function graph_data( $data, $atts ) {
		if ( ! $data || ! self::should_change_graph( $atts ) ) {
			return $data;
		}

		$field = reset( $atts['fields'] );
		if ( 'nps' !== $field->type ) {
			return $data;
		}

		$new_data = self::get_init_graph_data( $data[0], $atts );

		// Add the current data to the new data.
		foreach ( $data as $row ) {
			if ( ! is_numeric( $row[0] ) ) {
				continue;
			}

			$new_data[ $row[0] + 1 ][1] = intval( $row[1] );
		}

		return $new_data;
	}

	/**
	 * Gets initialize graph data.
	 *
	 * @param array $first_col The first column data.
	 * @param array $atts      Graph atts.
	 * @return array
	 */
	protected static function get_init_graph_data( $first_col, $atts ) {
		if ( 'table' !== $atts['type'] && count( $first_col ) < 3 ) {
			$first_col[2] = array( 'role' => 'style' );
		}
		$new_data = array( $first_col );

		for ( $i = 0; $i <= 10; $i++ ) {
			// The first element is option, the second is the number of selected.
			$new_data[ $i + 1 ] = array( (string) $i, 0 );

			if ( isset( $first_col[2] ) ) {
				if ( $i < 7 ) {
					// Red.
					$color = '#fedad7';
				} elseif ( $i < 9 ) {
					// Yellow.
					$color = '#faf3c5';
				} else {
					// Green.
					$color = '#cde7da';
				}

				$new_data[ $i + 1 ][2] = $color;
			}
		}

		return $new_data;
	}

	/**
	 * Modifies Google chart options.
	 *
	 * @param array $options Chart options.
	 * @param array $args    Arguments. Contains `atts` and `type`.
	 * @return array
	 */
	public static function google_chart_options( $options, $args ) {
		if ( ! self::should_change_graph( $args['atts'] ) ) {
			return $options;
		}

		$field = reset( $args['atts']['fields'] );
		if ( 'nps' !== $field->type ) {
			return $options;
		}

		// Just show int value.
		$options['vAxis']['format'] = '#';

		return $options;
	}

	/**
	 * Checks if should change graph to show NPS data.
	 *
	 * @since 1.0.12
	 *
	 * @param array $atts Graph shortcode attributes.
	 * @return bool
	 */
	protected static function should_change_graph( $atts ) {
		return ! empty( $atts['fields'] ) && 1 === count( $atts['fields'] ) && empty( $atts['x_axis'] );
	}

	/**
	 * Changes the has_variable_html_id value of NPS field. This fixes calculation issue.
	 *
	 * @param bool  $has_variable_html_id Has variable HTML id or not.
	 * @param array $args                 The arguments.
	 * @return bool
	 */
	public static function field_has_variable_html_id( $has_variable_html_id, $args ) {
		if ( 'nps' === FrmField::get_field_type( $args['field'] ) ) {
			return true;
		}

		return $has_variable_html_id;
	}

	/**
	 * Adds field type switch for NPS field.
	 *
	 * @param array $field_types The field types list.
	 * @param array $args        {
	 *     The arguments.
	 *
	 *     @type string $type            Field type name.
	 *     @type array  $field_selection The full list of fields.
	 * }
	 *
	 * @return array
	 */
	public static function switch_field_types( $field_types, $args ) {
		$switch_types = array( 'radio', 'checkbox', 'select', 'scale', 'star', 'lookup' );

		if ( 'nps' === $args['type'] ) {
			foreach ( $switch_types as $switch_type ) {
				if ( isset( $args['field_selection'][ $switch_type ] ) ) {
					$field_types[ $switch_type ] = $args['field_selection'][ $switch_type ];
				}
			}
		} elseif ( in_array( $args['type'], $switch_types, true ) ) {
			// Switch other fields to NPS.
			if ( isset( $args['field_selection']['nps'] ) ) {
				$field_types['nps'] = $args['field_selection']['nps'];
			}
		}

		return $field_types;
	}

	/**
	 * Adds NPS field to radio similar field types.
	 *
	 * @since 1.0.06
	 *
	 * @param array $field_types Field types.
	 * @return array
	 */
	public static function add_to_radio_similar_field_types( $field_types ) {
		$field_types[] = 'nps';
		return $field_types;
	}
}
