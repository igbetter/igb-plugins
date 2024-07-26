<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsPreviewHelper {

	/**
	 * @var object
	 */
	private static $view;

	/**
	 * @var array
	 */
	private static $atts;

	/**
	 * Store the $where_query value when get_ordered_entry_ids is called for determining entryCount.
	 *
	 * @var mixed
	 */
	private static $last_where_query;

	public static function get_ordered_entry_ids( $form_id, $filter, $sort = array(), $limit = '', $page_size = '', $offset = '' ) {
		self::prepare_view_object( $form_id, $filter, $sort, $limit, $page_size, $offset );
		FrmViewsDisplaysController::apply_atts_to_view_object( self::$atts, self::$view );
		$where_query            = self::get_where_query_for_view_listing_page();
		self::$last_where_query = $where_query;
		return FrmViewsDisplaysController::get_ordered_entry_ids_for_view( self::$view, self::$atts, $where_query );
	}

	/**
	 * @param int        $form_id
	 * @param array      $filter
	 * @param array      $sort
	 * @param int|string $limit
	 * @param int|string $page_size
	 * @param int|string $offset
	 * @return object
	 */
	private static function prepare_view_object( $form_id, $filter, $sort = array(), $limit = '', $page_size = '', $offset = '' ) {
		$view                     = new stdClass();
		$view->ID                 = 0;
		$view->frm_form_id        = $form_id;
		$view->frm_where          = array();
		$view->frm_where_is       = array();
		$view->frm_where_val      = array();
		$view->frm_where_or       = array();
		$view->frm_where_group    = array();
		$view->frm_where_group_or = array();
		$view->frm_show_count     = 'dynamic';
		$view->frm_order_by       = array();
		$view->frm_order          = array();
		$view->frm_page_size      = $page_size;

		if ( $filter ) {
			$index = 1; // index starts at 1, not 0
			foreach ( $filter as $rule ) {
				$view->frm_where[ $index ]          = $rule['where'];
				$view->frm_where_is[ $index ]       = $rule['is'];
				$view->frm_where_val[ $index ]      = $rule['val'];
				$view->frm_where_or[ $index ]       = ! empty( $rule['or'] );
				$view->frm_where_group[ $index ]    = ! empty( $rule['group'] ) ? $rule['group'] : 0;
				$view->frm_where_group_or[ $index ] = ! empty( $rule['group_or'] );
				++$index;
			}
		}

		if ( $sort ) {
			foreach ( $sort as $rule ) {
				$view->frm_order_by[] = $rule['by'];
				$view->frm_order[]    = $rule['order'];
			}
		}

		$view->frm_limit  = $limit;
		$view->frm_offset = $offset;

		self::$view = $view;
		self::$atts = FrmViewsDisplaysController::get_atts_for_view( array(), $view );

		return $view;
	}

	/**
	 * @return array
	 */
	private static function get_where_query_for_view_listing_page() {
		$where = FrmViewsDisplaysController::get_where_query_for_view_listing_page( self::$view, self::$atts );
		return $where;
	}

	/**
	 * @param array $unsanitized
	 * @return array
	 */
	public static function sanitize_filter( $unsanitized ) {
		if ( ! $unsanitized ) {
			return array();
		}

		$valid_where_is_options = array_keys( FrmViewsDisplaysHelper::where_is_options() );
		$sanitized              = array();

		foreach ( $unsanitized as $rule ) {
			if ( ! in_array( $rule['is'], $valid_where_is_options, true ) ) {
				continue;
			}

			$sanitized[] = array(
				'where'    => sanitize_key( $rule['where'] ),
				'is'       => $rule['is'],
				'val'      => sanitize_text_field( $rule['val'] ),
				'or'       => ! empty( $rule['or'] ) ? 1 : 0,
				'group'    => ! empty( $rule['group'] ) ? absint( $rule['group'] ) : 0,
				'group_or' => ! empty( $rule['group_or'] ) ? 1 : 0,
			);
		}

		return $sanitized;
	}

	/**
	 * @param array $unsanitized
	 * @return array
	 */
	public static function sanitize_sort( $unsanitized ) {
		if ( ! $unsanitized ) {
			return array();
		}

		$valid_order_options = array( 'ASC', 'DESC' );
		$sanitized           = array();
		foreach ( $unsanitized as $rule ) {
			if ( ! in_array( $rule['order'], $valid_order_options, true ) ) {
				continue;
			}

			$sanitized[] = array(
				'by'    => sanitize_key( $rule['by'] ),
				'order' => $rule['order'],
			);
		}

		return $sanitized;
	}

	public static function get_last_where_query() {
		return self::$last_where_query;
	}
}
