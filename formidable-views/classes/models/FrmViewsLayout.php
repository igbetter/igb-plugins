<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsLayout {

	private static $table_name = 'frm_view_layouts';

	public static function get_frontend_my_templates_data() {
		return array();
		// return array_reduce( self::get_my_templates(), __CLASS__ . '::prepare_template_object_for_frontend', array() );
	}

	/**
	 * @param array  $total
	 * @param object $current
	 * @return array
	 */
	private static function prepare_template_object_for_frontend( $total, $current ) {
		$total[] = array(
			'id'   => $current->id,
			'name' => $current->name,
			'data' => json_decode( $current->data ),
		);
		return $total;
	}

	/**
	 * @param int          $view_id
	 * @param false|string $type 'listing' or 'detail'.
	 * @return array|false|object
	 */
	public static function get_layouts_for_view( $view_id, $type = false ) {
		$where = array(
			'view_id' => $view_id,
		);
		if ( $type ) {
			$where['type'] = $type;
		}
		$layouts = self::get_layouts( $where );
		if ( $type ) {
			return self::check_layouts( $layouts, $type );
		}
		return $layouts;
	}

	/**
	 * @return array
	 */
	public static function get_my_templates() {
		$where  = array(
			'view_id'    => null,
			'type'       => null,
			// we need to specify something with a value to avoid a PHP Notice (The query argument of wpdb::prepare() must have a placeholder)
			'data like%' => '[',
		);
		$select = 'id, name, data';
		return self::get_layouts( $where, $select );
	}

	/**
	 * @param array  $where
	 * @param string $select
	 * @return array
	 */
	private static function get_layouts( $where, $select = '*' ) {
		if ( self::maybe_create_layout_table() ) {
			return array();
		}
		return FrmDb::get_results( self::$table_name, $where, $select );
	}

	/**
	 * @return bool true if the table needs to be generated
	 */
	private static function maybe_create_layout_table() {
		if ( self::already_created_layout_table() ) {
			return false;
		}
		self::create_layout_table();
		return true;
	}

	/**
	 * @return bool
	 */
	private static function already_created_layout_table() {
		return get_option( 'frm_views_layout_table_exists' );
	}

	/**
	 * Create the table for view layouts (custom "my templates" and layouts assigned to views are both stored here)
	 */
	private static function create_layout_table() {
		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$table_name = $wpdb->prefix . self::$table_name;
		$sql        = "
			CREATE TABLE `{$table_name}` (
				id INT (11) NOT NULL auto_increment,
				name VARCHAR(255) NULL DEFAULT NULL,
				view_id INT (11) NULL DEFAULT NULL,
				type ENUM ('listing', 'detail') NULL DEFAULT NULL,
				data LONGTEXT NOT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY view_id (view_id)
			);
		";

		$collation = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';
		dbDelta( $sql . $collation );

		update_option( 'frm_views_layout_table_exists', true, 'no' );
	}

	/**
	 * @param string $name
	 * @param array  $data
	 * @return bool true on success
	 */
	public static function create_template( $name, $data ) {
		global $wpdb;
		$values = array(
			'name'       => $name,
			'data'       => self::prepare_layout_data_for_save( $data ),
			'created_at' => current_time( 'mysql', 1 ),
		);
		$wpdb->insert( $wpdb->prefix . self::$table_name, $values );
		return true;
	}

	/**
	 * @param array $data
	 * @return string
	 */
	public static function prepare_layout_data_for_save( $data ) {
		$data = self::filter_template_data( $data );
		return wp_json_encode( $data );
	}

	/**
	 * @param array $data
	 * @return array filtered data
	 */
	private static function filter_template_data( $data ) {
		return is_array( $data ) ? array_map( __CLASS__ . '::filter_item', $data ) : array();
	}

	private static function filter_item( $item ) {
		if ( 'row' === self::get_item_type( $item ) ) {
			return self::filter_row( $item );
		}
		return self::filter_box( $item );
	}

	/**
	 * @param array $item
	 * @return string type
	 */
	private static function get_item_type( $item ) {
		return isset( $item['layout'] ) ? 'row' : 'box';
	}

	/**
	 * @param array $row
	 * @return array
	 */
	private static function filter_row( $row ) {
		return array(
			'id'     => absint( $row['id'] ),
			'layout' => isset( $row['layout'] ) ? absint( $row['layout'] ) : 1,
			'boxes'  => isset( $row['boxes'] ) ? self::filter_template_data( $row['boxes'] ) : array(),
		);
	}

	/**
	 * @param array $box
	 * @return array
	 */
	private static function filter_box( $box ) {
		$output = array(
			'id' => absint( $box['id'] ),
		);

		if ( ! isset( $box['rows'] ) ) {
			return $output;
		}

		$output['rows'] = self::filter_template_data( $box['rows'] );
		return $output;
	}

	/**
	 * @param int    $view_id
	 * @param string $listing_layout
	 * @param string $detail_layout
	 */
	public static function maybe_create_layouts_for_view( $view_id, $listing_layout, $detail_layout ) {
		$templates = self::get_layouts_for_view( $view_id );
		self::maybe_update_layout( $view_id, $templates, 'listing', $listing_layout );
		self::maybe_update_layout( $view_id, $templates, 'detail', $detail_layout );
	}

	/**
	 * Check an array of layouts for a specific type.
	 *
	 * @param array  $layouts
	 * @param string $type 'listing' or 'detail'.
	 * @return false|object
	 */
	private static function check_layouts( $layouts, $type ) {
		foreach ( $layouts as $layout ) {
			if ( $type === $layout->type ) {
				return $layout;
			}
		}
		return false;
	}

	/**
	 * Check incoming $data and either create, update, or delete a layout accordingly.
	 *
	 * @param int    $view_id
	 * @param array  $layouts
	 * @param string $type
	 * @param array  $data
	 */
	private static function maybe_update_layout( $view_id, $layouts, $type, $data ) {
		$layout = self::check_layouts( $layouts, $type );
		$data   = self::prepare_layout_data_for_save( $data );

		if ( $data && '[]' !== $data ) {
			if ( $layout ) {
				self::update_layout( $layout->id, $data );
			} else {
				self::create_layout( $view_id, $type, $data );
			}
		} elseif ( $layout ) {
			self::delete_layout( $layout->id );
		}
	}

	/**
	 * Update a layout.
	 *
	 * @param int    $layout_id
	 * @param string $data
	 */
	public static function update_layout( $layout_id, $data ) {
		global $wpdb;
		$values = array(
			'data' => $data,
		);
		$where  = array(
			'id' => $layout_id,
		);
		$wpdb->update( $wpdb->prefix . self::$table_name, $values, $where );
	}

	/**
	 * Create a layout.
	 *
	 * @param int    $view_id
	 * @param string $type
	 * @param string $data
	 */
	public static function create_layout( $view_id, $type, $data ) {
		global $wpdb;
		$values = array(
			'view_id'    => $view_id,
			'type'       => $type,
			'data'       => $data,
			'created_at' => current_time( 'mysql', 1 ),
		);
		$wpdb->insert( $wpdb->prefix . self::$table_name, $values );
	}

	/**
	 * Delete a layout.
	 *
	 * @param int $layout_id
	 */
	public static function delete_layout( $layout_id ) {
		global $wpdb;
		$where = array(
			'id' => $layout_id,
		);
		$wpdb->delete( $wpdb->prefix . self::$table_name, $where );
	}

	/**
	 * Duplicate layouts for a view
	 *
	 * @since 5.5 Add the third param.
	 *
	 * @param int   $original_view_id
	 * @param int   $new_view_id
	 * @param mixed $blog_id
	 */
	public static function duplicate_layouts( $original_view_id, $new_view_id, $blog_id = '' ) {
		if ( $blog_id && is_multisite() ) {
			switch_to_blog( $blog_id );
		}

		$layouts = self::get_layouts_for_view( $original_view_id );

		if ( function_exists( 'restore_current_blog' ) ) {
			restore_current_blog();
		}

		foreach ( $layouts as $layout ) {
			self::create_layout( $new_view_id, $layout->type, $layout->data );
		}
	}
}
