<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProDisplay {

	/**
	 * Check for a qualified view.
	 *
	 * @param array $args
	 * @return false|object
	 */
	public static function get_auto_custom_display( $args ) {
		return FrmProDisplaysController::deprecated_function( __METHOD__, 'FrmViewsDisplay::get_auto_custom_display', $args );
	}

	public static function getOne( $id, $blog_id = false, $get_meta = false, $atts = array() ) {
		return FrmProDisplaysController::deprecated_function( __METHOD__, 'FrmViewsDisplay::getOne', $id, $blog_id, $get_meta, $atts );
	}

	public static function getAll( $where = array(), $order_by = 'post_date', $limit = 99 ) {
		return FrmProDisplaysController::deprecated_function( __METHOD__, 'FrmViewsDisplay::getAll', $where, $order_by, $limit );
	}
}
