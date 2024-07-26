<?php
/**
 * Plugin Name: Formidable Form Action Automation
 * Description: Allows you to set future notifications on form entries
 * Version: 2.07
 * Author URI: https://formidableforms.com
 * Author: Strategy11
 *
 * @package formidable-autoresponder
 */

add_action( 'init', 'load_frm_autoresponder', 0 );
function load_frm_autoresponder() {
	require_once( dirname( __FILE__ ) . '/classes/helpers/FrmAutoresponderHelper.php' );
	spl_autoload_register( 'FrmAutoresponderHelper::autoload' );

	FrmAutoresponderAppController::init();
}
