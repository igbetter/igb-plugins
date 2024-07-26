<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsDisplaysHelper {

	public static function setup_new_vars() {
		$values   = array();
		$defaults = self::get_default_opts();
		foreach ( $defaults as $var => $default ) {
			$sanitize       = self::sanitize_option( $var );
			$values[ $var ] = FrmAppHelper::get_param( $var, $default, 'post', $sanitize );
		}

		return $values;
	}

	public static function setup_edit_vars( $post, $check_post = true ) {
		if ( ! $post ) {
			return false;
		}

		$values = (object) $post;
		$vars   = self::get_keys_that_map_to_an_frm_prefixed_option();

		foreach ( $vars as $var ) {
			$values->{'frm_' . $var} = get_post_meta( $post->ID, 'frm_' . $var, true );
			if ( $check_post ) {
				$sanitize                = self::sanitize_option( $var );
				$values->{'frm_' . $var} = FrmAppHelper::get_param( $var, $values->{'frm_' . $var}, 'post', $sanitize );
			}
		}

		$defaults = self::get_default_opts();
		$options  = get_post_meta( $post->ID, 'frm_options', true );
		foreach ( $defaults as $var => $default ) {
			if ( ! isset( $values->{'frm_' . $var} ) ) {
				$values->{'frm_' . $var} = isset( $options[ $var ] ) ? $options[ $var ] : $default;
				if ( $check_post ) {
					$sanitize                = self::sanitize_option( $var );
					$values->{'frm_' . $var} = FrmAppHelper::get_post_param( 'options[' . $var . ']', $values->{'frm_' . $var}, $sanitize );
				}
			} elseif ( 'param' === $var && empty( $values->{'frm_' . $var} ) ) {
				$values->{'frm_' . $var} = $default;
			}
		}

		$values->frm_form_id  = (int) $values->frm_form_id;
		$values->frm_order_by = empty( $values->frm_order_by ) ? array() : (array) $values->frm_order_by;
		$values->frm_order    = empty( $values->frm_order ) ? array() : (array) $values->frm_order;

		return $values;
	}

	/**
	 * Some meta keys map directly to a post_meta key like frm_form_id, or frm_dyncontent.
	 * This lists the keys that do map this way. Others are mapped together as frm_options.
	 *
	 * @return array
	 */
	public static function get_keys_that_map_to_an_frm_prefixed_option() {
		return array( 'form_id', 'entry_id', 'dyncontent', 'param', 'type', 'show_count', 'table_view', 'grid_view' );
	}

	/**
	 * Allow script and style tags in content boxes,
	 * but remove them from other settings
	 *
	 * @param string $name
	 * @return string
	 */
	private static function sanitize_option( $name ) {
		$allow_code = array( 'before_content', 'content', 'after_content', 'dyncontent', 'empty_msg', 'where_is' );
		return in_array( $name, $allow_code, true ) ? '' : 'sanitize_text_field';
	}

	public static function get_default_opts() {
		return array(
			'description'           => '',
			'name'                  => '',
			'display_key'           => '',
			'form_id'               => 0,
			'date_field_id'         => '',
			'edate_field_id'        => '',
			'repeat_event_field_id' => '',
			'repeat_edate_field_id' => '',
			'entry_id'              => '',
			'before_content'        => '',
			'content'               => '',
			'after_content'         => '',
			'dyncontent'            => '',
			'param'                 => 'entry',
			'type'                  => '',
			'show_count'            => 'all',
			'no_rt'                 => 0,
			'order_by'              => array(),
			'order'                 => array(),
			'limit'                 => '',
			'page_size'             => '',
			'offset'                => '',
			'empty_msg'             => __( 'No Entries Found', 'formidable-views' ),
			'copy'                  => 0,
			'where'                 => array(),
			'where_is'              => array(),
			'where_val'             => array(),
			'where_or'              => array(),
			'where_group'           => array(),
			'where_group_or'        => array(),
			'group_by'              => array(),
			'ajax_refresh'          => 0,
			'disable_preview'       => 0,
			'table_row_style'       => 'frm-alt-table',
			'table_responsive'      => 0,
			'table_classes'         => '',
			'grid_column_count'     => 1,
			'grid_row_gap'          => '20',
			'grid_column_gap'       => '2',
			'grid_classes'          => '',
			'ajax_pagination'       => '',
			'calendar_event_popup'  => 1,
			'calendar_options'      => array(),
			'map_address_fields'    => array(),
		);
	}

	public static function is_edit_view_page() {
		global $pagenow;
		$post_type = FrmAppHelper::simple_get( 'post_type', 'sanitize_title' );
		return is_admin() && 'edit.php' === $pagenow && FrmViewsDisplaysController::$post_type === $post_type;
	}

	public static function prepare_duplicate_view( &$post ) {
		$post = self::get_current_view( $post );
		$post = self::setup_edit_vars( $post );
	}

	/**
	 * Check if a View has been duplicated. If it has, get the View object to be duplicated. If it has not been duplicated, just get the new post object.
	 *
	 * @param object $post
	 * @return object - the View to be copied or the View that is being created (if it is not being duplicated)
	 */
	public static function get_current_view( $post ) {
		if ( FrmViewsDisplaysController::$post_type === $post->post_type && isset( $_GET['copy_id'] ) ) {
			global $copy_display;
			return $copy_display;
		}
		return $post;
	}

	public static function where_is_options() {
		return array(
			'='               => __( 'equals', 'formidable-views' ),
			'!='              => __( 'does not equal', 'formidable-views' ),
			'>'               => __( 'is greater than', 'formidable-views' ),
			'<'               => __( 'is less than', 'formidable-views' ),
			'>='              => __( 'is greater than or equal to', 'formidable-views' ),
			'<='              => __( 'is less than or equal to', 'formidable-views' ),
			'LIKE'            => __( 'contains', 'formidable-views' ),
			'not LIKE'        => __( 'does not contain', 'formidable-views' ),
			'LIKE%'           => __( 'starts with', 'formidable-views' ),
			'%LIKE'           => __( 'ends with', 'formidable-views' ),
			'group_by'        => __( 'is unique (get oldest entries)', 'formidable-views' ),
			'group_by_newest' => __( 'is unique (get newest entries)', 'formidable-views' ),
		);
	}

	/**
	 * Get the View type (show_count) for each View, e.g. calendar, dynamic
	 *
	 * @return array|object|void|null
	 */
	public static function get_show_counts() {
		$show_counts = self::get_meta_values( 'frm_show_count', 'frm_display' );
		return $show_counts;
	}

	/**
	 * Get the options for the site's Views
	 *
	 * @param false|string $include_key specify one specific key to retrieve to reduce memory use. if false all keys are included.
	 * @return array|object|void|null
	 */
	public static function get_frm_options_for_views( $include_key = false ) {
		$views_options = self::get_meta_values( 'frm_options', 'frm_display' );

		foreach ( $views_options as $key => $value ) {
			FrmProAppHelper::unserialize_or_decode( $value->meta_value );

			if ( false !== $include_key ) {
				if ( isset( $value->meta_value[ $include_key ] ) ) {
					$views_options[ $key ]->meta_value = array(
						$include_key => $value->meta_value[ $include_key ],
					);
				} else {
					$views_options[ $key ]->meta_value = array();
				}
			} else {
				$views_options[ $key ]->meta_value = $value->meta_value;
			}
		}

		return $views_options;
	}

	/**
	 * Get the specified meta value for the specified post type
	 *
	 * @param string $key
	 * @param string $post_type
	 *
	 * @return array|object|void|null
	 */
	public static function get_meta_values( $key = '', $post_type = 'frm_display' ) {
		global $wpdb;

		if ( empty( $key ) ) {
			return;
		}

		$table                = $wpdb->postmeta . ' pm LEFT JOIN ' . $wpdb->posts . ' p ON p.ID = pm.post_id';
		$field                = 'pm.post_id, pm.meta_value, pm.meta_key';
		$where['pm.meta_key'] = $key;
		$where['p.post_type'] = $post_type;

		$results = FrmDb::get_var( $table, $where, $field, array(), '', 'associative_results' );

		return $results;
	}

	public static function update_post_content_if_view_exists( &$post, $display_id, $form, $entry, &$dyn_content ) {
		$display = FrmViewsDisplay::getOne( $display_id, false, true );

		if ( ! $display ) {
			return;
		}

		$dyn_content          = 'one' === $display->frm_show_count ? $display->post_content : $display->frm_dyncontent;
		$post['post_content'] = apply_filters( 'frm_content', $dyn_content, $form, $entry );
	}

	/**
	 * Get the page number from the URL, and make sure it isn't 0
	 *
	 * @param int $view_id
	 * @return int
	 */
	public static function get_current_page_num( $view_id ) {
		$page_param   = $_GET && isset( $_GET[ 'frm-page-' . $view_id ] ) ? 'frm-page-' . $view_id : 'frm-page';
		$current_page = FrmAppHelper::simple_get( $page_param, 'absint', 1 );
		return max( 1, $current_page );
	}

	/**
	 * @since 5.2
	 *
	 * @param object $view
	 * @return string either 'classic', 'calendar', 'table', or 'grid'.
	 */
	public static function get_view_type( $view ) {
		$show_count = get_post_meta( $view->ID, 'frm_show_count', true );
		if ( 'calendar' === $show_count ) {
			return 'calendar';
		}

		$view_type = get_post_meta( $view->ID, 'frm_view_type', true );
		if ( is_string( $view_type ) && in_array( $view_type, self::get_valid_view_types(), true ) ) {
			return $view_type;
		}

		if ( self::is_grid_type( $view ) ) {
			return 'grid';
		}

		if ( self::is_table_type( $view ) ) {
			return 'table';
		}

		return 'classic';
	}

	/**
	 * @since 5.6
	 *
	 * @return array<string>
	 */
	private static function get_valid_view_types() {
		$valid_view_types = array( 'grid', 'table' );

		/**
		 * @since 5.6
		 *
		 * @param array<string> $valid_view_types
		 */
		$filtered_valid_view_types = apply_filters( 'frm_valid_view_types', $valid_view_types );

		if ( is_array( $filtered_valid_view_types ) ) {
			$valid_view_types = $filtered_valid_view_types;
		} else {
			_doing_it_wrong( __METHOD__, esc_html__( 'Please return an array of valid view types.', 'formidable-views' ), '5.6' );
		}

		return $valid_view_types;
	}

	/**
	 * @since 5.3
	 *
	 * @param object $view
	 * @return int 1 or 0.
	 */
	public static function is_grid_type( $view ) {
		if ( in_array( get_post_meta( $view->ID, 'frm_grid_view', true ), array( '1', 1 ), true ) ) {
			return 1;
		}
		if ( self::is_table_type( $view ) ) {
			// table types use grid style content, so check for table before checking content for grid data.
			return 0;
		}
		$show_count = get_post_meta( $view->ID, 'frm_show_count', true );
		if ( ! in_array( $show_count, array( 'all', 'dynamic' ), true ) ) {
			return 0;
		}
		if ( self::content_is_in_grid_format( $view->post_content ) ) {
			return 1;
		}
		$dyncontent = get_post_meta( $view->ID, 'frm_dyncontent', true );
		if ( self::content_is_in_grid_format( $dyncontent ) ) {
			return 1;
		}
		return 0;
	}

	/**
	 * @since 5.3
	 *
	 * @param string $content post_content or dyncontent value.
	 * @return bool
	 */
	private static function content_is_in_grid_format( $content ) {
		if ( ! $content ) {
			return false;
		}
		$helper = new FrmViewsContentHelper( $content );
		return $helper->content_is_an_array();
	}

	/**
	 * @since 5.3
	 *
	 * @param object $view
	 * @return int 1 or 0.
	 */
	public static function is_table_type( $view ) {
		return in_array( get_post_meta( $view->ID, 'frm_table_view', true ), array( 1, '1' ), true ) ? 1 : 0;
	}

	/**
	 * @since 5.6
	 *
	 * @param object|int $view
	 * @return int 1 or 0.
	 */
	public static function is_map_type( $view ) {
		if ( ! class_exists( 'FrmGeoMapViewController', false ) ) {
			return 0;
		}
		$view_id = is_object( $view ) ? $view->ID : $view;
		return 'map' === get_post_meta( $view_id, 'frm_view_type', true ) ? 1 : 0;
	}

	/**
	 * @since 5.3
	 *
	 * @param int $view_id
	 * @return bool
	 */
	public static function is_legacy_table_type( $view_id ) {
		$options = get_post_meta( $view_id, 'frm_options', true );

		if ( ! is_array( $options ) || ! array_key_exists( 'before_content', $options ) ) {
			return false;
		}

		$before_content = $options['before_content'];
		$show_count     = get_post_meta( $view_id, 'frm_show_count', true );
		return (bool) self::check_view_data_for_table_type( $show_count, $before_content );
	}

	/**
	 * @since 5.3
	 *
	 * @param string $show_count
	 * @param string $listing_before_content
	 * @return int 1 or 0.
	 */
	public static function check_view_data_for_table_type( $show_count, $listing_before_content ) {
		return in_array( $show_count, array( 'all', 'dynamic' ), true ) && self::check_if_view_before_content_matches_table_type( $listing_before_content ) ? 1 : 0;
	}

	/**
	 * @since 5.3
	 *
	 * @param string $before_content
	 * @return bool
	 */
	private static function check_if_view_before_content_matches_table_type( $before_content ) {
		$before_content_begins_a_table = false !== strpos( $before_content, '<table' );
		if ( ! $before_content_begins_a_table ) {
			return false;
		}
		$before_content_ends_a_table = false !== strpos( $before_content, '</table>' );
		return ! $before_content_ends_a_table;
	}

	/**
	 * @since 5.2
	 *
	 * @param string|WP_Post $view
	 * @return string
	 */
	public static function get_view_type_label( $view ) {
		$view_type = is_string( $view ) ? $view : self::get_view_type( $view );
		switch ( $view_type ) {
			case 'calendar':
				return __( 'Calendar', 'formidable-views' );
			case 'grid':
				return __( 'Grid', 'formidable-views' );
			case 'table':
				return __( 'Table', 'formidable-views' );
			case 'map':
				return __( 'Map', 'formidable-views' );
			case 'classic':
			default:
				return __( 'Classic', 'formidable-views' );
		}
	}

	/**
	 * @param string              $string
	 * @param int|stdClass|string $form
	 * @return string
	 */
	public static function maybe_replace_form_name_shortcodes( $string, $form ) {
		if ( ! is_callable( 'FrmFormsController::replace_form_name_shortcodes' ) ) {
			return $string;
		}
		return FrmFormsController::replace_form_name_shortcodes( $string, $form );
	}
}
