<?php
/**
 * Controller for Views support
 *
 * @since 2.0
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmPdfsViewsController {

	/**
	 * The current index of grid item in the loop.
	 *
	 * @var int
	 */
	private static $loop_index = 0;

	/**
	 * Cache the column count of the current grid.
	 *
	 * @var null|int
	 */
	private static $column_count = null;

	/**
	 * The unique class of a grid. This is used to add CSS to that grid only.
	 *
	 * @var null|string
	 */
	private static $grid_class = null;

	/**
	 * Maybe filter view content.
	 *
	 * @since 2.0
	 *
	 * @param string           $unfiltered_content The view content.
	 * @param stdClass         $entry              The entry that is being actively displayed in the view.
	 * @param array            $shortcodes         List of shortcodes, result of FrmProDisplaysHelper::get_shortcodes.
	 * @param WP_Post|stdClass $view               The target view.
	 * @param string           $all                View display type. 'all' is used for the listing page and 'one' is used for the detail page.
	 * @param string           $odd                'even or 'odd'. Used for zebra striping.
	 * @param array            $args               Additional details including 'is_grid_view'.
	 * @return string
	 */
	public static function entry_content( $unfiltered_content, $entry, $shortcodes, $view, $all, $odd, $args ) {
		if ( ! FrmPdfsAppHelper::is_pdf() || empty( $args['is_grid_view'] ) ) {
			return $unfiltered_content;
		}

		if ( null === self::$column_count ) {
			$options            = get_post_meta( $view->ID, 'frm_options', true );
			self::$column_count = is_array( $options ) && array_key_exists( 'grid_column_count', $options ) ? $options['grid_column_count'] : false;
		}

		if ( ! self::$column_count ) {
			return $unfiltered_content;
		}

		$unfiltered_content = '<div ' . FrmAppHelper::array_to_html_params( self::get_grid_cell_attrs() ) . '>' . $unfiltered_content . '</div>';

		self::$loop_index++;

		if ( null === self::$grid_class ) {
			self::$grid_class = 'frm_grid_container_' . rand( 0, 9999 );
		}

		if ( 0 === self::$loop_index % self::$column_count ) {
			$unfiltered_content .= '</div><div class="frm_grid_container ' . esc_attr( self::$grid_class ) . '">';
		}

		return $unfiltered_content;
	}

	/**
	 * Get attributes to use for a grid view cell.
	 *
	 * @return array
	 */
	private static function get_grid_cell_attrs() {
		return array(
			'class' => self::get_cell_class_from_column_count( self::$column_count ),
		);
	}

	/**
	 * Filter the inner content of a grid view inner content before the wrapper is added.
	 *
	 * @since 2.0
	 *
	 * @param string           $inner_content The inner content of the grid view.
	 * @param WP_Post|stdClass $view          The view getting filtered.
	 * @param array            $args          Additional details including 'is_grid_view'.
	 * @return string
	 */
	public static function inner_content_before_add_wrapper( $inner_content, $view, $args ) {
		if ( ! FrmPdfsAppHelper::is_pdf() || empty( $args['is_grid_view'] ) || empty( self::$column_count ) ) {
			return $inner_content;
		}

		if ( self::$loop_index % self::$column_count ) {
			for ( $i = self::$loop_index % self::$column_count; $i < self::$column_count; $i++ ) {
				$inner_content .= '<div class="' . esc_attr( self::get_cell_class_from_column_count( self::$column_count ) ) . '">&nbsp;</div>';
			}
		}

		$inner_content = self::get_css_for_grid( $view, $args ) . '<div class="frm_grid_container ' . esc_attr( self::$grid_class ) . '">' . $inner_content . '</div>';

		self::$loop_index   = 0;
		self::$column_count = null;
		self::$grid_class   = null;

		return $inner_content;
	}

	/**
	 * Get style tag with CSS to use for handling grid view CSS in a PDF.
	 *
	 * @since 2.0
	 *
	 * @param WP_Post|stdClass $view The grid view that we are displaying in a PDF.
	 * @param array            $args Additional details including 'box_content'.
	 * @return string
	 */
	private static function get_css_for_grid( $view, $args ) {
		$options = get_post_meta( $view->ID, 'frm_options', true );

		$row_gap    = self::get_grid_row_gap( $options );
		$column_gap = self::get_grid_column_gap( $options );
		$cell_css   = self::get_grid_cell_css( $args['box_content'] );
		$grid_class = '.' . self::$grid_class;

		// Start building CSS.
		$css = '<style>';

		$css .= "{$grid_class} { border-spacing: {$column_gap} {$row_gap}; }";
		$css .= "{$grid_class} .frm_grid_container { border-spacing: 0; }"; // Reset inner grid gap.

		if ( $cell_css ) {
			$css .= "\n{$grid_class} > div { {$cell_css} }";
		}

		return $css . '</style>';
	}

	/**
	 * Gets the grid row gap.
	 *
	 * @since 2.0
	 *
	 * @param array $options View options.
	 * @return string
	 */
	private static function get_grid_row_gap( $options ) {
		$row_gap = is_array( $options ) && array_key_exists( 'grid_row_gap', $options ) ? $options['grid_row_gap'] : false;

		if ( ! $row_gap && '0' !== $row_gap && 0 !== $row_gap ) {
			$row_gap = '20';
		}

		$row_gap /= 2; // One half in the top container, one half in the bottom container.

		$row_gap .= 'px';

		return $row_gap;
	}

	/**
	 * Gets the grid column gap.
	 *
	 * @param array $options View options.
	 * @return string
	 */
	private static function get_grid_column_gap( $options ) {
		$column_gap = is_array( $options ) && array_key_exists( 'grid_column_gap', $options ) ? $options['grid_column_gap'] : false;

		if ( ! $column_gap && '0' !== $column_gap && 0 !== $column_gap ) {
			$column_gap = '2';
		}

		// CSS border-spacing doesn't support % unit, so we convert to px. Assume the width of screen is 1000px.
		$column_gap *= 10; // {gap in %} / 100 * 1000.
		$column_gap .= 'px';

		return $column_gap;
	}

	/**
	 * Gets the CSS of grid cell.
	 *
	 * @param array $box_content The grid boxes data.
	 * @return string
	 */
	private static function get_grid_cell_css( $box_content ) {
		$cell_css = '';
		foreach ( $box_content as $box_data ) {
			if ( ! isset( $box_data['box'] ) || 0 !== $box_data['box'] ) {
				continue;
			}

			if ( ! empty( $box_data['style'] ) ) {
				foreach ( $box_data['style'] as $key => $value ) {
					if ( $value ) {
						$cell_css .= FrmViewsLayoutHelper::convert_camel_case_style( $key ) . ': ' . $value . ' !important;';
					}
				}
			}
			break;
		}

		return $cell_css;
	}

	/**
	 * Gets the grid cell class from the given column count.
	 *
	 * @param int $column_count Column count.
	 * @return string
	 */
	private static function get_cell_class_from_column_count( $column_count ) {
		return 'frm' . intval( 12 / $column_count );
	}

	/**
	 * Remove the frm-responsive-table class name from a table view PDF.
	 *
	 * @since 2.0
	 *
	 * @param string $class The class names to use for table view. This may include multiple space separated classes.
	 * @return string
	 */
	public static function table_view_class( $class ) {
		if ( FrmPdfsAppHelper::is_pdf() ) {
			$class = str_replace( 'frm-responsive-table', '', $class );
		}

		return $class;
	}
}
