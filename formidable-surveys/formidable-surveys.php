<?php
/**
 * Plugin Name: Formidable Surveys
 * Description: Create survey forms.
 * Version: 1.1.1
 * Plugin URI: https://formidableforms.com/
 * Author URI: https://formidableforms.com/
 * Author: Strategy11
 *
 * @package FrmSurveys
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

require_once dirname( __FILE__ ) . '/vendor/autoload.php';

if ( ! class_exists( '\FrmSurveys\controllers\HooksController', false ) ) {
	add_filter( 'frm_load_controllers', '\FrmSurveys\controllers\HooksController::add_hook_controller' );

	register_activation_hook( __FILE__, array( 'FrmSurveys\controllers\AppController', 'update_stylesheet_on_activation' ) );
}
