<?php
/*
Plugin Name: Formidable Conversational Forms
Description: Ask one question at a time for automated conversations.
Version: 1.1.3
Plugin URI: https://formidableforms.com/
Author URI: https://formidableforms.com/
Author: Strategy11
*/

/**
 * Register autoload for Formidable conversational forms.
 *
 * @param string $class_name
 * @return void
 */
function frm_forms_chat_autoloader( $class_name ) {
	// Only load Frm classes here
	if ( ! preg_match( '/^FrmChat.+$/', $class_name ) ) {
		return;
	}

	$filepath = dirname( __FILE__ ) . '/classes/';
	if ( preg_match( '/^.+Helper$/', $class_name ) ) {
		$filepath .= 'helpers/';
	} elseif ( preg_match( '/^.+Controller$/', $class_name ) ) {
		$filepath .= 'controllers/';
	} else {
		$filepath .= 'models/';
	}

	$filepath .= $class_name . '.php';

	if ( file_exists( $filepath ) ) {
		include $filepath;
	}
}

/**
 * @return void
 */
function load_formidable_chat() {
	$is_free_installed = function_exists( 'load_formidable_forms' );
	$is_pro_installed  = function_exists( 'load_formidable_pro' );

	if ( ! $is_free_installed ) {
		add_action( 'admin_notices', 'frm_chat_free_not_installed_notice' );
	} elseif ( ! $is_pro_installed ) {
		add_action( 'admin_notices', 'frm_chat_pro_not_installed_notice' );
		$page = FrmAppHelper::get_param( 'page', '', 'get', 'sanitize_text_field' );
		if ( 'formidable' === $page ) {
			add_filter( 'frm_message_list', 'frm_chat_pro_missing_add_message' );
		}
	} else {
		// Add the autoloader
		spl_autoload_register( 'frm_forms_chat_autoloader' );

		FrmChatAppController::load_hooks();
		FrmChatSettingsController::load_hooks();
	}
}

/**
 * @return void
 */
function frm_chat_free_not_installed_notice() {
	?>
	<div class="error">
		<p>
			<?php esc_html_e( 'Formidable Conversational Forms requires Formidable Forms to be installed.', 'formidable-chat' ); ?>
			<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=formidable+forms&tab=search&type=term' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Install Formidable Forms', 'formidable-chat' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * @return void
 */
function frm_chat_pro_not_installed_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'Formidable Conversational Forms requires Formidable Forms Pro to be installed.', 'formidable-chat' ); ?></p>
	</div>
	<?php
}

/**
 * @param array $messages
 * @return array
 */
function frm_chat_pro_missing_add_message( $messages ) {
	$messages['chat_pro_missing'] = 'Formidable Conversational Forms requires Formidable Forms Pro to be installed.';
	return $messages;
}

/**
 * @return void
 */
function frm_update_stylesheet_on_activation() {
	if ( ! function_exists( 'load_formidable_forms' ) ) {
		return;
	}

	load_formidable_chat();

	$frm_style = new FrmStyle();
	$frm_style->update( 'default' );

	$template_api = new FrmChatStyleTemplateApi();
	if ( ! $template_api->get_conversational_style() ) {
		$template_api->install_style();
	}
}

add_action( 'plugins_loaded', 'load_formidable_chat', 1 );

register_activation_hook( __FILE__, 'frm_update_stylesheet_on_activation' );
