<?php
/**
 * Hooks controller
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\controllers;

use FrmSurveys\helpers\AppHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class HooksController
 */
class HooksController {

	/**
	 * Loads hooks when this plugin is incompatible.
	 */
	public static function load_incompatible_hooks() {
		self::load_translation();
		add_action( 'admin_notices', array( 'FrmSurveys\controllers\AppController', 'show_incompatible_notice' ) );
	}

	/**
	 * Adds this class to hook controllers list.
	 *
	 * @since 1.0
	 *
	 * @param array $controllers Hook controllers.
	 * @return array
	 */
	public static function add_hook_controller( $controllers ) {
		if ( ! AppHelper::is_compatible() ) {
			self::load_incompatible_hooks();
			return $controllers;
		}

		$controllers[] = __CLASS__;
		return $controllers;
	}

	/**
	 * Load the translation files.
	 *
	 * @since 1.0
	 */
	protected static function load_translation() {
		add_action( 'plugins_loaded', array( 'FrmSurveys\controllers\AppController', 'init_translation' ) );
	}

	/**
	 * Loads plugin hooks.
	 */
	public static function load_hooks() {
		self::load_translation();
		add_action( 'frm_after_install', 'FrmSurveys\controllers\AppController::trigger_upgrade' );

		add_filter( 'frm_get_field_type_class', array( 'FrmSurveys\controllers\NPSController', 'get_field_type_class' ), 10, 2 );
		add_filter( 'frm_pro_available_fields', array( 'FrmSurveys\controllers\NPSController', 'add_to_available_fields' ) );
		add_filter( 'frm_pro_stat_from_meta_values', array( 'FrmSurveys\controllers\NPSController', 'get_stat_from_meta_values' ), 10, 2 );
		add_filter( 'frm_number_fields', array( 'FrmSurveys\controllers\NPSController', 'add_to_number_fields' ) );
		add_filter( 'frm_logic_nps_input_type', array( 'FrmSurveys\controllers\NPSController', 'change_input_type_for_logic_rules' ) );
		add_filter( 'frm_graph_data', array( 'FrmSurveys\controllers\NPSController', 'graph_data' ), 10, 2 );
		add_filter( 'frm_google_chart', array( 'FrmSurveys\controllers\NPSController', 'google_chart_options' ), 10, 2 );
		add_filter( 'frm_pro_field_has_variable_html_id', array( 'FrmSurveys\controllers\NPSController', 'field_has_variable_html_id' ), 10, 2 );
		add_filter( 'frm_pro_radio_similar_field_types', array( 'FrmSurveys\controllers\NPSController', 'add_to_radio_similar_field_types' ) );

		add_filter( 'frm_get_field_type_class', array( 'FrmSurveys\controllers\RadioController', 'change_radio_field_type_class' ), 10, 2 );
		add_filter( 'frm_pro_field_should_show_images', array( 'FrmSurveys\controllers\RadioController', 'field_should_show_images' ), 10, 2 );
		add_filter( 'frm_pro_field_should_show_label', array( 'FrmSurveys\controllers\RadioController', 'field_should_show_label' ), 10, 2 );
		add_filter( 'frm_choice_field_option_label', array( 'FrmSurveys\controllers\RadioController', 'choice_field_option_label' ), 10, 2 );
		add_filter( 'frm_field_div_classes', array( 'FrmSurveys\controllers\RadioController', 'add_field_classes' ), 10, 2 );

		if ( ScaleController::core_and_pro_versions_met() ) {
			add_filter( 'frm_get_field_type_class', array( 'FrmSurveys\controllers\ScaleController', 'change_scale_field_type_class' ), 10, 2 );
			add_filter( 'frm_field_div_classes', array( 'FrmSurveys\controllers\ScaleController', 'add_field_classes' ), 10, 2 );
		}

		add_filter( 'frm_get_field_type_class', array( 'FrmSurveys\controllers\LikertController', 'get_field_type_class' ), 10, 2 );
		add_filter( 'frm_pro_available_fields', array( 'FrmSurveys\controllers\LikertController', 'add_to_available_fields' ) );
		add_filter( 'frm_fields_in_form', array( 'FrmSurveys\controllers\LikertController', 'remove_row_fields_from_form' ) );
		add_filter( 'frm_subfields_in_repeater', array( 'FrmSurveys\controllers\LikertController', 'remove_row_fields_from_form' ) );
		add_filter( 'frm_entry_values_fields', array( 'FrmSurveys\controllers\LikertController', 'remove_row_fields_from_form' ) );
		add_filter( 'frm_validate_likert_field_entry', array( 'FrmSurveys\controllers\LikertController', 'validate_field_entry' ), 10, 4 );
		add_filter( 'frm_validate_entry', array( 'FrmSurveys\controllers\LikertController', 'validate_entry' ), 10, 3 );
		add_filter( 'frm_field_div_classes', array( 'FrmSurveys\controllers\LikertController', 'row_field_classes' ), 10, 3 );
		add_filter( 'frm_google_chart', array( 'FrmSurveys\controllers\LikertController', 'google_chart_options' ), 10, 2 );
		add_filter( 'frm_graph_data', array( 'FrmSurveys\controllers\LikertController', 'graph_data' ), 10, 2 );
		add_filter( 'frm_pro_fields_in_summary_values', array( 'FrmSurveys\controllers\LikertController', 'change_field_order_keep_likert' ) );

		// This should run before FrmProFieldsHelper::get_form_fields().
		add_filter( 'frm_get_paged_fields', array( 'FrmSurveys\controllers\LikertController', 'update_likert_rows_order' ), 9 );

		add_filter( 'frm_fields_to_validate', array( 'FrmSurveys\controllers\LikertController', 'remove_orphaned_rows' ) );

		// Ranking fields.
		add_filter( 'frm_get_field_type_class', array( 'FrmSurveys\controllers\RankingController', 'get_field_type_class' ), 10, 2 );
		add_filter( 'frm_pro_available_fields', array( 'FrmSurveys\controllers\RankingController', 'add_to_available_fields' ) );
		add_filter( 'frm_graph_data', array( 'FrmSurveys\controllers\RankingController', 'graph_data' ), 10, 2 );
		add_filter( 'frm_field_div_classes', array( 'FrmSurveys\controllers\RankingController', 'add_classname_to_parent_container' ), 10, 2 );
		add_filter( 'frm_field_type_support_image_options', array( 'FrmSurveys\controllers\RankingController', 'field_type_support_image_options' ), 10, 2 );
		add_action( 'frm_include_front_css', array( 'FrmSurveys\controllers\AssetsController', 'add_frontend_css' ) );
		add_action( 'wp_enqueue_scripts', array( 'FrmSurveys\controllers\AssetsController', 'load_frontend_js' ) );
	}

