<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 5.2
 */
class FrmViewsApplicationsController {

	/**
	 * @return void
	 */
	public static function pre_edit_form() {
		$plugin_url      = FrmViewsAppHelper::plugin_url();
		$version         = FrmViewsAppHelper::plugin_version();
		$js_dependencies = array( 'formidable_edit_application_term' );

		wp_register_script( 'formidable_views_edit_application_term', $plugin_url . '/js/frm_application.js', $js_dependencies, $version, true );
		wp_enqueue_script( 'formidable_views_edit_application_term' );

		FrmViewsAppHelper::add_view_embed_examples_script();
	}

	/**
	 * @param array<string> $icons indexed by type key (ie. 'form', 'page').
	 * @return array<string>
	 */
	public static function add_views_icons_for_application_term_page( $icons ) {
		require_once FrmViewsAppHelper::plugin_path() . '/images/icons.svg';
		$icons['view']          = 'frm_view_icon';
		$icons['grid view']     = 'frm_views_grid_icon';
		$icons['calendar view'] = 'frm_views_calendar_icon';
		$icons['table view']    = 'frm_views_table_icon';
		$icons['classic view']  = 'frm_code_icon';
		return $icons;
	}
}
