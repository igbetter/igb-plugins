<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsEditorController {

	/**
	 * @var int
	 */
	private static $view_id;

	/**
	 * @var WP_Post
	 */
	private static $view;

	/**
	 * @var array
	 *
	 * Holds the data from frm_options postmeta value
	 * Used in FrmViewsEditorController::get_data
	 */
	private static $options;

	/**
	 * @var object the field used for post_status.
	 */
	private static $status_field;

	private static $layout_helper;

	/**
	 * Load the view editor
	 * Called from ?admin.php?page=formidable-views-editor&view=...
	 */
	public static function view_editor() {
		$view_id = FrmAppHelper::get_param( 'view', 0, 'get', 'absint' );
		if ( ! $view_id ) {
			self::handle_invalid_view();
		}

		$view = get_post( $view_id );
		if ( ! $view || ! ( $view instanceof WP_Post ) || FrmViewsDisplaysController::$post_type !== $view->post_type ) {
			self::handle_invalid_view();
		}

		self::$view_id = $view->ID;

		// for wysiwyg.php frm_no_rt check.
		$view->frm_no_rt = self::get_option_value( 'no_rt' ) ? 1 : 0;
		if ( ! $view->frm_no_rt && self::is_legacy_table_type( $view_id ) ) {
			$view->frm_no_rt = 1;
		}

		$form_id = get_post_meta( $view_id, 'frm_form_id', true );
		if ( $form_id ) {
			$form = FrmForm::getOne( $form_id );
		}

		echo '<div class="frm_wrap">';
		echo '<div class="frm_page_container frm_forms">';
		if ( ! empty( $form ) ) {
			self::render_header( $form, $view );
		} else {
			self::render_publish_wrapper( $view );
		}

		$application_ids          = FrmViewsAppHelper::get_application_ids_for_view( $view_id );
		$editor_path              = FrmViewsAppHelper::views_path() . '/editor/';
		$show_count               = get_post_meta( $view_id, 'frm_show_count', true );
		$include_copy_option      = self::include_copy_option();
		$is_grid_type             = self::is_grid_type( $view );
		$show_education           = self::show_education( $is_grid_type ? 'grid' : 'classic' );
		$start_adding_content_url = admin_url( 'admin.php?page=formidable-entries&frm_action=new&form=0' );
		$active_preview_filter    = self::get_active_preview_filter( $view_id );
		$check_all_option         = in_array( $show_count, array( 'all', 'dynamic' ), true );
		$is_table_type            = self::is_table_type( $view );
		$is_map_type              = FrmViewsDisplaysHelper::is_map_type( $view );

		if ( $show_education ) {
			self::register_welcome_script();
		}

		self::add_form_name_sidebar_shortcode_option();

		self::register_view_editor_scripts_and_styles();
		FrmViewsAppHelper::add_view_embed_examples_script();

		/**
		 * Allow the Geolocation add-on to load hooks before the editor is printed.
		 *
		 * @since 5.6
		 *
		 * @param array $args
		 */
		do_action(
			'frm_views_editor_init',
			compact( 'view', 'is_grid_type', 'is_table_type', 'is_map_type' )
		);

		FrmAppHelper::include_svg();
		require $editor_path . 'editor.php';
		echo '</div>'; // End frm_page_container.
		echo '</div>'; // End frm_wrap.
	}

	/**
	 * Add a [form_name] shortcode option to the bottom of the Advanced "Customization" tab for easy access.
	 *
	 * @since 5.3.2
	 *
	 * @return void
	 */
	public static function add_form_name_sidebar_shortcode_option() {
		add_filter(
			'frm_helper_shortcodes',
			/**
			 * @param array<string,string> $shortcodes
			 * @return array<string,string>
			 */
			function ( $shortcodes ) {
				$shortcodes['form_name'] = __( 'Form Name', 'formidable' );
				return $shortcodes;
			}
		);
	}

	private static function register_welcome_script() {
		$version         = FrmViewsAppHelper::plugin_version();
		$welcome_js_path = FrmViewsAppHelper::plugin_url() . '/js/welcome.js';
		wp_register_script( 'formidable_views_editor_welcome', $welcome_js_path, array( 'wp-i18n' ), $version, true );
		wp_enqueue_script( 'formidable_views_editor_welcome' );
	}

	/**
	 * @param WP_Post $view
	 */
	private static function render_metaboxes( $view ) {
		do_action( 'add_meta_boxes', 'frm_display', $view );
		do_action( 'add_meta_boxes_frm_display', $view );
		global $wp_meta_boxes;
		$metaboxes = self::filter_and_flatten_metaboxes( $wp_meta_boxes['frm_display'], $view );

		if ( ! $metaboxes ) {
			return;
		}

		echo '<div id="frm_view_editor_metabox_wrapper">';
		echo '<form id="frm_view_editor_metabox_form">';
		echo '<div id="side-sortables">';
		array_walk(
			$metaboxes,
			function ( $metabox ) use ( $view ) {
				echo '<div id="' . esc_attr( $metabox['id'] ) . '" class="frm-view-editor-meta-box">';
					echo '<h3>' . esc_html( $metabox['title'] ) . '</h3>';
					echo '<div class="inside">';
						call_user_func( $metabox['callback'], $view, $metabox );
					echo '</div>';
				echo '</div>';
			}
		);
		echo '</div>';
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render the nav header with Builder, Settings, Entries options
	 *
	 * @param mixed  $form passed down to header.php view. if falsey nothing will be rendered.
	 * @param object $view
	 * @return void
	 */
	private static function render_header( $form, $view ) {
		if ( ! $form ) {
			return;
		}

		$_GET['show_nav'] = 1;
		$version          = FrmAppHelper::plugin_version();
		wp_register_style( 'formidable-admin', FrmAppHelper::plugin_url() . '/css/frm_admin.css', array(), $version );

		add_filter( 'frm_forms_dropdown', 'FrmViewsEditorController::disable_forms_dropdown' );
		require FrmViewsAppHelper::views_path() . '/editor/header.php';
		remove_filter( 'frm_forms_dropdown', 'FrmViewsEditorController::disable_forms_dropdown' );
	}

	/**
	 * To disable the forms dropdown on the view editor, hide all of the options by changing the $where filter to something that will always be empty (id = -1).
	 *
	 * @return array
	 */
	public static function disable_forms_dropdown() {
		return array(
			'id' => array( -1 ),
		);
	}

	private static function render_publish_wrapper( $view ) {
		$title = $view->post_title;
		require FrmViewsAppHelper::views_path() . '/editor/header_view_editor_publish_wrapper.php';
	}

	/**
	 * @return int 1 if we're including the copy option, or 0 if we are not.
	 */
	private static function include_copy_option() {
		return is_multisite() && current_user_can( 'setup_network' ) ? 1 : 0;
	}

	/**
	 * When a view id isn't passed, or the view can't be found / isn't a view, render an invalid view page
	 * Right now this page is just forcing a redirect from JavaScript to the index page
	 */
	private static function handle_invalid_view() {
		require FrmViewsAppHelper::views_path() . '/editor/invalid.php';
		wp_die();
	}

	private static function wysiwyg( $view ) {
		$editor_args = array();
		if ( $view->frm_no_rt ) {
			$editor_args['teeny']   = true;
			$editor_args['tinymce'] = false;
		}

		require FrmViewsAppHelper::views_path() . '/editor/wysiwyg.php';
	}

	private static function register_view_editor_scripts_and_styles() {
		// prevent undefined index: hook_suffix when calling set_current_screen()
		global $hook_suffix;

		// prevent class-wp-site-health from trying to get property 'id' of non-object.
		set_current_screen();

		// For third party plugin support, try to enqueue all scripts that normally get added when a post is being edited.
		try {
			do_action( 'admin_enqueue_scripts', 'edit.php' );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// For better stability, ignore any exception that happens when this action is triggered.
			// The Google Listing and Ads plugin by WooCommerce throws a InvalidAsset exception for example.
		}

		self::register_frontend_scripts();
		self::include_google_jsapi_for_google_graphs();

		FrmViewsAppHelper::add_modal_css();
		wp_enqueue_style( 'formidable' ); // register front-end formidable styles for a more accurate preview.
		wp_enqueue_style( 'wp-color-picker' ); // color pickers are used in style settings.

		self::register_editor_js();
		$version = FrmViewsAppHelper::plugin_version();
		wp_enqueue_style( 'formidable_views_index', FrmViewsAppHelper::plugin_url() . '/css/index.css', array(), $version ); // Include css for table fields modal.
		wp_register_style( 'formidable_views_editor', FrmViewsAppHelper::plugin_url() . '/css/editor.css', array(), $version );
		wp_enqueue_script( 'formidable_views_editor' );
		wp_enqueue_style( 'formidable_views_editor' );

		/**
		 * Trigger an action so other add-ons can add additional scripts.
		 * This is required for the Geolocation add-on to add scripts for map views.
		 *
		 * @since 5.6
		 *
		 * @param int $view_id
		 */
		do_action( 'frm_views_enqueue_editor_scripts', self::$view_id );
	}

	/**
	 * Register the (possibly minified) main editor JavaScript file.
	 */
	private static function register_editor_js() {
		$version         = FrmViewsAppHelper::plugin_version();
		$use_minified_js = FrmViewsAppHelper::use_minified_js_file();
		$editor_js_path  = FrmViewsAppHelper::plugin_url() . '/js/editor' . FrmViewsAppHelper::js_suffix() . '.js';

		if ( ! $use_minified_js ) {
			FrmViewsAppHelper::add_dom_script();
		}

		$js_dependencies = array( 'wp-i18n', 'wp-color-picker' );
		if ( class_exists( 'FrmApplicationsController' ) ) {
			$js_dependencies[] = 'formidable_dom';
		}

		wp_register_script( 'formidable_views_editor', $editor_js_path, $js_dependencies, $version, true );
	}

	/**
	 * In order to access frmProFormJS().loadGoogle() to preview frm-graph shortcodes we need to load formidablepro.js.
	 */
	private static function register_frontend_scripts() {
		FrmProAppController::register_scripts();
		wp_enqueue_script( 'formidable' );
		wp_enqueue_script( 'formidablepro' );
	}

	/**
	 * In order to load frm-graph shortcodes we need to load teh google graphs jsapi.
	 */
	private static function include_google_jsapi_for_google_graphs() {
		wp_enqueue_script( 'google_jsapi', 'https://www.gstatic.com/charts/loader.js', array(), FrmAppHelper::plugin_version() );
	}

	/**
	 * Check for formidable views AJAX requests and handle routing
	 */
	public static function route_ajax() {
		$action = str_replace( 'wp_ajax_', '', current_action() );

		$function = false;
		switch ( $action ) {
			case 'frm_views_process_box_preview':
				FrmViewsPreviewController::route_ajax( $action );
				break;

			case 'frm_views_editor_get_data':
				$function = 'get_data';
				break;

			case 'frm_views_editor_create':
				$function = 'create_view';
				break;

			case 'frm_views_get_table_column_options':
				$function = 'get_table_column_options';
				break;

			case 'frm_views_get_map_view_options':
				$function = 'get_map_view_options';
				break;

			case 'frm_views_get_calendar_view_options':
				$function = 'get_calendar_view_options';
				break;

			case 'frm_save_view_layout_template':
				$function = 'save_layout_template';
				break;

			case 'frm_views_editor_update':
				$function = 'update_view';
				break;

			case 'frm_views_editor_info':
				$function = 'pull_form_info';
				break;

			case 'frm_update_layout_template':
				$function = 'update_layout_template';
				break;

			case 'frm_delete_layout_template':
				$function = 'delete_layout_template';
				break;

			case 'frm_dismiss_coming_soon_message':
				$function = 'dismiss_coming_soon_message';
				break;

			case 'frm_flatten_view':
				$function = 'flatten_view';
				break;

			case 'frm_view_dropdown_options':
				$function = 'get_dropdown_options';
				break;
		}

		if ( $function ) {
			self::verify_and_continue_to_function( $function );
		}
	}

	private static function verify_and_continue_to_function( $function ) {
		FrmAppHelper::permission_check( 'frm_edit_displays' );
		check_ajax_referer( 'frm_ajax', 'nonce' );
		self::$function();
	}

	private static function get_data() {
		self::$view_id = FrmAppHelper::get_param( 'view', 0, 'post', 'absint' );
		if ( ! self::$view_id ) {
			wp_send_json_error();
		}

		self::$view = get_post( self::$view_id );
		if ( ! self::$view ) {
			wp_send_json_error();
		}

		$is_grid_type    = self::is_grid_type( self::$view );
		$is_table_type   = self::is_table_type( self::$view );
		$layout_helper   = new FrmViewsLayoutHelper( self::$view );
		$listing_content = self::get_listing_content();
		$detail_content  = self::get_detail_content();

		if ( $is_grid_type || $is_table_type ) {
			$listing_layout = is_array( $listing_content ) ? $layout_helper->get_layout_data( 'listing' ) : '';
			$detail_layout  = is_array( $detail_content ) ? $layout_helper->get_layout_data( 'detail' ) : '';
		} else {
			$listing_layout = '';
			$detail_layout  = '';

			// flatten data in case it might be saved as grid format and it no longer is a grid.
			if ( is_array( $listing_content ) ) {
				$listing_content = $layout_helper->flatten( $listing_content, 'listing' );
			}
			if ( is_array( $detail_content ) ) {
				$detail_content = $layout_helper->flatten( $detail_content, 'detail' );
			}
		}

		$response_data = array(
			'emptyMessage'         => self::get_option_value( 'empty_msg' ),
			'limit'                => self::get_option_value( 'limit' ),
			'pageSize'             => self::get_option_value( 'page_size' ),
			'offset'               => self::get_option_value( 'offset' ),
			'filterEntries'        => self::get_filter_entries_data_for_frontend(),
			'sortEntries'          => self::get_sort_entries_data_for_frontend(),
			'listingLayout'        => $listing_layout,
			'detailLayout'         => $detail_layout,
			'listingContent'       => $listing_content,
			'detailContent'        => $detail_content,
			'listingBeforeContent' => self::get_option_value( 'before_content' ),
			'listingAfterContent'  => self::get_option_value( 'after_content' ),
			'detailSlug'           => self::get_detail_slug(),
			'parameterValue'       => self::get_parameter_value(),
			'copy'                 => self::get_option_value( 'copy' ),
			'ajaxRefresh'          => self::get_option_value( 'ajax_refresh' ),
			'disablePreview'       => self::get_option_value( 'no_rt' ),
			'ajaxPagination'       => self::get_option_value( 'ajax_pagination' ),
		);
		if ( 'calendar' === get_post_meta( self::$view_id, 'frm_show_count', true ) ) {
			$response_data = array_merge(
				$response_data,
				array(
					'fieldOptions'                  => self::get_field_options(),
					'dateFieldId'                   => self::get_option_value( 'date_field_id' ),
					'edateFieldId'                  => self::get_option_value( 'edate_field_id' ),
					'repeatEventFieldId'            => self::get_option_value( 'repeat_event_field_id' ),
					'repeatEdateFieldId'            => self::get_option_value( 'repeat_edate_field_id' ),
					'dismissedCalendarComingSoon'   => self::get_dismissed_coming_soon( 'calendar' ),
					'calendarEventPopup'            => self::get_option_value( 'calendar_event_popup', 1 ),
					'calendarEventPopupTitle'       => self::get_calendar_option( 'title' ),
					'calendarEventPopupDescription' => self::get_calendar_option( 'description' ),
					'calendarEventPopupThumbnail'   => self::get_calendar_option( 'thumbnail' ),
					'calendarEventPopupTime'        => self::get_calendar_option( 'time' ),
				)
			);
		} elseif ( self::is_table_type( self::$view ) ) {
			$response_data = array_merge(
				$response_data,
				array(
					'tableRowStyle'   => self::get_option_value( 'table_row_style', 'frm-alt-table' ),
					'tableResponsive' => self::get_option_value( 'table_responsive', 0 ),
					'customClasses'   => self::get_option_value( 'table_classes' ),
				)
			);
		} elseif ( self::is_grid_type( self::$view ) ) {
			$response_data = array_merge(
				$response_data,
				array(
					'gridColumnCount' => self::get_option_value( 'grid_column_count', 1 ),
					'gridRowGap'      => self::get_option_value( 'grid_row_gap', '20' ),
					'gridColumnGap'   => self::get_option_value( 'grid_column_gap', '2' ),
					'customClasses'   => self::get_option_value( 'grid_classes' ),
					'gridResponsive'  => self::get_option_value( 'grid_responsive', 0 ),
				)
			);
		}

		/**
		 * @since 5.6
		 *
		 * @param array  $response_data
		 * @param object $view
		 */
		$response_data = apply_filters(
			'frm_views_editor_data_processor_response_data',
			$response_data,
			self::$view,
			/**
			 * Pass an anonymous function to make self::get_option_value callable via hook.
			 *
			 * @param string $key
			 * @param mixed  $default
			 * @return mixed
			 */
			function ( $key, $default = '' ) {
				return self::get_option_value( $key, $default );
			}
		);

		wp_send_json_success( $response_data );
	}

	/**
	 * @since 5.4.2
	 *
	 * @return void
	 */
	public static function check_license() {
		if ( FrmViewsAppController::is_expired_outside_grace_period() ) {
			wp_die( esc_html__( 'Your account license has expired. In order to access more Pro features, please renew your subscription.', 'formidable-views' ) );
		}
	}

	private static function create_view() {
		self::check_license();

		$form_id = FrmAppHelper::get_param( 'form', 0, 'post', 'absint' );
		$type    = FrmAppHelper::get_param( 'type', 'all', 'post', 'sanitize_key' );
		$name    = FrmAppHelper::get_param( 'name', '', 'post', 'sanitize_text_field' );
		$view_id = FrmViewsDisplay::create( $form_id, $type, $name );

		self::maybe_add_application_ids_to_view( $view_id, $form_id );

		$redirect      = FrmViewsAppHelper::get_url_to_view_editor( $view_id );
		$response_data = array(
			'redirect' => esc_url_raw( $redirect ),
		);
		wp_send_json_success( $response_data );
	}

	/**
	 * Maybe add application ids to view on create.
	 *
	 * @since 5.2
	 * @param int $view_id
	 * @param int $form_id
	 * @return void
	 */
	private static function maybe_add_application_ids_to_view( $view_id, $form_id ) {
		if ( ! is_callable( 'FrmProApplication::add_post_to_application' ) ) {
			return;
		}

		$application_id = FrmAppHelper::get_post_param( 'application_id', 0, 'absint' );
		if ( $application_id ) {
			FrmProApplication::add_post_to_application( $application_id, $view_id, 'view' );
			self::add_application_to_form_if_relation_is_missing( $application_id, $form_id );
		}

		if ( ! $form_id || ! is_callable( 'FrmProApplicationsHelper::get_application_ids_for_form' ) ) {
			return;
		}

		$form_application_ids = FrmProApplicationsHelper::get_application_ids_for_form( $form_id );
		foreach ( $form_application_ids as $form_application_id ) {
			if ( $application_id && $application_id === $form_application_id ) {
				// Avoid adding the set application id twice if it's also assigned to the form.
				continue;
			}
			FrmProApplication::add_post_to_application( $form_application_id, $view_id, 'view' );
		}
	}

	/**
	 * @param int $application_id
	 * @param int $form_id
	 * @return void
	 */
	private static function add_application_to_form_if_relation_is_missing( $application_id, $form_id ) {
		if ( ! $form_id ) {
			return;
		}

		$current_form_ids = array_map( 'intval', get_term_meta( $application_id, '_frm_form_id' ) );
		if ( in_array( $form_id, $current_form_ids, true ) ) {
			return;
		}

		FrmProApplication::add_form_to_application( $application_id, $form_id );
	}

	private static function get_calendar_view_options() {
		$form_id = FrmAppHelper::get_param( 'form', '', 'post', 'absint' );
		if ( ! $form_id ) {
			wp_send_json_error();
		}

		wp_send_json_success( self::get_field_options( $form_id ) );
	}

	private static function get_table_column_options() {
		$form_id = FrmAppHelper::get_param( 'form', '', 'post', 'absint' );

		if ( ! $form_id ) {
			wp_send_json_error();
		}

		$options = array();
		$fields  = FrmField::get_all_for_form( (int) $form_id, '', 'exclude', 'exclude' );

		/**
		 * Allows modifying the list of fields in the Create view popup.
		 *
		 * @since 5.1.04
		 *
		 * @param array $fields The array of fields.
		 * @param array $args   Includes `form_id`.
		 */
		$fields        = apply_filters( 'frm_views_fields_in_create_view_popup', $fields, compact( 'form_id' ) );
		$exclude_types = FrmField::no_save_fields();
		foreach ( $fields as $field ) {
			if ( in_array( $field->type, $exclude_types, true ) || FrmProField::is_list_field( $field ) ) {
				continue;
			}

			$options[] = array(
				'value' => $field->id,
				'label' => FrmAppHelper::truncate( $field->name, 50 ),
			);

			$may_include_dynamically_linked_field_options = 'data' === $field->type && ! empty( $field->field_options['form_select'] ) && is_numeric( $field->field_options['form_select'] );
			if ( ! $may_include_dynamically_linked_field_options ) {
				continue;
			}

			$linked_form_id = FrmDb::get_var( 'frm_fields', array( 'id' => $field->field_options['form_select'] ), 'form_id' );
			$linked_fields  = FrmField::getAll(
				array(
					'fi.type not' => FrmField::no_save_fields(),
					'fi.form_id'  => $linked_form_id,
				)
			);

			if ( $linked_fields ) {
				$linked_form = FrmForm::getOne( $linked_form_id );
				foreach ( $linked_fields as $linked_field ) {
					$options[] = array(
						'value' => $field->id . ':' . $linked_field->id,
						'label' => FrmAppHelper::truncate( $linked_form->name . ': ' . $linked_field->name, 50 ),
					);
				}
			}
		}

		$options = array_merge(
			$options,
			array_map(
				function ( $key ) {
					return array(
						'value' => $key,
						'label' => FrmViewsDisplay::convert_table_option_key_to_label( $key ),
					);
				},
				array( 'created_at', 'updated_at', 'id', 'item_key', 'post_id', 'is_draft' )
			)
		);

		wp_send_json_success(
			array(
				'options' => $options,
			)
		);
	}

	private static function get_map_view_options() {
		$form_id = FrmAppHelper::get_param( 'form', '', 'post', 'absint' );

		if ( ! $form_id ) {
			wp_send_json_error();
		}

		$address_field_options = self::get_address_field_options( $form_id );
		wp_send_json_success(
			array(
				'addressFieldOptions' => $address_field_options,
			)
		);
	}

	private static function get_listing_content() {
		return self::try_to_decode_content( self::$view->post_content );
	}

	private static function get_detail_content() {
		return self::try_to_decode_content( self::$view->frm_dyncontent );
	}

	/**
	 * Old data is just a string. If the decoded result isn't an array, return the raw content
	 *
	 * @param string $content
	 * @return array|string
	 */
	private static function try_to_decode_content( $content ) {
		$helper = new FrmViewsContentHelper( $content );
		return $helper->get_content();
	}

	/**
	 * @return array
	 */
	private static function get_filter_entries_data_for_frontend() {
		$where = self::get_option_value( 'where' );
		if ( ! $where ) {
			return array();
		}

		$data         = array();
		$where_is     = self::get_option_value( 'where_is' );
		$where_val    = self::get_option_value( 'where_val' );
		$or_val       = self::get_option_value( 'where_or' );
		$group_val    = self::get_option_value( 'where_group' );
		$group_or_val = self::get_option_value( 'where_group_or' );

		foreach ( $where as $index => $condition ) {
			$data[] = array(
				'where'    => $condition,
				'is'       => $where_is[ $index ],
				'val'      => $where_val[ $index ],
				'or'       => ! empty( $or_val[ $index ] ) ? 1 : 0,
				'group'    => ! empty( $group_val[ $index ] ) ? $group_val[ $index ] : 0,
				'group_or' => ! empty( $group_or_val[ $index ] ) ? 1 : 0,
			);
		}

		return $data;
	}

	/**
	 * @return array
	 */
	private static function get_sort_entries_data_for_frontend() {
		$order = self::get_option_value( 'order' );
		if ( ! $order ) {
			return array();
		}

		$data      = array();
		$order_by  = self::get_option_value( 'order_by' );
		$field_ids = array_filter( $order_by, 'is_numeric' );

		if ( $field_ids ) {
			$field_ids = FrmDb::get_col( 'frm_fields', array( 'id' => $field_ids ), 'id' );
			$field_ids = array_map( 'intval', $field_ids );
		}

		foreach ( $order_by as $index => $order_by_value ) {
			if ( ! is_numeric( $order_by_value ) || in_array( (int) $order_by_value, $field_ids, true ) ) {
				$data[] = array(
					'order' => $order[ $index ],
					'by'    => $order_by_value,
				);
			}
		}

		return $data;
	}

	/**
	 * Get value at key from frm_options post meta if applicable.
	 *
	 * @param string $key
	 * @param string $default the default value to return back when an option is not found.
	 * @return string
	 */
	private static function get_option_value( $key, $default = '' ) {
		if ( ! isset( self::$options ) ) {
			self::$options = get_post_meta( self::$view_id, 'frm_options', true );
			FrmAppHelper::unserialize_or_decode( self::$options );
		}
		if ( is_array( self::$options ) && array_key_exists( $key, self::$options ) ) {
			return self::$options[ $key ];
		}
		return $default;
	}

	/**
	 * Get value at "name" key from calendar_options object.
	 *
	 * @param string $key
	 * @param string $default the default value to return back when an option is not found.
	 * @return string
	 */
	private static function get_calendar_option( $key, $default = '' ) {
		$options = self::get_option_value( 'calendar_options' );

		if ( empty( $options ) ) {
			return '';
		}

		foreach ( $options as $option ) {
			if ( $option['name'] === $key ) {
				return (string) $option['value'];
			}
		}

		return $default;
	}

	/**
	 * Create a new layout template on save.
	 * Since you can't really edit a layout template a new one is always saved.
	 */
	private static function save_layout_template() {
		$name = FrmAppHelper::get_param( 'name', '', 'post', 'sanitize_text_field' );
		$data = FrmAppHelper::get_param( 'data', '', 'post' ); // this is sanitized in FrmViewsLayout::create_template

		if ( FrmViewsLayout::create_template( $name, $data ) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	public static function publish_button() {
		include FrmViewsAppHelper::plugin_path() . '/classes/views/editor/publish_button.php';
	}

	private static function update_view() {
		$view_id = FrmAppHelper::get_param( 'id', '', 'post', 'absint' );
		if ( ! $view_id ) {
			wp_send_json_error();
		}

		$view = get_post( $view_id );
		if ( ! $view ) {
			wp_send_json_error();
		}

		if ( 'frm_display' !== $view->post_type ) {
			wp_send_json_error();
		}

		$name                        = FrmAppHelper::get_param( 'name', '', 'post', 'sanitize_text_field' );
		$application_ids             = FrmAppHelper::get_param( 'applicationIds', '', 'post' );
		$post_name                   = FrmAppHelper::get_param( 'viewKey', '', 'post', 'sanitize_text_field' );
		$form_id                     = FrmAppHelper::get_param( 'formId', 0, 'post', 'absint' );
		$empty_message               = FrmAppHelper::get_param( 'emptyMessage', '', 'post' );
		$limit                       = FrmAppHelper::get_param( 'limit', '', 'post', 'sanitize_text_field' );
		$page_size                   = FrmAppHelper::get_param( 'pageSize', '', 'post', 'sanitize_text_field' );
		$offset                      = FrmAppHelper::get_param( 'offset', '', 'post', 'sanitize_text_field' );
		$listing_layout              = FrmAppHelper::get_param( 'listingLayout', '', 'post' );
		$detail_layout               = FrmAppHelper::get_param( 'detailLayout', '', 'post' );
		$listing_content             = self::sanitize_content( FrmAppHelper::get_param( 'listingContent', '', 'post' ) );
		$detail_content              = self::sanitize_content( FrmAppHelper::get_param( 'detailContent', '', 'post' ) );
		$listing_before_content      = FrmAppHelper::get_param( 'listingBeforeContent', '', 'post' );
		$listing_after_content       = FrmAppHelper::get_param( 'listingAfterContent', '', 'post' );
		$filter_entries              = FrmViewsPreviewHelper::sanitize_filter( FrmAppHelper::get_param( 'filterEntries', '', 'post' ) );
		$sort_entries                = FrmViewsPreviewHelper::sanitize_sort( FrmAppHelper::get_param( 'sortEntries', '', 'post' ) );
		$show_count                  = FrmAppHelper::get_param( 'showCount', '', 'post', 'sanitize_text_field' );
		$detail_slug                 = FrmAppHelper::get_param( 'detailSlug', '', 'post', 'sanitize_text_field' );
		$parameter_value             = FrmAppHelper::get_param( 'parameterValue', '', 'post', 'sanitize_text_field' );
		$date_field_id               = FrmAppHelper::get_param( 'dateFieldId', '', 'post', 'sanitize_text_field' );
		$edate_field_id              = FrmAppHelper::get_param( 'edateFieldId', '', 'post', 'sanitize_text_field' );
		$repeat_event_field_id       = FrmAppHelper::get_param( 'repeatEventFieldId', '', 'post', 'sanitize_text_field' );
		$repeat_edate_field_id       = FrmAppHelper::get_param( 'repeatEdateFieldId', '', 'post', 'sanitize_text_field' );
		$ajax_refresh                = FrmAppHelper::get_param( 'ajaxRefresh', '', 'post', 'absint' );
		$disable_preview             = FrmAppHelper::get_param( 'disablePreview', 0, 'post', 'absint' );
		$ajax_pagination             = $page_size ? FrmAppHelper::get_param( 'ajaxPagination', '0', 'post', 'sanitize_text_field' ) : '0';
		$active_preview_filter       = FrmAppHelper::get_param( 'activePreviewFilter', '', 'post', 'sanitize_text_field' );
		$metabox_data                = self::parse_metabox_data( FrmAppHelper::get_param( 'metaboxData', '', 'post' ) );
		$is_grid_type                = FrmAppHelper::get_param( 'isGridType', 0, 'post', 'absint' );
		$is_table_type               = FrmAppHelper::get_param( 'isTableType', 0, 'post', 'absint' );
		$calendar_event_popup        = FrmAppHelper::get_param( 'calendarEventPopup', '', 'post', 'sanitize_text_field' );
		$calendar_epopup_title       = FrmAppHelper::get_param( 'calendarEventPopupTitle', '', 'post', 'sanitize_text_field' );
		$calendar_epopup_description = FrmAppHelper::get_param( 'calendarEventPopupDescription', '', 'post', 'sanitize_text_field' );
		$calendar_epopup_time        = FrmAppHelper::get_param( 'calendarEventPopupTime', '', 'post', 'sanitize_text_field' );
		$calendar_epopup_thumbnail   = FrmAppHelper::get_param( 'calendarEventPopupThumbnail', '', 'post', 'sanitize_text_field' );

		if ( ! in_array( $ajax_pagination, array( '1', '0', 'load-more', 'infinite-scroll' ), true ) ) {
			$ajax_pagination = '0';
		}

		$listing_page_is_json_type = $is_grid_type || $is_table_type;
		if ( ! $listing_page_is_json_type ) {
			$listing_content = self::guarantee_content_is_a_string( $listing_content );
		}

		$detail_page_is_json_type = $is_grid_type;
		if ( ! $detail_page_is_json_type ) {
			$detail_content = self::guarantee_content_is_a_string( $detail_content );
		}

		$post = array_merge(
			(array) $view,
			array(
				'ID'           => $view_id,
				'post_title'   => $name,
				'post_content' => self::maybe_encode_json_content( $listing_content ),
				'post_name'    => $post_name,
			)
		);
		if ( 'draft' === $view->post_status ) {
			$post['post_status'] = 'private';
		}
		FrmDb::save_json_post( $post );

		$options = array(
			'date_field_id'         => $date_field_id,
			'edate_field_id'        => $edate_field_id,
			'repeat_event_field_id' => $repeat_event_field_id,
			'repeat_edate_field_id' => $repeat_edate_field_id,
			'before_content'        => $listing_before_content,
			'after_content'         => $listing_after_content,
			'empty_msg'             => $empty_message,
			'limit'                 => $limit ? $limit : '',
			'page_size'             => $page_size ? $page_size : '',
			'offset'                => $offset ? $offset : '',
			'no_rt'                 => $disable_preview ? 1 : 0,
			'ajax_pagination'       => $ajax_pagination,
			'calendar_event_popup'  => $calendar_event_popup,
			'calendar_options'      => array(
				array(
					'name'  => 'title',
					'value' => $calendar_epopup_title,
				),
				array(
					'name'  => 'description',
					'value' => $calendar_epopup_description,
				),
				array(
					'name'  => 'time',
					'value' => $calendar_epopup_time,
				),
				array(
					'name'  => 'thumbnail',
					'value' => $calendar_epopup_thumbnail,
				),
			),
			'ajax_refresh'          => $ajax_refresh ? 1 : 0,
		);

		if ( self::include_copy_option() ) {
			$copy            = FrmAppHelper::get_param( 'copy', '', 'post', 'absint' );
			$options['copy'] = $copy ? 1 : 0;
		}
		$where_options = self::format_where_options_for_update( $filter_entries );
		if ( ! empty( $where_options['where'] ) ) {
			$options = array_merge( $options, $where_options );
		}
		$sort_options = self::format_sort_options_for_update( $sort_entries );
		if ( ! empty( $sort_options['order_by'] ) ) {
			$options = array_merge( $options, $sort_options );
		}
		if ( ! empty( $metabox_data['options'] ) ) {
			if ( isset( $metabox_data['options']['view_export_possible'] ) ) {
				if ( $is_table_type ) {
					$metabox_data['options']['view_export_possible'] = 1;
				} else {
					$metabox_data['options']['view_export_possible'] = FrmViewsDisplaysHelper::check_view_data_for_table_type( $show_count, $listing_before_content );
				}
			}
			$options = array_merge( $options, $metabox_data['options'] );
		}
		if ( $is_grid_type ) {
			$options['grid_column_count'] = FrmAppHelper::get_param( 'gridColumnCount', 1, 'post', 'absint' );
			$options['grid_row_gap']      = FrmAppHelper::get_param( 'gridRowGap', '20', 'post', 'sanitize_text_field' );
			$options['grid_column_gap']   = FrmAppHelper::get_param( 'gridColumnGap', '2', 'post', 'sanitize_text_field' );
			$options['grid_classes']      = FrmAppHelper::get_param( 'customClasses', '', 'post', 'sanitize_text_field' );
			$options['grid_responsive']   = FrmAppHelper::get_param( 'gridResponsive', 1, 'post', 'absint' );
		} elseif ( $is_table_type ) {
			$options['table_row_style']  = FrmAppHelper::get_param( 'tableRowStyle', '', 'post', 'sanitize_text_field' );
			$options['table_responsive'] = FrmAppHelper::get_param( 'tableResponsive', 1, 'post', 'absint' );
			$options['table_classes']    = FrmAppHelper::get_param( 'customClasses', '', 'post', 'sanitize_text_field' );
		}

		/**
		 * @since 5.6
		 *
		 * @param array   $options
		 * @param WP_Post $view
		 */
		$options = apply_filters( 'frm_views_editor_options_before_update', $options, $view );

		$data = array(
			'dyncontent'            => self::maybe_encode_json_content( $detail_content ),
			'param'                 => $detail_slug ? $detail_slug : 'entry',
			'type'                  => $parameter_value ? $parameter_value : 'id',
			'show_count'            => $show_count,
			'active_preview_filter' => $active_preview_filter,
			'options'               => $options,
			'form_id'               => $form_id ? $form_id : '',
		);
		FrmViewsDisplay::update( $view_id, $data );

		if ( ! $is_grid_type && '1' === get_post_meta( $view->ID, 'frm_grid_view', true ) ) {
			delete_post_meta( $view->ID, 'frm_grid_view' );
			delete_post_meta( $view->ID, 'frm_view_type' );
		}

		if ( ! $is_table_type && '1' === get_post_meta( $view->ID, 'frm_table_view', true ) ) {
			delete_post_meta( $view->ID, 'frm_table_view' );
			delete_post_meta( $view->ID, 'frm_view_type' );
		}

		FrmViewsLayout::maybe_create_layouts_for_view( $view_id, $listing_layout, $detail_layout );

		if ( ! empty( $metabox_data['acf'] ) && function_exists( 'acf_save_post' ) ) {
			acf_save_post( $view_id, $metabox_data['acf'] );
		}

		self::sync_application_data( $view_id, $application_ids );

		$response_data = array();

		if ( ! $view->post_name || '' === $post_name ) {
			$response_data['viewKey'] = FrmDb::get_var( 'posts', array( 'ID' => $view_id ), 'post_name' );
		}

		self::prevent_tooltips_in_future_sessions( $is_grid_type ? 'grid' : 'classic' );

		/**
		 * Trigger frm_create_display so views successfully copy.
		 *
		 * @param int   $view_id
		 * @param array $data
		 */
		do_action( 'frm_create_display', $view_id, $data );

		wp_send_json_success( $response_data );
	}

	/**
	 * @param array|string $content
	 * @return string
	 */
	private static function maybe_encode_json_content( $content ) {
		return is_array( $content ) ? FrmAppHelper::prepare_and_encode( $content ) : $content;
	}

	private static function format_where_options_for_update( $filter_entries ) {
		$where                  = array();
		$where_is               = array();
		$where_val              = array();
		$where_or               = array();
		$where_group            = array();
		$where_group_or         = array();
		$valid_where_is_options = array_keys( FrmViewsDisplaysHelper::where_is_options() );

		if ( is_array( $filter_entries ) ) {
			foreach ( $filter_entries as $rule ) {
				$where[]          = $rule['where'];
				$where_is[]       = $rule['is'];
				$where_val[]      = $rule['val'];
				$where_or[]       = ! empty( $rule['or'] ) ? 1 : 0;
				$where_group[]    = ! empty( $rule['group'] ) ? $rule['group'] : 0;
				$where_group_or[] = ! empty( $rule['group_or'] ) ? 1 : 0;
			}
		}

		return compact( 'where', 'where_is', 'where_val', 'where_or', 'where_group', 'where_group_or' );
	}

	private static function format_sort_options_for_update( $sort_entries ) {
		$order_by = array();
		$order    = array();
		if ( is_array( $sort_entries ) ) {
			$valid_order_options = array( 'ASC', 'DESC' );
			foreach ( $sort_entries as $rule ) {
				if ( ! in_array( $rule['order'], $valid_order_options, true ) ) {
					continue;
				}

				$order_by[] = sanitize_key( $rule['by'] );
				$order[]    = $rule['order'];
			}
		}
		return compact( 'order_by', 'order' );
	}

	/**
	 * @param mixed $content
	 */
	private static function sanitize_content( $content ) {
		if ( ! is_array( $content ) ) {
			return $content;
		}

		$sanitized         = array();
		$processed_box_ids = array();
		foreach ( $content as $box_data ) {
			if ( ! isset( $box_data['box'] ) || ! isset( $box_data['content'] ) ) {
				continue;
			}
			$box_id = absint( $box_data['box'] );
			if ( isset( $processed_box_ids[ $box_id ] ) ) {
				// avoid ever saving the same box id twice by accident.
				continue;
			}
			$sanitized_box_data = array(
				'box'     => $box_id,
				'content' => self::replace_quot_entity( $box_data['content'] ),
			);
			if ( ! empty( $box_data['style'] ) ) {
				$sanitized_box_data['style'] = self::sanitize_style( $box_data['style'] );
			}
			if ( ! empty( $box_data['name'] ) ) {
				$sanitized_box_data['name'] = self::replace_quot_entity( FrmAppHelper::kses( $box_data['name'], 'all' ) );
			}
			if ( ! empty( $box_data['detailsLink'] ) ) {
				$sanitized_box_data['detailsLink'] = 1;
			}

			$sanitized[]                  = $sanitized_box_data;
			$processed_box_ids[ $box_id ] = $box_id;
		}

		return $sanitized;
	}

	private static function replace_quot_entity( $string ) {
		return str_replace( '&quot;', '"', $string );
	}

	/**
	 * @param array $style
	 * @return array
	 */
	private static function sanitize_style( $style ) {
		$sanitized_style = array();
		foreach ( $style as $key => $value ) {
			$sanitized_style[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
		}
		return $sanitized_style;
	}

	public static function insert_form_popup() {
		if ( ! self::should_insert_form_popup() ) {
			return;
		}

		FrmAppHelper::load_admin_wide_js();

		$shortcodes = array(
			'formidable' => array(
				'name'  => __( 'Form', 'formidable' ),
				'label' => __( 'Insert a Form', 'formidable' ),
			),
		);
		$shortcodes = apply_filters( 'frm_popup_shortcodes', $shortcodes );

		include FrmAppHelper::plugin_path() . '/classes/views/frm-forms/insert_form_popup.php';
	}

	/**
	 * @return bool true if the active page should include the insert_form_popup.
	 */
	private static function should_insert_form_popup() {
		return FrmViewsAppHelper::view_editor_is_active();
	}

	/**
	 * Render the <option> tags for Name (First) and Address (Country) types of sort options.
	 *
	 * @since 5.5
	 *
	 * @param int                  $field_id
	 * @param string               $field_name
	 * @param array<string,string> $subfields
	 * @param bool                 $include_sort_option_class
	 * @return void
	 */
	public static function render_subfield_sort_options( $field_id, $field_name, $subfields, $include_sort_option_class = true ) {
		$truncated_field_name = FrmAppHelper::truncate( $field_name, 50 );
		foreach ( $subfields as $type => $label ) {
			$attributes = array(
				'value' => $field_id . '_' . $type,
			);
			if ( $include_sort_option_class ) {
				$attributes['class'] = 'frm-views-sort-option';
			}
			FrmHtmlHelper::echo_dropdown_option( $truncated_field_name . ' (' . $label . ')', false, $attributes );
		}
	}

	/**
	 * Render <option> tags used for the filter and sort view modals.
	 *
	 * @param int $form_id
	 * @return void
	 */
	public static function render_field_select_template_options( $form_id ) {
		include FrmViewsAppHelper::plugin_path() . '/classes/views/editor/field_select_template_options.php';
	}

	private static function pull_form_info() {
		ob_start();
		$form_id = FrmAppHelper::get_param( 'form', 0, 'post', 'absint' );
		self::render_field_select_template_options( $form_id );
		$html = ob_get_contents();
		ob_end_clean();
		$response_data = array(
			'fieldSelectTemplateOptions' => $html,
			'dateFieldIds'               => self::get_date_field_ids( $form_id ),
			'draftDropdownOptions'       => self::get_draft_dropdown_options(),
			'statusDropdownOptions'      => self::get_status_dropdown_options( $form_id ),
			'addressFieldOptions'        => self::get_address_field_options( $form_id ),
			'statusFieldId'              => self::get_status_field_id(),
		);
		wp_send_json_success( $response_data );
	}

	private static function update_layout_template() {
		$id   = FrmAppHelper::get_param( 'id', '', 'post', 'absint' );
		$data = FrmAppHelper::get_param( 'data', '', 'post' );
		$data = FrmViewsLayout::prepare_layout_data_for_save( $data );
		FrmViewsLayout::update_layout( $id, $data );
		wp_send_json_success();
	}

	private static function delete_layout_template() {
		$id = FrmAppHelper::get_param( 'id', '', 'post', 'absint' );
		FrmViewsLayout::delete_layout( $id );
		wp_send_json_success();
	}

	/**
	 * @return string
	 */
	private static function get_detail_slug() {
		$slug = get_post_meta( self::$view_id, 'frm_param', true );
		if ( ! $slug ) {
			$slug = 'entry';
		}
		return $slug;
	}

	/**
	 * @return string
	 */
	private static function get_parameter_value() {
		$value = get_post_meta( self::$view_id, 'frm_type', true );
		if ( ! $value ) {
			$value = 'id';
		}
		return $value;
	}

	/**
	 * Get the data required to pull field options for calendar.
	 *
	 * @param int|null $form_id The form ID.
	 *
	 * @return array
	 */
	private static function get_field_options( $form_id = null ) {
		if ( null === $form_id ) {
			$form_id = get_post_meta( self::$view_id, 'frm_form_id', true );
		}
		$fields = FrmField::get_all_for_form( (int) $form_id, '', 'exclude', 'exclude' );

		if ( ! $fields ) {
			return array();
		}

		$types = array(
			'date',
			'number',
			'select',
			'radio',
			'scale',
			'star',
			'text',
			'textarea',
			'file',
			'time',
		);

		$options = array();
		foreach ( $fields as $field ) {
			if ( ! in_array( $field->type, $types, true ) || FrmProField::is_list_field( $field ) ) {
				continue;
			}
			$options[] = array(
				'value' => $field->id,
				'type'  => $field->type,
				'label' => FrmAppHelper::truncate( $field->name, 50 ),
			);
		}

		return $options;
	}

	/**
	 * Update application relation data for view.
	 *
	 * @param int   $view_id
	 * @param mixed $application_ids
	 * @return void
	 */
	private static function sync_application_data( $view_id, $application_ids ) {
		if ( ! taxonomy_exists( 'frm_application' ) || ! is_callable( 'FrmProApplication::add_post_to_application' ) ) {
			return;
		}

		$application_ids          = is_array( $application_ids ) ? array_map( 'intval', $application_ids ) : array();
		$previous_application_ids = FrmViewsAppHelper::get_application_ids_for_view( $view_id );

		$new_application_ids = array_diff( $application_ids, $previous_application_ids );
		array_walk(
			$new_application_ids,
			function ( $application_id ) use ( $view_id ) {
				FrmProApplication::add_post_to_application( absint( $application_id ), $view_id, 'view' );
			}
		);

		$old_application_ids = array_diff( $previous_application_ids, $application_ids );
		array_walk(
			$old_application_ids,
			function ( $application_id ) use ( $view_id ) {
				FrmProApplication::remove_post_from_application( absint( $application_id ), $view_id, 'view' );
			}
		);
	}

	/**
	 * @param string $type
	 * @return int 1 or 0.
	 */
	private static function show_education( $type ) {
		$meta = get_user_meta( get_current_user_id(), 'frm_views_tried_the_new_editor', true );
		if ( ! is_array( $meta ) ) {
			return 1;
		}
		return in_array( $type, $meta, true ) ? 0 : 1;
	}

	/**
	 * @param string $type
	 */
	private static function prevent_tooltips_in_future_sessions( $type ) {
		$user_id    = get_current_user_id();
		$meta_key   = 'frm_views_tried_the_new_editor';
		$meta_value = get_user_meta( $user_id, $meta_key, true );

		if ( is_array( $meta_value ) && in_array( $type, $meta_value, true ) ) {
			// type is already set so exit early without updating meta.
			return;
		}

		$new_meta_value   = is_array( $meta_value ) ? $meta_value : array();
		$new_meta_value[] = $type;

		update_user_meta( $user_id, $meta_key, $new_meta_value );
	}

	/**
	 * @param array  $metaboxes
	 * @param object $view
	 * @return array
	 */
	private static function filter_and_flatten_metaboxes( $metaboxes, $view ) {
		self::$view = $view;
		$flat       = self::flatten_metaboxes( $metaboxes );
		$filtered   = array_filter( $flat, self::class . '::filter_metaboxes' );
		return array_values( $filtered );
	}

	/**
	 * @param array $array
	 * @return array
	 */
	private static function flatten_metaboxes( $array ) {
		$flat = array();
		foreach ( $array as $current ) {
			if ( isset( $current['id'] ) ) {
				$flat[] = $current;
			} else {
				$flat = array_merge( $flat, self::flatten_metaboxes( $current ) );
			}
		}
		return $flat;
	}

	/**
	 * Remove the legacy metaboxes from the array of metaboxes.
	 *
	 * @param array $metabox
	 * @return bool true if the metabox should be included in the end result.
	 */
	private static function filter_metaboxes( $metabox ) {
		if ( isset( self::$view ) && ! self::view_supports_metabox_id( self::$view, $metabox['id'] ) ) {
			return false;
		}
		if ( 0 === strpos( $metabox['id'], 'acf-' ) ) {
			// allow any ACF fields assigned to views.
			return true;
		}
		$default_allowed_metabox_ids = array( 'icl_div_config', 'icl_div', 'frm_export_view' );
		$allowed_metabox_ids         = apply_filters( 'frm_view_editor_allowed_metaboxes', $default_allowed_metabox_ids );
		return in_array( $metabox['id'], $allowed_metabox_ids, true );
	}

	/**
	 * @param object $view
	 * @param string $metabox_id
	 * @return bool
	 */
	private static function view_supports_metabox_id( $view, $metabox_id ) {
		if ( 'frm_export_view' === $metabox_id ) {
			$show_count = get_post_meta( $view->ID, 'frm_show_count', true );
			if ( in_array( $show_count, array( 'all', 'dynamic' ), true ) && ! self::is_grid_type( $view ) ) {
				return true;
			}
			return (bool) self::is_table_type( $view );
		}
		return true;
	}

	/**
	 * @param string $metabox_data raw data.
	 * @return array parsed data.
	 */
	private static function parse_metabox_data( $metabox_data ) {
		$params = array();
		parse_str( urldecode( $metabox_data ), $params );
		return $params;
	}

	/**
	 * @param int $view_id
	 * @return bool
	 */
	private static function is_legacy_table_type( $view_id ) {
		return FrmViewsDisplaysHelper::is_legacy_table_type( $view_id );
	}

	public static function maybe_highlight_menu() {
		if ( ! FrmViewsAppHelper::view_editor_is_active() ) {
			return;
		}
		echo '<script type="text/javascript">jQuery(document).ready(function(){frmSelectSubnav();});</script>';
	}

	/**
	 * @param int $view_id
	 * @return string active preview filter value. Either '0', 'limited', or '1'.
	 */
	private static function get_active_preview_filter( $view_id ) {
		$filter = get_post_meta( $view_id, 'frm_active_preview_filter', true );
		if ( ! in_array( $filter, array( '0', '1', 'limited' ), true ) ) {
			$filter = FrmViewsAppHelper::get_default_content_filter();
		}
		return $filter;
	}

	/**
	 * Dismiss the coming soon message for a type (Calendar Editor or Table Editor).
	 * The message should not be shown again to the user after they dismiss it.
	 * This is handled through an AJAX request when the x is clicked.
	 */
	private static function dismiss_coming_soon_message() {
		$type = FrmAppHelper::get_param( 'type', '', 'post', 'sanitize_key' );
		if ( ! $type || ! in_array( $type, array( 'calendar', 'table' ), true ) ) {
			wp_send_json_error();
		}
		update_user_meta( get_current_user_id(), self::get_dismiss_message_meta_key( $type ), 1 );
		wp_send_json_success();
	}

	/**
	 * @param string $type 'table' or 'calendar'.
	 * @return int 1 or 0.
	 */
	private static function get_dismissed_coming_soon( $type ) {
		return get_user_meta( get_current_user_id(), self::get_dismiss_message_meta_key( $type ), true ) ? 1 : 0;
	}

	/**
	 * @param string $type 'table' or 'calendar'.
	 * @return string
	 */
	private static function get_dismiss_message_meta_key( $type ) {
		return 'frm_dismiss_view_editor_' . $type . '_coming_soon';
	}

	/**
	 * Check if a view is a grid type.
	 *
	 * @param object $view
	 * @return int 1 or 0.
	 */
	public static function is_grid_type( $view ) {
		return FrmViewsDisplaysHelper::is_grid_type( $view );
	}

	/**
	 * @param object $view
	 * @return int 1 or 0.
	 */
	public static function is_table_type( $view ) {
		return FrmViewsDisplaysHelper::is_table_type( $view );
	}

	private static function flatten_view() {
		$listing_layout  = FrmAppHelper::get_param( 'listingLayout', '', 'post' );
		$listing_content = FrmAppHelper::get_param( 'listingContent', '', 'post' );
		$detail_layout   = FrmAppHelper::get_param( 'detailLayout', '', 'post' );
		$detail_content  = FrmAppHelper::get_param( 'detailContent', '', 'post' );
		$type            = FrmAppHelper::get_param( 'type', 'grid', 'post', 'sanitize_key' );

		if ( ! $listing_layout ) {
			$listing_layout = self::get_default_layout();
		} else {
			$listing_layout = self::convert_layout_arrays_to_objects( $listing_layout );
		}

		if ( ! $detail_layout ) {
			$detail_layout = self::get_default_layout();
		} else {
			$detail_layout = self::convert_layout_arrays_to_objects( $detail_layout );
		}

		$response = array(
			'listingContent' => '',
			'detailContent'  => '',
		);

		self::$view_id = FrmAppHelper::get_param( 'view', 0, 'post', 'absint' );

		if ( $listing_content ) {
			$response['listingContent'] = self::flatten_content( $listing_content, $listing_layout, $type );
			if ( 'table' === $type ) {
				self::$layout_helper->force_layout_data( $listing_layout );
				$headers                   = self::$layout_helper->table_headers();
				$response['beforeContent'] = self::$layout_helper->get_table_header_content( $headers );
			}
		}

		if ( $detail_content && 'grid' === $type ) {
			$response['detailContent'] = self::flatten_content( $detail_content, $detail_layout );
		}

		wp_send_json_success( $response );
	}

	private static function convert_layout_arrays_to_objects( $layout ) {
		$encoded = json_encode( $layout );
		return json_decode( $encoded, false );
	}

	/**
	 * @param array  $content
	 * @param array  $layout
	 * @param string $type 'grid' or 'table'.
	 * @return string
	 */
	private static function flatten_content( $content, $layout, $type = 'grid' ) {
		$view     = new stdClass();
		$view->ID = self::$view_id;
		$helper   = new FrmViewsLayoutHelper( $view, $type );
		$helper->index_content_by_box( $content );
		self::$layout_helper = $helper;
		return $helper->get_output( $layout );
	}

	private static function get_default_layout() {
		$box         = new stdClass();
		$box->id     = 0;
		$row         = new stdClass();
		$row->boxes  = array( $box );
		$row->layout = 1;
		return array( $row );
	}

	/**
	 * Content is usually sent to the editor as an array.
	 * For some views without multiple boxes, we want to just take the string content from the first box (which should be the only box).
	 *
	 * @param mixed $content
	 * @return string
	 */
	private static function guarantee_content_is_a_string( $content ) {
		if ( ! is_array( $content ) ) {
			return is_string( $content ) ? $content : '';
		}
		return self::guarantee_flat_content( $content );
	}

	/**
	 * @param mixed $content
	 * @return string
	 */
	private static function guarantee_flat_content( $content ) {
		if ( ! is_array( $content ) ) {
			return $content;
		}
		foreach ( $content as $box ) {
			if ( isset( $box['box'] ) && ! empty( $box['content'] ) ) {
				return $box['content'];
			}
		}
		return '';
	}

	/**
	 * @since 5.3.3
	 *
	 * @return void
	 */
	private static function get_dropdown_options() {
		$view_id          = FrmAppHelper::get_post_param( 'viewId', 0, 'absint' );
		$where            = array(
			'post_type'     => FrmViewsDisplaysController::$post_type,
			'post_status !' => 'trash',
			'ID !'          => $view_id,
		);
		$columns          = array( 'ID', 'post_title' );
		$args             = array( 'order_by' => 'post_title ASC' );
		$view_names_by_id = wp_list_pluck(
			FrmDb::get_results( 'posts', $where, 'ID, post_title', $args ),
			'post_title',
			'ID'
		);

		if ( ! $view_names_by_id ) {
			wp_send_json_success( array( 'options' => array() ) );
		}

		$view_ids         = array_keys( $view_names_by_id );
		$views_by_form_id = array_reduce(
			FrmDb::get_results(
				'postmeta',
				array(
					'post_id'  => $view_ids,
					'meta_key' => 'frm_form_id',
				),
				'post_id, meta_value'
			),
			function ( $total, $row ) use ( $view_names_by_id ) {
				$view_id = $row->post_id;
				$form_id = $row->meta_value;

				if ( ! isset( $total[ $form_id ] ) ) {
					$total[ $form_id ] = array();
				}

				$view_data_for_array             = new stdClass();
				$view_data_for_array->ID         = $view_id;
				$view_data_for_array->post_title = $view_names_by_id[ $view_id ];

				$total[ $form_id ][] = $view_data_for_array;
				return $total;
			},
			array()
		);

		$options = array();
		if ( $views_by_form_id ) {
			$all_form_ids = array_keys( $views_by_form_id );
			$form_data    = FrmDb::get_results( 'frm_forms', array( 'id' => $all_form_ids ), 'id, name', array( 'order_by' => 'name ASC' ) );

			foreach ( $form_data as $row ) {
				$category  = ( $row->name ? $row->name : __( 'Untitled', 'formidable-views' ) ) . ' (' . __( 'Form', 'formidable-views' ) . ' ' . $row->id . ')';
				$options[] = array(
					'categoryHeader' => $category,
				);

				foreach ( $views_by_form_id[ $row->id ] as $view ) {
					$options[] = array(
						'id'       => $view->ID,
						'name'     => $view->post_title ? $view->post_title : __( 'Untitled', 'formidable-views' ),
						'editUrl'  => get_edit_post_link( $view->ID ),
						'category' => $category,
					);
				}
			}
		}

		$data = compact( 'options' );
		wp_send_json_success( $data );
	}

	/**
	 * @param string $classes
	 * @return string
	 */
	public static function add_view_editor_body_class( $classes ) {
		$classes .= ' frm-views-editor-body';
		return $classes;
	}

	private static function echo_default_grid_styles() {
		$default_grid_styles = self::get_default_grid_styles();
		echo json_encode( $default_grid_styles );
	}

	/**
	 * @param int $form_id
	 * @return array
	 */
	private static function get_date_field_ids( $form_id ) {
		$where = array(
			'type'    => 'date',
			'form_id' => $form_id,
		);
		return FrmDb::get_col( 'frm_fields', $where );
	}

	/**
	 * @param int $form_id
	 */
	private static function echo_date_field_ids( $form_id ) {
		echo json_encode( self::get_date_field_ids( $form_id ) );
	}

	private static function echo_draft_dropdown_options() {
		echo json_encode( self::get_draft_dropdown_options() );
	}

	/**
	 * @return array
	 */
	private static function get_draft_dropdown_options() {
		$default_options = array(
			'both' => __( 'Draft or complete entry', 'formidable-views' ),
			'0'    => __( 'Complete entry', 'formidable-views' ),
			'1'    => __( 'Draft', 'formidable-views' ),
		);

		/**
		 * Extends the filters drop down options.
		 *
		 * @since 5.4.2
		 *
		 * @param array $default_options Array of options.
		 */
		$extended_options = apply_filters( 'frm_views_entry_status_options', $default_options );

		if ( ! is_array( $extended_options ) ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'Please return an array of options.', 'formidable-views' ), '5.4.2' );
			$extended_options = $default_options;
		}

		return $extended_options;
	}

	/**
	 * @param int $form_id
	 */
	private static function echo_status_dropdown_options( $form_id ) {
		echo json_encode( self::get_status_dropdown_options( $form_id ) );
	}

	/**
	 * @param int $form_id
	 * @return array
	 */
	private static function get_status_dropdown_options( $form_id ) {
		$post_action = FrmFormAction::get_action_for_form( $form_id, 'wppost', 1 );
		if ( is_object( $post_action ) && is_array( $post_action->post_content ) && ! empty( $post_action->post_content['post_status'] ) ) {
			$status_field = FrmField::getOne( $post_action->post_content['post_status'] );
			if ( $status_field && is_object( $status_field ) && ! empty( $status_field->options ) ) {
				self::$status_field = $status_field;
				$options            = FrmProFieldsHelper::get_status_options( $status_field, $status_field->options );
				return array_reduce(
					$options,
					function ( $total, $option ) {
						$total[ $option['value'] ] = $option['label'];
						return $total;
					},
					array()
				);
			}
		}
		return array();
	}

	/**
	 * @since 5.6
	 *
	 * @param int $form_id
	 * @return void
	 */
	public static function echo_address_field_options( $form_id ) {
		echo json_encode( self::get_address_field_options( $form_id ) );
	}

	/**
	 * @since 5.6
	 *
	 * @param int $form_id
	 * @return array
	 */
	private static function get_address_field_options( $form_id ) {
		$address_fields = FrmField::get_all_types_in_form( $form_id, 'address' );
		return wp_list_pluck( $address_fields, 'name', 'id' );
	}

	/**
	 * @return int
	 */
	private static function get_status_field_id() {
		return isset( self::$status_field ) ? self::$status_field->id : 0;
	}

	/**
	 * @return array
	 */
	public static function get_default_grid_styles() {
		return array(
			'padding'     => '10px',
			'borderColor' => '#efefef',
			'borderStyle' => 'solid',
			'borderWidth' => '1px',
		);
	}

	public static function add_table_view_headers_to_csv( $before_content, $view ) {
		if ( self::is_table_type( $view ) ) {
			$layout_helper = new FrmViewsLayoutHelper( $view, 'table' );
			$layout_helper->set_layout_data( 'listing' );
			$content_helper = new FrmViewsContentHelper( $view->post_content );
			$layout_helper->index_content_by_box( $content_helper->get_content() );
			$before_content .= '<table>' . $layout_helper->table_headers();
		}
		return $before_content;
	}

	/**
	 * After the view post type is registered, set up screen details (a requirement for some third party plugins when calling do_action( 'load-post.php' ))
	 *
	 * @param string $post_type
	 */
	public static function setup_screen_after_view_post_type_is_registered( $post_type ) {
		if ( FrmViewsDisplaysController::$post_type !== $post_type ) {
			return;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			require_once ABSPATH . '/wp-admin/includes/screen.php';
		}

		if ( ! class_exists( 'WP_Screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		}

		global $hook_suffix;
		if ( is_null( $hook_suffix ) ) {
			$hook_suffix = ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		set_current_screen();
		global $current_screen;
		$current_screen->post_type = $post_type;
	}

	public static function call_load_post_action_on_admin_init() {
		do_action( 'load-post.php' ); // required for ACF to initialize.
	}
}