	/**
	 * These hooks are only needed for front-end forms.
	 */
	public static function load_form_hooks() {

	}

	/**
	 * These hooks only load during ajax request.
	 *
	 * @since 1.0
	 */
	public static function load_ajax_hooks() {
		add_action( 'wp_ajax_frm_load_likert_display', array( 'FrmSurveys\controllers\LikertController', 'load_field_display' ) );
	}

	/**
	 * These hooks only load in the admin area.
	 *
	 * @since 1.0
	 */
	public static function load_admin_hooks() {
		add_filter( 'frm_db_needs_upgrade', array( 'FrmSurveys\controllers\AppController', 'needs_upgrade' ) );
		add_action( 'admin_init', array( 'FrmSurveys\controllers\AppController', 'initialize' ) );

		add_action( 'frm_pro_reports_boxes', array( 'FrmSurveys\controllers\NPSController', 'show_nps_score_in_report' ), 10, 2 );
		add_filter( 'frm_switch_field_types', array( 'FrmSurveys\controllers\NPSController', 'switch_field_types' ), 10, 2 );

		add_filter( 'frm_radio_display_format_options', array( 'FrmSurveys\controllers\AppController', 'change_field_display_format_options' ) );
		add_filter( 'frm_checkbox_display_format_options', array( 'FrmSurveys\controllers\AppController', 'change_field_display_format_options' ) );

		add_filter( 'frm_radio_display_format_args', array( 'FrmSurveys\controllers\AppController', 'change_field_display_format_args' ), 10, 2 );
		add_filter( 'frm_checkbox_display_format_args', array( 'FrmSurveys\controllers\AppController', 'change_field_display_format_args' ), 10, 2 );

		add_filter( 'frm_should_hide_bulk_edit', array( 'FrmSurveys\controllers\AppController', 'update_bulk_edit_visibility' ), 10, 3 );
		add_filter( 'frm_build_field_class', array( 'FrmSurveys\controllers\RadioController', 'add_field_classes' ), 10, 2 );

		if ( ScaleController::core_and_pro_versions_met() ) {
			add_filter( 'frm_scale_display_format_options', array( 'FrmSurveys\controllers\ScaleController', 'change_scale_display_format_options' ), 10, 2 );
			add_filter( 'frm_scale_display_format_args', array( 'FrmSurveys\controllers\ScaleController', 'change_scale_display_format_args' ), 10, 2 );
			add_filter( 'frm_build_field_class', array( 'FrmSurveys\controllers\ScaleController', 'add_field_classes' ), 10, 2 );
		}

		add_action( 'frm_before_field_options', array( 'FrmSurveys\controllers\LikertController', 'show_options_before' ), 10, 2 );
		add_action( 'frm_update_form', array( 'FrmSurveys\controllers\LikertController', 'on_update_form' ), 10, 2 );
		add_action( 'frm_before_destroy_field', array( 'FrmSurveys\controllers\LikertController', 'delete_row_fields' ) );
		add_filter( 'frm_fields_in_form_builder', array( 'FrmSurveys\controllers\LikertController', 'remove_row_fields_from_form' ) );
		add_filter( 'frm_fields_in_settings', array( 'FrmSurveys\controllers\LikertController', 'remove_row_fields_from_form' ) );
		add_filter( 'frm_fields_in_reports', array( 'FrmSurveys\controllers\LikertController', 'remove_row_fields_from_form' ) );
		add_filter( 'frm_fields_in_entries_list_table', array( 'FrmSurveys\controllers\LikertController', 'change_field_order_no_likert' ) );
		add_filter( 'frm_fields_in_tags_box', array( 'FrmSurveys\controllers\LikertController', 'change_field_order_keep_likert' ) );
		add_filter( 'frm_views_fields_in_create_view_popup', array( 'FrmSurveys\controllers\LikertController', 'change_field_order_keep_likert' ) );
		add_filter( 'frm_pro_fields_in_dynamic_selection', array( 'FrmSurveys\controllers\LikertController', 'change_field_order_no_likert' ) );
		add_filter( 'frm_pro_fields_in_lookup_selection', array( 'FrmSurveys\controllers\LikertController', 'change_field_order_no_likert' ) );
		add_filter( 'frm_table_graph_types', array( 'FrmSurveys\controllers\LikertController', 'table_graph_types' ) );
		add_filter( 'frm_is_field_present_in_logic_options', array( 'FrmSurveys\controllers\LikertController', 'exclude_from_logic_options' ), 10, 2 );
		add_filter( 'frm_before_field_created', array( 'FrmSurveys\controllers\LikertController', 'create_default_rows_and_columns' ) );
		add_action( 'frm_after_field_created', array( 'FrmSurveys\controllers\LikertController', 'update_default_rows_after_creating' ), 10, 2 );
		add_action( 'frm_after_duplicate_form', array( 'FrmSurveys\controllers\LikertController', 'after_duplicate_form' ) );
		add_action( 'frm_after_duplicate_field', array( 'FrmSurveys\controllers\LikertController', 'after_duplicate_field' ) );
		add_filter( 'frm_csv_field_columns', array( 'FrmSurveys\controllers\LikertController', 'exclude_likert_from_csv_headings' ), 10, 2 );
		add_filter( 'frm_pro_csv_import_fixed_meta_values', array( 'FrmSurveys\controllers\LikertController', 'set_csv_import_fixed_meta_values' ), 10, 2 );
		add_filter( 'frm_fields_for_csv_export', array( 'FrmSurveys\controllers\LikertController', 'prepare_fields_for_csv_export_import' ) );
		add_filter( 'frm_pro_fields_for_csv_mapping', array( 'FrmSurveys\controllers\LikertController', 'prepare_fields_for_csv_export_import' ) );
		add_filter( 'frm_pro_get_field_for_import', array( 'FrmSurveys\controllers\LikertController', 'prepare_field_for_import' ) );

		// Detect which tab is active from "Display Format": "Simple"|"Images".
		if ( is_callable( 'FrmProFieldsController::change_checkbox_display_format_args' ) ) {
			add_filter( 'frm_ranking_display_format_args', 'FrmProFieldsController::change_checkbox_display_format_args', 5, 2 );
		}

		add_action( 'admin_enqueue_scripts', array( 'FrmSurveys\controllers\AssetsController', 'load_admin_scripts' ) );
		add_action( 'admin_footer', array( 'FrmSurveys\controllers\AssetsController', 'print_admin_footer_html' ) );
		// Enqueue assets to Gutenberg editor.
		add_action( 'enqueue_block_editor_assets', 'FrmSurveys\controllers\AssetsController::block_editor_assets' );

		// Ranking Fields.
		add_filter( 'frm_reports_page_table_types', array( 'FrmSurveys\controllers\RankingController', 'reports_page_table_types' ) );
		add_filter( 'frm_is_field_present_in_logic_options', array( 'FrmSurveys\controllers\RankingController', 'exclude_from_logic_options' ), 10, 2 );
		add_filter( 'frm_bulk_edit_field_types', array( 'FrmSurveys\controllers\RankingController', 'enable_bulk_edit' ), 10 );
		add_filter( 'frm_ranking_display_format_options', array( 'FrmSurveys\controllers\RankingController', 'change_field_display_format_options' ), 5 );
	}

	/**
	 * This can be removed later. It's required for earlier Formidable versions.
	 *
	 * @since 1.0
	 */
	public static function load_view_hooks() {
		// Add view hooks here.
	}
}
