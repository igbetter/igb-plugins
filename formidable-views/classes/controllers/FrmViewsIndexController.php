<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsIndexController {

	public static function admin_nav( $args = array() ) {
		FrmViewsDisplaysController::add_new_button( $args );
		?>
		<div class="frm_hidden">
			<?php FrmFormsHelper::forms_dropdown( 'frm_data_source_form_select_template', '', array( 'inc_children' => 'include' ) ); ?>
		</div>
		<?php
		self::maybe_register_selected_form_name_jsvar();
	}

	public static function admin_footer() {
		if ( ! FrmViewsAppHelper::is_on_views_listing_page() ) {
			return;
		}
		require FrmViewsAppHelper::plugin_path() . '/images/icons.svg';
		self::register_new_view_icons();
	}

	public static function add_index_script() {
		if ( ! FrmViewsAppHelper::is_on_views_listing_page() || ! FrmViewsDisplaysHelper::is_edit_view_page() ) {
			return;
		}

		FrmViewsAppHelper::add_modal_css();

		$version = FrmViewsAppHelper::plugin_version();
		wp_register_style( 'formidable_views_index', FrmViewsAppHelper::plugin_url() . '/css/index.css', array(), $version );

		self::register_index_js();

		wp_enqueue_script( 'formidable_views_index' );
		wp_enqueue_style( 'formidable_views_index' );

		self::add_index_inline_style();

		FrmViewsAppHelper::add_view_embed_examples_script();
	}

	private static function maybe_register_selected_form_name_jsvar() {
		$form_id = FrmAppHelper::get_param( 'form', '', 'get', 'absint' );
		if ( ! $form_id ) {
			return;
		}

		$form = FrmForm::getOne( $form_id );
		if ( ! $form ) {
			return;
		}

		echo '<script type="text/javascript">var frmSelectedFormName = ' . wp_json_encode( $form->name ) . ';</script>';
	}

	/**
	 * Register the (possibly minified) main index JavaScript file.
	 */
	private static function register_index_js() {
		$use_minified_js = FrmViewsAppHelper::use_minified_js_file();
		$index_js_path   = FrmViewsAppHelper::plugin_url() . '/js/index' . FrmViewsAppHelper::js_suffix() . '.js';
		$dependencies    = array( 'wp-i18n' );
		$version         = FrmViewsAppHelper::plugin_version();

		if ( class_exists( 'FrmApplicationTemplate' ) && class_exists( 'FrmProApplication' ) ) {
			$dependencies[] = 'jquery-ui-autocomplete';
			$dependencies[] = 'formidable_dom';
		}

		if ( ! $use_minified_js ) {
			FrmViewsAppHelper::add_dom_script();
		}

		wp_register_script( 'formidable_views_index', $index_js_path, $dependencies, $version, true );

		if ( 1 === FrmAppHelper::simple_get( 'triggerNewViewModal', 'absint' ) ) {
			$application_id = FrmAppHelper::simple_get( 'applicationId', 'absint' );
			if ( $application_id ) {
				$application = get_term( $application_id, 'frm_application' );
				if ( $application instanceof WP_Term ) {
					wp_localize_script(
						'formidable_views_index',
						'frmAutocompleteApplicationVars',
						array( 'name' => $application->name )
					);
				}
			}
		}

		$form_ids_with_address_fields = array_values(
			array_unique(
				FrmDb::get_col(
					'frm_fields',
					array( 'type' => 'address' ),
					'form_id'
				)
			)
		);

		$applications_cap = is_callable( 'FrmProApplicationsHelper::get_custom_applications_capability' ) ? FrmProApplicationsHelper::get_custom_applications_capability() : 'frm_edit_forms';
		$js_vars          = array(
			'canAddApplications'       => current_user_can( $applications_cap ) ? 1 : 0,
			'pluginURL'                => FrmViewsAppHelper::plugin_url(),
			'showMapViews'             => class_exists( 'FrmGeoMapViewController', false ),
			'formIdsWithAddressFields' => $form_ids_with_address_fields,
		);
		wp_localize_script( 'formidable_views_index', 'frmViewsIndexVars', $js_vars );
	}

	/**
	 * Adds inline CSS styles to the admin.
	 *
	 * @since 5.4.2
	 */
	private static function add_index_inline_style() {
		global $wp_query;
		$styles = '';

		// Hides the '.table-view-list' element when there are no posts available.
		$post_status = FrmAppHelper::get_param( 'post_status' );
		if ( FrmViewsDisplaysHelper::is_edit_view_page() && ! $post_status && ! $wp_query->found_posts ) {
			$styles .= '
				.table-view-list, .table-view-excerpt {
					display: none;
				}
			';
		}

		wp_add_inline_style( 'formidable_views_index', $styles );
	}

	private static function register_new_view_icons() {
		require_once FrmViewsAppHelper::views_path() . '/index/new-view-icons.php';
	}
}
