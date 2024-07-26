<?php
/**
 * Ranking Field graph data class
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\helpers;

use FrmProEntryMeta;
/**
 * Prepare graph data in reports page for Ranking Field.
 *
 * @since 1.1
 */
class RankingGraphData {

	/**
	 * The graph prepared data.
	 *
	 * @var array
	 */
	private $graph_data = array();

	/**
	 * Constructor function. Builds the Ranking Field graph data for bars & table chart.
	 *
	 * @since 1.1
	 * @param array   $data The default graph data.
	 * @param object  $field The field object.
	 * @param boolean $is_table_chart Defines if the graph data is for a table chart.
	 */
	public function __construct( $data, $field, $is_table_chart ) {

		$options      = $this->get_all_field_options( $field );
		$field_values = FrmProEntryMeta::get_all_metas_for_field( $field, array() );

		if ( empty( $field_values ) ) {
			return;
		}

		$ranking_field_data = $this->order_options_by_average_position( $field_values );

		if ( true === $is_table_chart ) {
			$data[0] = array(
				__( 'Rank', 'formidable-surveys' ),
				__( 'Option Name', 'formidable-surveys' ),
				__( 'Times at #1', 'formidable-surveys' ),
				__( 'Average Position', 'formidable-surveys' ),
			);

			$this->graph_data = array_merge( array( $data[0] ), $this->prepare_table_chart_data( $ranking_field_data ) );
			return;
		}

		$data[0][1]       = __( 'Average Position', 'formidable-surveys' );
		$this->graph_data = array_merge( array( $data[0] ), self::prepare_bar_chart_data( $ranking_field_data, $data[1][2] ) );
	}

	/**
	 * Get the graph data.
	 *
	 * @since 1.1
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->graph_data;
	}

	/**
	 * Get all the available field options' value.
	 *
	 * @since 1.1
	 * @param object $field The field object.
	 */
	private function get_all_field_options( $field ) {
		return array_column( $field->options, 'value' );
	}

	/**
	 * Order the ranking field option by average position.
	 *
	 * @since 1.1
	 * @param array $meta_values The field meta values.
	 */
	private function order_options_by_average_position( $meta_values ) {
		$results = array();
		$data    = array();
		foreach ( $meta_values as $selection_group ) {
			foreach ( $selection_group as $key => $option ) {
				if ( ! isset( $data[ $option ] ) ) {
					$data[ $option ] = array(
						'name'                 => $option,
						'first-position-count' => 0,
						'average-position'     => array(),
					);
				};

				if ( 0 === $key ) {
					$data[ $option ]['first-position-count']++;
				};

				$data[ $option ]['average-position'][] = ( $key + 1 );

			};
		}

		foreach ( $data as $option ) {
			$average_position           = number_format( array_sum( $option['average-position'] ) / count( $option['average-position'] ), 1 );
			$option['average-position'] = $average_position;
			$results[ $option['name'] ] = $option;
		}

		usort(
			$results,
			function( $a, $b ) {
				if ( (float) $a['average-position'] === (float) $b['average-position'] ) {
					return 0;
				}
				return $a['average-position'] < $b['average-position'] ? -1 : 1;
			}
		);

		return $results;
	}

	/**
	 * Prepare the data for bar chart.
	 *
	 * @since 1.1
	 * @param array  $ranking_values The ranking values ordered by average position.
	 * @param string $color The chart bar color.
	 */
	private function prepare_bar_chart_data( $ranking_values, $color ) {
		$data = array();
		foreach ( $ranking_values as $value ) {
			$data[] = array( $value['name'], (float) $value['average-position'], $color );
		}
		return $data;
	}

	/**
	 * Prepare the data for table chart.
	 *
	 * @since 1.1
	 * @param array $ranking_values The ranking values ordered by average position.
	 */
	private function prepare_table_chart_data( $ranking_values ) {
		$data  = array();
		$index = 1;
		foreach ( $ranking_values as $value ) {
			$data[] = array(
				'#' . $index,
				$value['name'],
				(string) $value['first-position-count'],
				$value['average-position'],
			);
			$index++;
		}
		return $data;
	}
}
