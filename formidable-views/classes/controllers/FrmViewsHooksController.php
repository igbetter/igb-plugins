<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsHooksController {

	public static function load_views() {
		global $frm_vars;
		if ( ! $frm_vars['pro_is_authorized'] ) {
			return;
		}

		if ( is_admin() && 'formidable-views-editor' === FrmAppHelper::get_param( 'page', '', 'get', 'sanitize_text_field' ) ) {
			FrmViewsAppHelper::emulate_legacy_view_editor();
		}

		add_action( 'admin_init', 'FrmViewsAppController::admin_init' );

		add_filter( 'frm_load_controllers', 'FrmViewsHooksController::load_controllers' );
		FrmHooksController::trigger_load_hook();
		remove_filter( 'frm_load_controllers', 'FrmViewsHooksController::load_controllers' );
		add_filter( 'frm_load_controllers', 'FrmViewsHooksController::add_hook_controller' );
	}

	public static function load_controllers( $controllers ) {
		unset( $controllers[0] ); // don't load hooks in free again
		unset( $controllers[1] ); // don't load pro either
		return self::add_hook_controller( $controllers );
	}

	public static function add_hook_controller( $controllers ) {
		$controllers[] = 'FrmViewsHooksController';
		return $controllers;
	}

	public static function load_hooks() {
		add_action( 'wp_before_admin_bar_render', 'FrmViewsAppController::admin_bar_configure', 25 );
		add_action( 'init', 'FrmViewsDisplaysController::register_post_types', 0 );
		add_action( 'init', 'FrmViewsSimpleBlocksController::register_simple_view_block', 20 );
		add_action( 'before_delete_post', 'FrmViewsDisplaysController::before_delete_post' );
		add_action( 'widgets_init', 'FrmViewsDisplaysController::register_widgets' );
		add_action( 'genesis_init', 'FrmViewsAppController::load_genesis' );
		add_action( 'frm_include_front_css', 'FrmViewsAppController::include_views_css' );

		add_filter( 'the_content', 'FrmViewsDisplaysController::get_content', 10 );
		add_filter( 'get_canonical_url', 'FrmViewsDisplaysController::maybe_filter_canonical_url', 10, 2 );

		add_shortcode( 'display-frm-data', 'FrmViewsDisplaysController::get_shortcode' );
		add_filter( 'frm_export_csv_table_heading', 'FrmViewsEditorController::add_table_view_headers_to_csv', 10, 2 );

		add_action( 'elementor/widgets/register', 'FrmViewsHooksController::register_elementor_hooks' );

		// AJAX Pagination
		add_filter( 'frm_before_display_content', 'FrmViewsPaginationController::before_display_content', 10, 3 );
	}

	public static function load_admin_hooks() {
		add_action( 'frm_after_uninstall', 'FrmViewsDb::uninstall' );
		add_action( 'admin_menu', 'FrmViewsDisplaysController::menu', 13 );
		add_action( 'admin_footer', 'FrmViewsEditorController::insert_form_popup' );
		add_action( 'restrict_manage_posts', 'FrmViewsDisplaysController::switch_form_box' );
		add_action( 'post_submitbox_misc_actions', 'FrmViewsDisplaysController::submitbox_actions' );
		add_action( 'add_meta_boxes', 'FrmViewsDisplaysController::add_meta_boxes' );
		add_action( 'save_post', 'FrmViewsDisplaysController::save_post' );
		add_action( 'frm_destroy_form', 'FrmViewsDisplaysController::delete_views_for_form' );
		add_action( 'manage_frm_display_posts_custom_column', 'FrmViewsDisplaysController::manage_custom_columns', 10, 2 );
		add_action( 'admin_footer', 'FrmViewsIndexController::admin_footer' );
		add_action( 'admin_enqueue_scripts', 'FrmViewsIndexController::add_index_script' );

		add_filter( 'parse_query', 'FrmViewsDisplaysController::filter_forms' );
		add_filter( 'frm_form_nav_list', 'FrmViewsAppController::form_nav', 9, 2 );
		add_filter( 'admin_head-post.php', 'FrmViewsDisplaysController::highlight_menu' );
		add_filter( 'admin_head-post-new.php', 'FrmViewsDisplaysController::highlight_menu' );
		add_filter( 'admin_head', 'FrmViewsEditorController::maybe_highlight_menu' );
		add_filter( 'views_edit-frm_display', 'FrmViewsDisplaysController::add_form_nav' );
		add_filter( 'edit_form_top', 'FrmViewsDisplaysController::add_form_nav_edit' );
		add_filter( 'post_row_actions', 'FrmViewsDisplaysController::post_row_actions', 10, 2 );
		add_filter( 'default_content', 'FrmViewsDisplaysController::default_content', 10, 2 );
		add_filter( 'manage_edit-frm_display_columns', 'FrmViewsDisplaysController::manage_columns' );
		add_filter( 'manage_edit-frm_display_sortable_columns', 'FrmViewsDisplaysController::sortable_columns' );
		add_filter( 'get_user_option_manageedit-frm_displaycolumnshidden', 'FrmViewsDisplaysController::hidden_columns' );
		add_filter( 'frm_popup_shortcodes', 'FrmViewsDisplaysController::popup_shortcodes', 9 );
		add_filter( 'get_the_excerpt', 'FrmViewsDisplaysController::use_first_box_for_excerpt_for_grid', 1, 2 );

		// Settings
		add_filter( 'frm_add_settings_section', 'FrmViewsSettingsController::add_settings_section', 1 );
		add_action( 'frm_update_settings', 'FrmViewsSettingsController::update' );
		add_action( 'frm_store_settings', 'FrmViewsSettingsController::store' );

		// Embed
		add_filter( 'frm_create_page_with_view_shortcode_content', 'FrmViewsAppController::get_page_shortcode_content', 1, 2 );

		// Applications
		add_action( 'frm_application_pre_edit_form', 'FrmViewsApplicationsController::pre_edit_form', 1 );
		add_filter( 'frm_application_term_icons', 'FrmViewsApplicationsController::add_views_icons_for_application_term_page', 1 );

		if ( FrmViewsAppHelper::view_editor_is_active() ) {
			add_filter( 'admin_body_class', 'FrmViewsEditorController::add_view_editor_body_class' );
		}

		if ( FrmViewsAppHelper::view_editor_is_active() || FrmViewsAppHelper::is_on_views_listing_page() ) {
			add_filter( 'frm_api_include_embed_form_script', '__return_true' );
		}

		if ( FrmAppHelper::is_admin_page( 'formidable' ) ) {
			add_filter( 'frm_before_save_wppost_action', 'FrmViewsDisplaysController::save_wppost_action_displays', 11, 2 );
		}

		// Add the Views Gutenberg block assets
		if ( is_callable( 'register_block_type' ) ) {
			add_action( 'enqueue_block_editor_assets', 'FrmViewsSimpleBlocksController::block_editor_assets' );
		}
	}

	public static function load_ajax_hooks() {
		add_action( 'wp_ajax_frm_get_cd_tags_box', 'FrmViewsDisplaysController::get_tags_box' );
		add_action( 'wp_ajax_frm_get_date_field_select', 'FrmViewsDisplaysController::get_date_field_select' );
		add_action( 'wp_ajax_frm_add_order_row', 'FrmViewsDisplaysController::get_order_row' );
		add_action( 'wp_ajax_frm_add_where_row', 'FrmViewsDisplaysController::get_where_row' );
		add_action( 'wp_ajax_frm_add_where_options', 'FrmViewsDisplaysController::get_where_options' );
		add_action( 'wp_ajax_frm_display_get_content', 'FrmViewsDisplaysController::get_post_content' );

		// AJAX Pagination
		add_action( 'wp_ajax_frm_views_load_page', 'FrmViewsPaginationController::load_page' );
		add_action( 'wp_ajax_nopriv_frm_views_load_page', 'FrmViewsPaginationController::load_page' );

		self::load_editor_ajax_hooks();
	}

	private static function load_editor_ajax_hooks() {
		$editor_route = 'FrmViewsEditorController::route_ajax';
		add_action( 'wp_ajax_frm_views_process_box_preview', $editor_route );
		add_action( 'wp_ajax_frm_views_editor_get_data', $editor_route );
		add_action( 'wp_ajax_frm_views_editor_create', $editor_route );
		add_action( 'wp_ajax_frm_views_get_table_column_options', $editor_route );
		add_action( 'wp_ajax_frm_views_get_map_view_options', $editor_route );
		add_action( 'wp_ajax_frm_views_get_calendar_view_options', $editor_route );
		add_action( 'wp_ajax_frm_views_editor_update', $editor_route );
		add_action( 'wp_ajax_frm_views_editor_info', $editor_route );
		add_action( 'wp_ajax_frm_save_view_layout_template', $editor_route );
		add_action( 'wp_ajax_frm_update_layout_template', $editor_route );
		add_action( 'wp_ajax_frm_delete_layout_template', $editor_route );
		add_action( 'wp_ajax_frm_dismiss_coming_soon_message', $editor_route );
		add_action( 'wp_ajax_frm_flatten_view', $editor_route );
		add_action( 'wp_ajax_frm_view_dropdown_options', $editor_route );
	}

	public static function load_view_hooks() {
		add_filter( 'frm_after_display_content', 'FrmViewsDisplaysController::include_pagination', 9, 4 );
		add_filter( 'frm_keep_address_value_array', '__return_true' );
		add_filter( 'frm_keep_credit_card_value_array', '__return_true' );
	}

	public static function load_form_hooks() {
	}

	public static function load_multisite_hooks() {
		add_action( 'frm_after_install', 'FrmViewsCopiesController::activation_install', 20 );
		add_action( 'frm_create_display', 'FrmViewsCopiesController::save_copied_display', 20, 2 );
		add_action( 'frm_update_display', 'FrmViewsCopiesController::save_copied_display', 20, 2 );
		add_action( 'frm_destroy_display', 'FrmViewsCopiesController::destroy_copied_display' );
	}

	public static function register_elementor_hooks() {
		require_once FrmViewsAppHelper::plugin_path() . '/classes/widgets/FrmViewsElementorWidget.php';
		\Elementor\Plugin::instance()->widgets_manager->register( new \FrmViewsElementorWidget() );

		if ( is_admin() ) {
			add_action(
				'elementor/editor/after_enqueue_styles',
				function () {
					wp_enqueue_style( 'font_icons', FrmAppHelper::plugin_url() . '/css/font_icons.css', array(), FrmAppHelper::plugin_version() );
				}
			);
		}
	}
}
