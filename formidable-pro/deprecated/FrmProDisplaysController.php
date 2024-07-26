<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProDisplaysController {

	public static $post_type = 'frm_display'; // this is here for backward compatibility, but use FrmViewsDisplaysController::$post_type instead

	private static $first_version_without_views = '4.09'; // used by deprecated_function

	/**
	 * Handle a deprecated views function
	 *
	 * @param string $method generally passed as __METHOD__
	 * @param string $replacement the new function in formidable-views
	 */
	public static function deprecated_function( $method, $replacement = '', ...$params ) {
		if ( ! self::silently_handle_deprecation( $method ) ) {
			_deprecated_function( $method, self::$first_version_without_views, $replacement );
		}

		if ( $replacement && is_callable( $replacement ) ) {
			return $replacement( ...$params );
		}
	}

	private static function silently_handle_deprecation( $method ) {
		return in_array( $method, self::silent_deprecation_methods(), true );
	}

	private static function silent_deprecation_methods() {
		return array(
			'FrmProDisplay::getOne',
			'FrmProDisplay::getAll',
			'FrmProDisplay::get_auto_custom_display',
			'FrmProDisplaysController::get_shortcode',
		);
	}

	/**
	 * @deprecated 4.09 This is still silently deprecated. It isn't safe to remove and should be properly deprecated.
	 */
	public static function get_shortcode( $atts ) {
		return self::deprecated_function( __METHOD__, 'FrmViewsDisplaysController::get_shortcode', $atts );
	}

	/**
	 * @deprecated 4.09 But deprecated messages were not being logged.
	 * @since 6.9.1 Deprecated messages are no longer silenced.
	 */
	public static function get_before_content_for_listing_page( $view, $args ) {
		return self::deprecated_function( __METHOD__, 'FrmViewsDisplaysController::get_before_content_for_listing_page', $view, $args );
	}

	/**
	 * @deprecated 4.09 But deprecated messages were not being logged.
	 * @since 6.9.1 Deprecated messages are no longer silenced.
	 */
	public static function get_inner_content_for_listing_page( $view, $args ) {
		return self::deprecated_function( __METHOD__, 'FrmViewsDisplaysController::get_inner_content_for_listing_page', $view, $args );
	}

	/**
	 * @deprecated 4.09
	 */
	public static function create_from_template( $path ) {
		return self::deprecated_function( __METHOD__, 'FrmViewsDisplaysController::create_from_template', $path );
	}

	/**
	 * @deprecated 4.09
	 */
	public static function before_delete_post( $post_id ) {
		return self::deprecated_function( __METHOD__, 'FrmViewsDisplaysController::before_delete_post', $post_id );
	}

	/**
	 * @deprecated x.x
	 */
	public static function get_display_data( $view, $content = '', $entry_id = false, $extra_atts = array() ) {
		if ( ! $entry_id && ! empty( $extra_atts['return_entry_ids'] ) && is_callable( 'FrmViewsDisplaysController::get_view_entry_ids' ) ) {
			_deprecated_function( __METHOD__, '6.11.1', 'FrmViewsDisplaysController::get_view_entry_ids' );
			return FrmViewsDisplaysController::get_view_entry_ids( $view, $content );
		}
		// since 4.09.
		return self::deprecated_function( __METHOD__ );
	}
}
