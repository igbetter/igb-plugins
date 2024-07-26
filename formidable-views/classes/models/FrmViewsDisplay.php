<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsDisplay {

	/**
	 * Use the same seed when ordering by RAND.
	 * This seed is passed when paginating so pages are consistent.
	 *
	 * @var int
	 */
	private static $seed = 0;

	/**
	 * Create a blank view.
	 *
	 * @param int    $form_id
	 * @param string $show_count
	 * @param string $name
	 * @return int view id
	 */
	public static function create( $form_id = 0, $show_count = 'all', $name = '' ) {
		$post_data = array(
			'post_status' => 'private',
			'post_type'   => FrmViewsDisplaysController::$post_type,
		);

		if ( $name ) {
			$post_data['post_title'] = $name;
		}

		$view_id = wp_insert_post( $post_data );
		$options = self::get_default_opts_for_create();

		if ( $form_id ) {
			add_post_meta( $view_id, 'frm_form_id', $form_id );
		}

		if ( 'grid' === $show_count ) {
			self::setup_grid_view( $view_id );
			$options['grid_responsive'] = 1; // New grid views are responsive by default.
			$show_count                 = 'all';
		} elseif ( 'table' === $show_count ) {
			$options['table_responsive'] = 1; // New table views are responsive by default.
			$show_count                  = 'all';
			self::setup_table_view( $view_id );
		} elseif ( 'map' === $show_count ) {
			$options    = self::setup_map_view( $view_id, $options );
			$show_count = 'all';
		} elseif ( 'calendar' === $show_count ) {
			$options = self::setup_calendar_view( $view_id, $options );
		}

		$options['ajax_pagination'] = '1'; // Turn on AJAX pagination by default for new views.

		add_post_meta( $view_id, 'frm_show_count', $show_count );
		add_post_meta( $view_id, 'frm_options', $options );

		return $view_id;
	}

	/**
	 * @param int $view_id
	 */
	private static function setup_grid_view( $view_id ) {
		add_post_meta( $view_id, 'frm_grid_view', 1, true );
		add_post_meta( $view_id, 'frm_view_type', 'grid', true );
	}

	/**
	 * @param int $view_id
	 */
	private static function setup_table_view( $view_id ) {
		add_post_meta( $view_id, 'frm_table_view', 1, true );
		add_post_meta( $view_id, 'frm_view_type', 'table', true );

		$options = FrmAppHelper::get_param( 'tableOptions', '', 'post' );

		if ( ! $options ) {
			// if no options are selected, there is nothing that needs to be set up.
			return;
		}

		$boxes   = array();
		$content = array();
		$box_id  = 1;

		foreach ( $options as $option ) {
			$option            = sanitize_text_field( $option );
			$boxes[]           = array(
				'id' => $box_id,
			);
			$is_dynamic_option = strpos( $option, ':' ) && 2 === count( explode( ':', $option ) );

			if ( $is_dynamic_option ) {
				list( $dynamic_field_id, $show_id ) = explode( ':', $option );
				$field                              = FrmField::getOne( $show_id );
				$content[]                          = array(
					'box'     => $box_id,
					'name'    => $field->name,
					'content' => '[' . $dynamic_field_id . ' show=' . $show_id . ']',
				);
			} elseif ( is_numeric( $option ) ) {
				$field     = FrmField::getOne( $option );
				$content[] = array(
					'box'     => $box_id,
					'name'    => $field->name,
					'content' => '[' . $field->id . ']',
				);
			} else {
				$content[] = array(
					'box'     => $box_id,
					'name'    => self::convert_table_option_key_to_label( $option ),
					'content' => '[' . self::convert_table_option_key_to_shortcode_key( $option ) . ']',
				);
			}
			++$box_id;
		}

		$row_data    = array(
			'id'     => 0,
			'boxes'  => $boxes,
			'layout' => 1,
		);
		$layout_data = array( $row_data );
		$layout_data = json_encode( $layout_data );
		FrmViewsLayout::create_layout( $view_id, 'listing', $layout_data );

		wp_update_post(
			array(
				'ID'           => $view_id,
				'post_content' => FrmAppHelper::prepare_and_encode( $content ),
			)
		);
	}

	/**
	 * @since 5.6
	 *
	 * @param int   $view_id
	 * @param array $options
	 * @return array
	 */
	private static function setup_map_view( $view_id, $options ) {
		add_post_meta( $view_id, 'frm_view_type', 'map', true );

		$map_address_fields = FrmAppHelper::get_param( 'mapAddressFields', '', 'post' );
		if ( $map_address_fields ) {
			$options['map_address_fields'] = $map_address_fields;
		}

		return $options;
	}

	/**
	 * @since 5.6
	 *
	 * @param int   $view_id
	 * @param array $options
	 *
	 * @return array
	 */
	private static function setup_calendar_view( $view_id, $options ) {
		$calendar_options = FrmAppHelper::get_param( 'calendarOptions', array(), 'post' );
		if ( $calendar_options ) {
			$options['calendar_options'] = $calendar_options;
			foreach ( $calendar_options as $option ) {
				if ( 'start_date' === $option['name'] ) {
					$options['date_field_id'] = $option['value'];
				}
				if ( 'end_date' === $option['name'] ) {
					$options['edate_field_id'] = $option['value'];
				}
			}
			wp_update_post(
				array(
					'ID'           => $view_id,
					'post_content' => self::calendar_build_predifined_listing_content( $calendar_options ),
				)
			);
		}

		return $options;
	}

	/**
	 * Builds the predefined listing content for the calendar.
	 *
	 * @since 5.6
	 *
	 * @param array $calendar_options The calendar options.
	 *
	 * @return string The predefined listing content.
	 */
	private static function calendar_build_predifined_listing_content( $calendar_options ) {
		$predefined_content = array(
			'title'      => '',
			'start_date' => '',
		);
		foreach ( $calendar_options as $option ) {
			if ( 'title' === $option['name'] && ! empty( $option['value'] ) ) {
				$predefined_content['title'] = '<strong>[' . $option['value'] . ']</strong>';
			}
			if ( 'start_date' === $option['name'] && ! empty( $option['value'] ) ) {
				$predefined_content['start_date'] = '[' . $option['value'] . ']<br/>';
			}
		}
		return $predefined_content['start_date'] . $predefined_content['title'];
	}

	public static function convert_table_option_key_to_label( $option ) {
		switch ( $option ) {
			case 'created_at':
				return __( 'Entry creation date', 'formidable-views' );
			case 'updated_at':
				return __( 'Entry updated date', 'formidable-views' );
			case 'id':
				return __( 'Entry ID', 'formidable-views' );
			case 'item_key':
				return __( 'Entry key', 'formidable-views' );
			case 'post_id':
				return __( 'Post ID', 'formidable-views' );
			case 'parent_item_id':
				return __( 'Parent Entry ID', 'formidable-views' );
			case 'is_draft':
				return __( 'Entry status', 'formidable-views' );
		}
		return '';
	}

	private static function convert_table_option_key_to_shortcode_key( $option ) {
		switch ( $option ) {
			case 'created_at':
				return 'created-at';
			case 'updated_at':
				return 'updated-at';
			case 'item_key':
				return 'key';
		}
		return $option;
	}

	/**
	 * FrmViewsDisplay::create sets frm_options based off of defaults defined from FrmViewsDisplaysHelper::get_default_opts().
	 * However, FrmViewsDisplaysHelper::get_default_opts() includes information that we do not want saved in frm_options.
	 * This includes the keys for other values like name, content, type, etc.
	 * This function removes the keys we don't want to save in frm_options and return only the defaults we want.
	 *
	 * @return array
	 */
	private static function get_default_opts_for_create() {
		$options                     = FrmViewsDisplaysHelper::get_default_opts();
		$keys_that_map_to_view       = array( 'name', 'description', 'content' );
		$keys_that_map_to_frm_prefix = FrmViewsDisplaysHelper::get_keys_that_map_to_an_frm_prefixed_option();
		$old_keys_we_do_not_need     = array( 'display_key' );
		$keys_to_remove              = array_merge( $keys_that_map_to_view, $keys_that_map_to_frm_prefix, $old_keys_we_do_not_need );

		foreach ( $keys_to_remove as $key ) {
			unset( $options[ $key ] );
		}

		return $options;
	}

	public static function duplicate( $id, $copy_keys = false, $blog_id = false ) {
		FrmViewsEditorController::check_license();

		global $wpdb;

		$values = self::getOne( $id, $blog_id, true );

		if ( ! $values || ! is_numeric( $values->frm_form_id ) ) {
			return false;
		}

		$new_values = array();
		foreach ( array( 'post_name', 'post_title', 'post_excerpt', 'post_content', 'post_status', 'post_type' ) as $k ) {
			if ( 'post_content' === $k ) {
				$new_values[ $k ] = self::maybe_prepare_json_content( $values->{$k} );
			} else {
				$new_values[ $k ] = $values->{$k};
			}
			unset( $k );
		}

		$meta = array();
		foreach ( array( 'form_id', 'entry_id', 'dyncontent', 'param', 'type', 'show_count', 'active_preview_filter', 'grid_view', 'table_view', 'view_type' ) as $k ) {
			if ( 'dyncontent' === $k ) {
				$meta[ $k ] = self::maybe_prepare_json_content( $values->{'frm_' . $k} );
			} else {
				$meta[ $k ] = $values->{'frm_' . $k};
			}
			unset( $k );
		}

		$default         = FrmViewsDisplaysHelper::get_default_opts();
		$meta['options'] = array();
		foreach ( $default as $k => $v ) {
			if ( isset( $meta[ $k ] ) ) {
				continue;
			}

			$meta['options'][ $k ] = $values->{'frm_' . $k};
			unset( $k, $v );
		}
		$meta['options']['copy'] = false;

		if ( $blog_id ) {
			$old_form        = FrmForm::getOne( $values->frm_form_id, $blog_id );
			$new_form        = FrmForm::getOne( $old_form->form_key );
			$meta['form_id'] = $new_form->id;
		} else {
			$meta['form_id'] = $values->frm_form_id;
		}

		$post_ID    = wp_insert_post( $new_values );
		$new_values = array_merge( (array) $new_values, $meta );

		self::update( $post_ID, $new_values );
		FrmViewsLayout::duplicate_layouts( $id, $post_ID, $blog_id );

		return $post_ID;
	}

	/**
	 * Grid views (listing+detail) and table views (listing only) use JSON content.
	 *
	 * @param string $content
	 * @return string
	 */
	private static function maybe_prepare_json_content( $content ) {
		$maybe_decoded = FrmAppHelper::maybe_json_decode( $content );
		if ( is_array( $maybe_decoded ) && isset( $maybe_decoded[0] ) && isset( $maybe_decoded[0]['box'] ) ) {
			return FrmAppHelper::prepare_and_encode( $maybe_decoded );
		}
		return $content;
	}

	public static function update( $id, $values ) {
		$new_values              = array();
		$new_values['frm_param'] = isset( $values['param'] ) ? sanitize_title_with_dashes( $values['param'] ) : '';

		$fields = array( 'dyncontent', 'type', 'show_count', 'form_id', 'entry_id', 'active_preview_filter', 'grid_view', 'table_view', 'view_type' );
		foreach ( $fields as $field ) {
			if ( isset( $values[ $field ] ) ) {
				$new_values[ 'frm_' . $field ] = $values[ $field ];
			}
		}

		if ( isset( $values['options'] ) ) {
			$new_values['frm_options'] = array();
			foreach ( $values['options'] as $key => $value ) {
				$new_values['frm_options'][ $key ] = $value;
			}
		}

		foreach ( $new_values as $key => $val ) {
			if ( 'frm_param' === $key ) {
				$last_param = get_post_meta( $id, $key, true );
				if ( $last_param != $val ) {
					update_post_meta( $id, $key, $val );
					add_rewrite_endpoint( $val, EP_PERMALINK | EP_PAGES );
					flush_rewrite_rules();
				}
			} else {
				update_post_meta( $id, $key, $val );
			}

			unset( $key, $val );
		}
	}

	public static function getOne( $id, $blog_id = false, $get_meta = false, $atts = array() ) {
		global $wpdb;

		if ( $blog_id && is_multisite() ) {
			switch_to_blog( $blog_id );
		}

		$id = sanitize_title( $id );
		if ( ! is_numeric( $id ) ) {
			$id = FrmDb::get_var(
				$wpdb->posts,
				array(
					'post_name'     => $id,
					'post_type'     => 'frm_display',
					'post_status !' => 'trash',
				),
				'ID'
			);

			if ( is_multisite() && empty( $id ) ) {
				self::restore_current_blog_in_multisite( $blog_id );
				return false;
			}
		}

		if ( empty( $id ) ) {
			// don't let it get the current page
			self::restore_current_blog_in_multisite( $blog_id );
			return false;
		}

		$post = get_post( $id );
		if ( ! $post || 'frm_display' !== $post->post_type || 'trash' === $post->post_status ) {
			$args  = array(
				'post_type'   => 'frm_display',
				'meta_key'    => 'frm_old_id',
				'meta_value'  => $id,
				'numberposts' => 1,
				'post_status' => 'publish',
			);
			$posts = get_posts( $args );

			if ( $posts ) {
				$post = reset( $posts );
			}
		}

		if ( $post && 'trash' === $post->post_status ) {
			self::restore_current_blog_in_multisite( $blog_id );
			return false;
		}

		if ( $post && $get_meta ) {
			$check_post = isset( $atts['check_post'] ) ? $atts['check_post'] : false;
			$post       = FrmViewsDisplaysHelper::setup_edit_vars( $post, $check_post );
		}
		self::restore_current_blog_in_multisite( $blog_id );

		return $post;
	}

	/**
	 * Restores the current blog.
	 *
	 * @since 5.5
	 *
	 * @param mixed $blog_id
	 */
	private static function restore_current_blog_in_multisite( $blog_id ) {
		if ( $blog_id && is_multisite() ) {
			restore_current_blog();
		}
	}
	public static function getAll( $where = array(), $order_by = 'post_date', $limit = 99 ) {
		if ( ! is_numeric( $limit ) ) {
			$limit = (int) $limit;
		}

		$order = 'DESC';
		if ( strpos( $order_by, ' ' ) ) {
			list( $order_by, $order ) = explode( ' ', $order_by );
		}

		$query = array(
			'numberposts' => $limit,
			'orderby'     => $order_by,
			'order'       => $order,
			'post_type'   => 'frm_display',
			'post_status' => array( 'publish', 'private' ),
		);
		$query = array_merge( (array) $where, $query );

		$results = get_posts( $query );
		return $results;
	}

	/**
	 * Check for a qualified view.
	 * Qualified:   1. set to show calendar or dynamic
	 *              2. published
	 *              3. form has posts/entry is linked to a post
	 *
	 * @param array $args
	 */
	public static function get_auto_custom_display( $args ) {
		$defaults = array(
			'post_id'  => false,
			'form_id'  => false,
			'entry_id' => false,
		);
		$args     = wp_parse_args( $args, $defaults );

		global $wpdb;

		if ( $args['form_id'] ) {
			$display_ids = self::get_display_ids_by_form( $args['form_id'] );

			if ( ! $display_ids ) {
				return false;
			}

			if ( ! $args['post_id'] && ! $args['entry_id'] ) {
				// does form have posts?
				$args['entry_id'] = FrmDb::get_var( 'frm_items', array( 'form_id' => $args['form_id'] ), 'post_id' );
			}
		}

		if ( $args['post_id'] && ! $args['entry_id'] ) {
			// is post linked to an entry?
			$args['entry_id'] = FrmDb::get_var( $wpdb->prefix . 'frm_items', array( 'post_id' => $args['post_id'] ) );
		}

		// this post does not have an auto display
		if ( ! $args['entry_id'] ) {
			return false;
		}

		$query = array(
			'pm.meta_key'   => 'frm_show_count',
			'post_type'     => 'frm_display',
			'pm.meta_value' => array( 'dynamic', 'calendar', 'one' ),
			'p.post_status' => 'publish',
		);

		if ( isset( $display_ids ) ) {
			$query['p.ID'] = $display_ids;
		}

		$display = FrmDb::get_row( $wpdb->posts . ' p LEFT JOIN ' . $wpdb->postmeta . ' pm ON (p.ID = pm.post_ID)', $query, 'p.*', array( 'order_by' => 'p.ID ASC' ) );

		return $display;
	}

	public static function get_display_ids_by_form( $form_id ) {
		global $wpdb;
		return FrmDb::get_col(
			$wpdb->postmeta,
			array(
				'meta_key'   => 'frm_form_id',
				'meta_value' => $form_id,
			),
			'post_ID'
		);
	}

	public static function get_form_custom_display( $form_id ) {
		global $wpdb;

		$display_ids = self::get_display_ids_by_form( $form_id );

		if ( ! $display_ids ) {
			return false;
		}

		$display = FrmDb::get_row(
			$wpdb->posts . ' p LEFT JOIN ' . $wpdb->postmeta . ' pm ON (p.ID = pm.post_ID)',
			array(
				'pm.meta_key'   => 'frm_show_count',
				'post_type'     => 'frm_display',
				'p.ID'          => $display_ids,
				'pm.meta_value' => array( 'dynamic', 'calendar', 'one' ),
				'p.post_status' => 'publish',
			),
			'p.*',
			array( 'order_by' => 'p.ID ASC' )
		);

		return $display;
	}

	/**
	 * @param int $form_id
	 * @return array
	 */
	public static function get_form_action_displays( $form_id ) {
		global $wpdb;

		$where       = array(
			'meta_key'   => 'frm_form_id',
			'meta_value' => $form_id,
		);
		$display_ids = FrmDb::get_col( $wpdb->postmeta, $where, 'post_ID' );

		if ( ! $display_ids ) {
			return array();
		}

		$query_args = array(
			'pm.meta_key'   => 'frm_show_count',
			'post_type'     => 'frm_display',
			'pm.meta_value' => array( 'dynamic', 'calendar', 'one' ),
			'p.post_status' => array( 'publish', 'private' ),
			'p.ID'          => $display_ids,
		);
		$displays   = FrmDb::get_results(
			$wpdb->posts . ' p LEFT JOIN ' . $wpdb->postmeta . ' pm ON (p.ID = pm.post_ID)',
			$query_args,
			'p.ID, p.post_title',
			array( 'order_by' => 'p.post_title ASC' )
		);

		return $displays;
	}

	/**
	 * @param int     $form_id
	 * @param WP_Post $form_action
	 * @return false|object
	 */
	public static function get_form_action_display( $form_id, $form_action ) {
		$display = false;
		if ( isset( $form_action->post_content['display_id'] ) ) {
			if ( is_numeric( $form_action->post_content['display_id'] ) ) {
				$display = self::getOne( $form_action->post_content['display_id'], false, true );
			}
		} elseif ( ! is_numeric( $form_action->post_content['post_content'] ) ) {
			$display = self::get_form_custom_display( $form_id );
			if ( $display ) {
				$display = FrmViewsDisplaysHelper::setup_edit_vars( $display, true );
			}
		}
		return $display;
	}

	public static function save_wppost_action_displays( $settings, $action ) {
		$form_id  = $action['menu_order'];
		$settings = FrmProForm::save_wppost_actions( $settings, $action );

		if ( empty( $settings['display_id'] ) ) {
			return $settings;
		}

		if ( is_numeric( $settings['display_id'] ) ) {
			// updating View
			$type = get_post_meta( $settings['display_id'], 'frm_show_count', true );

			if ( 'one' === $type ) {
				$display                 = get_post( $settings['display_id'], ARRAY_A );
				$display['post_content'] = $_POST['dyncontent']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				wp_insert_post( $display );
			} else {
				update_post_meta( $settings['display_id'], 'frm_dyncontent', $_POST['dyncontent'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		} elseif ( 'new' === $settings['display_id'] ) {
			// Get form name for View title
			$form = FrmForm::getOne( $form_id );
			if ( ! empty( $form->name ) ) {
				$post_title = $form->name;
			} else {
				$post_title = __( 'Single Post', 'formidable-views' );
			}

			// create new
			$cd_values = array(
				'post_status'  => 'publish',
				'post_type'    => 'frm_display',
				'post_title'   => $post_title,
				'post_excerpt' => __( 'Used for the single post page', 'formidable-views' ),
				'post_content' => $_POST['dyncontent'], // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			);

			$display_id             = wp_insert_post( $cd_values );
			$settings['display_id'] = $display_id;

			unset( $cd_values );

			update_post_meta( $display_id, 'frm_param', 'entry' );
			update_post_meta( $display_id, 'frm_type', 'display_key' );
			update_post_meta( $display_id, 'frm_show_count', 'one' );
			update_post_meta( $display_id, 'frm_form_id', $form_id );
		}

		return $settings;
	}

	/**
	 * Get the ID of a View using the key
	 *
	 * @param string $key
	 * @return int
	 */
	public static function get_id_by_key( $key ) {
		$id = FrmDb::get_var( 'posts', array( 'post_name' => sanitize_title( $key ) ) );
		return $id;
	}

	public static function post_options_for_views( $display, $form_id, $action ) {
		$displays = self::get_form_action_displays( $form_id );
		if ( ! $displays ) {
			$display = false;
		}
		$display_id_field_name = $action->get_field_name( 'display_id' );
		require FrmViewsAppHelper::plugin_path() . '/classes/views/actions/post_options.php';
	}

	/**
	 * Get ordered and filtered entries for Views
	 *
	 * @param array $where
	 * @param array $args
	 * @return array
	 */
	public static function get_view_results( $where, $args ) {
		global $wpdb;

		$defaults = array(
			'order_by_array' => array(),
			'order_array'    => array(),
			'limit'          => '',
			'posts'          => array(),
			'display'        => false,
		);

		$args               = wp_parse_args( $args, $defaults );
		$args['time_field'] = false;

		FrmViewsFilterHelper::clear_empty_args_where( $where );

		$query = array(
			'select' => 'SELECT it.id FROM ' . $wpdb->prefix . 'frm_items it',
			'where'  => $where,
			'order'  => 'ORDER BY it.created_at, it.id ASC',
		);

		// if order is set
		if ( ! empty( $args['order_by_array'] ) ) {
			self::prepare_entries_query( $query, $args );
		}

		$query = apply_filters( 'frm_view_order', $query, $args );

		if ( ! empty( $query['where'] ) ) {
			$query['where'] = FrmDb::prepend_and_or_where( 'WHERE ', $query['where'] );
		}
		$query['order'] = rtrim( $query['order'], ', ' );

		if ( 'ORDER BY' === $query['order'] ) {
			unset( $query['order'] ); // Unset the query order if there are no actual sorting fields to avoid broken SQL, ORDER BY followed by nothing.
		}

		$query = implode( ' ', $query ) . $args['limit'];

		$entry_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $entry_ids;
	}

	/**
	 * Set the random seed.
	 *
	 * @since 5.6
	 *
	 * @param int $seed
	 * @return void
	 */
	public static function set_seed( $seed ) {
		self::$seed = $seed;
	}

	/**
	 * Get the random seed.
	 * If one is already defined, it will be used first.
	 * Then, the $_REQUEST parameters are checked.
	 * Otherwise, a new seed is generated.
	 *
	 * @since 5.6
	 *
	 * @return int
	 */
	public static function get_seed() {
		if ( self::$seed ) {
			return self::$seed;
		}

		self::$seed = (int) FrmAppHelper::get_simple_request(
			array(
				'param'    => 'frmseed',
				'sanitize' => 'absint',
				'default'  => 0,
			)
		);
		if ( self::$seed ) {
			return self::$seed;
		}

		self::$seed = mt_rand( 1, PHP_INT_MAX );
		return self::$seed;
	}

	/**
	 * @param array $query
	 * @param array $args
	 * @return void
	 */
	private static function prepare_entries_query( &$query, &$args ) {
		if ( in_array( 'rand', $args['order_by_array'], true ) ) {
			// Random sorting happens in PHP so avoid any sorting in MySQL.
			$query['order'] = '';
			return;
		}

		// Remove other ordering fields if created_at or updated_at is selected for first ordering field
		if ( reset( $args['order_by_array'] ) === 'created_at' || reset( $args['order_by_array'] ) === 'updated_at' ) {
			foreach ( $args['order_by_array'] as $o_key => $order_by_field ) {
				if ( self::is_field_sort_option( $order_by_field ) ) {
					unset( $args['order_by_array'][ $o_key ] );
					unset( $args['order_array'][ $o_key ] );
				}
			}
			$numeric_order_array = array();
		} else {
			// Get number of fields in $args['order_by_array'] - this will not include created_at, updated_at, or random
			$numeric_order_array = array_filter( $args['order_by_array'], self::class . '::is_field_sort_option' );
		}

		if ( ! $numeric_order_array ) {
			// If ordering by creation date and/or update date without any fields
			$query['order'] = ' ORDER BY';

			foreach ( $args['order_by_array'] as $o_key => $order_by ) {
				FrmDb::esc_order_by( $args['order_array'][ $o_key ] );
				$query['order'] .= ' it.' . sanitize_title( $order_by ) . ' ' . $args['order_array'][ $o_key ] . ', ';
				unset( $order_by );
			}
			return;
		}

		// If ordering by at least one field (not just created_at, updated_at, or entry ID)
		$order_fields = array();
		foreach ( $args['order_by_array'] as $o_key => $order_by_field ) {
			if ( is_numeric( $order_by_field ) ) {
				$order_fields[ $o_key ] = FrmField::getOne( $order_by_field );
			} elseif ( self::is_field_sort_option( $order_by_field ) ) {
				$order_fields[ $o_key ] = FrmField::getOne( explode( '_', $order_by_field )[0] );
			} else {
				$order_fields[ $o_key ] = $order_by_field;
			}
		}

		// Get all post IDs for this form
		$linked_posts = array();
		foreach ( $args['posts'] as $post_meta ) {
			$linked_posts[ $post_meta->post_id ] = $post_meta->id;
		}

		$first_order    = true;
		$query['order'] = 'ORDER BY ';
		foreach ( $order_fields as $o_key => $o_field ) {
			self::prepare_ordered_entries_query( $query, $args, $o_key, $o_field, $first_order );
			$first_order = false;
			unset( $o_field );
		}
	}

	/**
	 * Check if a sort option targets a field.
	 * This is true for anything numeric, as well as a number followed by _first or _last (for Name fields).
	 *
	 * @since 5.5
	 *
	 * @param string $option
	 * @return bool
	 */
	private static function is_field_sort_option( $option ) {
		return is_numeric( $option )
			|| self::is_name_subfield_sort_option( $option )
			|| self::is_address_subfield_sort_option( $option );
	}

	/**
	 * Check for an option that looks like field id + ( '_first' or '_last' ).
	 *
	 * @since 5.5
	 *
	 * @param string $option
	 * @return bool
	 */
	private static function is_name_subfield_sort_option( $option ) {
		return self::is_combo_sort_option( $option, array( 'first', 'last' ) );
	}

	/**
	 * Check for an option that looks like field id + ( '_country', '_state' or '_city' ).
	 *
	 * @since 5.5
	 *
	 * @param string $option
	 * @return bool
	 */
	private static function is_address_subfield_sort_option( $option ) {
		return self::is_combo_sort_option( $option, array( 'country', 'state', 'city', 'zip' ) );
	}

	/**
	 * @since 5.5
	 *
	 * @param string $option
	 * @param array  $subfields
	 * @return bool
	 */
	private static function is_combo_sort_option( $option, $subfields ) {
		$split = explode( '_', $option );
		if ( 2 !== count( $split ) ) {
			return false;
		}
		return is_numeric( $split[0] ) && in_array( $split[1], $subfields, true );
	}

	/**
	 * @param array       $query
	 * @param array       $args
	 * @param string      $o_key
	 * @param object|null $o_field
	 * @param bool        $first_order
	 * @return void
	 */
	private static function prepare_ordered_entries_query( &$query, &$args, $o_key, $o_field, $first_order ) {
		global $wpdb;

		$order = $args['order_array'][ $o_key ];
		FrmDb::esc_order_by( $order );
		$o_key = sanitize_title( $o_key );

		// if field is some type of post field
		if ( isset( $o_field->field_options['post_field'] ) && $o_field->field_options['post_field'] ) {

			// if field is custom field
			if ( 'post_custom' === $o_field->field_options['post_field'] ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$query['select'] .= $wpdb->prepare( ' LEFT JOIN ' . $wpdb->postmeta . ' pm' . $o_key . ' ON pm' . $o_key . '.post_id=it.post_id AND pm' . $o_key . '.meta_key = %s ', $o_field->field_options['custom_field'] );
				$query['order']  .= 'CASE WHEN pm' . $o_key . '.meta_value IS NULL THEN 1 ELSE 0 END, pm' . $o_key . '.meta_value ';
				$query['order']  .= FrmProAppHelper::maybe_query_as_number( $o_field->type );
				$query['order']  .= $order . ', ';
			} elseif ( 'post_category' !== $o_field->field_options['post_field'] ) {
				// if field is a non-category post field
				$post_alias       = 'p' . $o_key;
				$entry_meta_alias = 'em' . $o_key;
				$post_field       = esc_sql( $o_field->field_options['post_field'] );
				$query['select'] .= ' LEFT JOIN ' . esc_sql( $wpdb->posts ) . " $post_alias ON $post_alias.ID=it.post_id LEFT JOIN " . $wpdb->prefix . "frm_item_metas $entry_meta_alias ON $entry_meta_alias.item_id=it.id AND $entry_meta_alias.field_id=" . $o_field->id;
				$query['order']  .= "CASE WHEN $post_alias." . $post_field . " IS NULL THEN $entry_meta_alias.meta_value ELSE $post_alias." . $post_field . ' END ' . $order . ', ';
			}
		} elseif ( self::is_field_sort_option( $args['order_by_array'][ $o_key ] ) ) {
			// Ordering by a normal, non-post field.

			if ( ! is_object( $o_field ) ) {
				// If the field is deleted, exit early.
				return;
			}

			if ( in_array( $o_field->type, array( 'name', 'address' ), true ) ) {
				self::prepare_order_by_for_combo_field( $query, $o_field, $order, $o_key, $args['order_by_array'][ $o_key ] );
			} else {
				$query['select'] .= $wpdb->prepare( ' LEFT JOIN ' . $wpdb->prefix . 'frm_item_metas em' . $o_key . ' ON em' . $o_key . '.item_id=it.id AND em' . $o_key . '.field_id=%d ', $o_field->id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$query['order']  .= 'CASE WHEN em' . $o_key . '.meta_value IS NULL THEN 1 ELSE 0 END, em' . $o_key . '.meta_value ';
				$query['order']  .= FrmProAppHelper::maybe_query_as_number( $o_field->type );
				$query['order']  .= $order . ', ';
			}

			// Meta value is only necessary for time field reordering and only if time field is first ordering field
			// Check if time field (for time field ordering)
			if ( $first_order && 'time' === $o_field->type ) {
				$args['time_field'] = $o_field;
			}
		} else {
			$query['order'] .= 'it.' . sanitize_title( $o_field ) . ' ' . $order . ', ';
		}
	}

	/**
	 * Unserialize a name field in MySQL to determine the proper order.
	 *
	 * @since 5.5
	 *
	 * @param array  $query
	 * @param object $o_field
	 * @param string $order
	 * @param string $o_key
	 * @param string $order_by The string setting for the order by field.
	 *                         This may be "created_at", a field id, or a name field ID
	 *                         ending with "_first" or "_last".
	 * @return void
	 */
	private static function prepare_order_by_for_combo_field( &$query, $o_field, $order, $o_key, $order_by ) {
		global $wpdb;

		// Support Name (First) and Name (Last) sort options.
		if ( self::is_name_subfield_sort_option( $order_by ) || self::is_address_subfield_sort_option( $order_by ) ) {
			$show = explode( '_', $order_by )[1];
		} elseif ( 'address' === $o_field->type ) {
				$show = 'country';
		} else {
			// Name field.
			$show = 'first';
		}

		$query['select']  = str_replace(
			'FROM ' . $wpdb->prefix . 'frm_items it',
			', SUBSTRING_INDEX(
				SUBSTRING_INDEX(
					REPLACE(
						em' . $o_key . '.meta_value,
						SUBSTRING_INDEX( em' . $o_key . '.meta_value, \'"' . $show . '";s\', 1 ),
						""
					),
					";",
					2
				),
				":",
				-1
			) as `SubFieldValue' . $o_key . '`
			FROM ' . $wpdb->prefix . 'frm_items it',
			$query['select']
		);
		$query['select'] .= $wpdb->prepare( ' LEFT JOIN ' . $wpdb->prefix . 'frm_item_metas em' . $o_key . ' ON em' . $o_key . '.item_id=it.id AND em' . $o_key . '.field_id=%d ', $o_field->id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query['order']  .= '`SubFieldValue' . $o_key . '` ' . $order . ', ';
	}

	/**
	 * Get Views count.
	 *
	 * @since 6.x
	 *
	 * @return array
	 */
	public static function get_views_count() {
		$views_count = wp_count_posts( 'frm_display' );
		if ( ! isset( $views_count->private ) || ! isset( $views_count->publish ) ) {
			return 0;
		}
		return $views_count->private + $views_count->publish;
	}
}
