<?php
/*
Plugin Name: Formidable Visual Views
Description: Add the power of views to Formidable Forms to display form submissions in listings, tables, calendars, and more.
Version: 5.6.1
Plugin URI: https://formidableforms.com/
Author URI: https://formidableforms.com/
Author: Strategy11
Text Domain: formidable-views
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( ! function_exists( 'load_formidable_views' ) ) {
	add_action( 'plugins_loaded', 'load_formidable_views', 1 );
	function load_formidable_views() {
		$is_free_installed = function_exists( 'load_formidable_forms' );
		$is_pro_installed  = function_exists( 'load_formidable_pro' );
		if ( ! $is_free_installed ) {
			add_action( 'admin_notices', 'frm_views_free_not_installed_notice' );
		} elseif ( ! $is_pro_installed ) {
			add_action( 'admin_notices', 'frm_views_pro_not_installed_notice' );
			$page = FrmAppHelper::get_param( 'page', '', 'get', 'sanitize_text_field' );
			if ( 'formidable' === $page ) {
				add_filter( 'frm_message_list', 'frm_views_pro_missing_add_message' );
			}
		} else {
			spl_autoload_register( 'frm_views_autoloader' );
			FrmViewsHooksController::load_views();
		}
	}

	function frm_views_autoloader( $class_name ) {
		// Only load Frm classes here
		if ( ! preg_match( '/^FrmViews.+$/', $class_name ) ) {
			return;
		}

		$filepath = __DIR__;
		frm_class_autoloader( $class_name, $filepath );
	}

	function frm_views_free_not_installed_notice() {
		?>
		<div class="error">
			<p>
				<?php esc_html_e( 'Formidable Views requires Formidable Forms to be installed.', 'formidable-views' ); ?>
				<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=formidable+forms&tab=search&type=term' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Install Formidable Forms', 'formidable-views' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	function frm_views_pro_not_installed_notice() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'Formidable Views requires Formidable Forms Pro to be installed.', 'formidable-views' ); ?></p>
		</div>
		<?php
	}

	function frm_views_pro_missing_add_message( $messages ) {
		$messages['views_pro_missing'] = 'Formidable Views requires Formidable Forms Pro to be installed.';
		return $messages;
	}
}


/**
 * Update stylesheet on activation hook.
 *
 * @since 5.3.1
 *
 * @return void
 */
function frm_views_update_stylesheet_on_activation() {
	if ( ! function_exists( 'load_formidable_forms' ) ) {
		return;
	}

	load_formidable_views();
	FrmViewsAppController::update_stylesheet();
}

register_activation_hook( __FILE__, 'frm_views_update_stylesheet_on_activation' );
