<?php
/**
 * Plugin Name: Formidable Campaign Monitor
 * Description: Add users to your Campaign Monitor list from your Formidable forms
 * Version: 1.05
 * Plugin URI: https://formidableforms.com/
 * Author URI: https://formidableforms.com/
 * Author: Strategy11
 * Text Domain: frm-campaignmonitor
 *
 * @package CampaignMonitor
 */

// don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

function frm_campaignmonitor_forms_autoloader( $class_name ) {
	$path = dirname( __FILE__ );

	// Only load Frm classes here.
	if ( ! preg_match( '/^FrmCM(.+)?$/', $class_name ) ) {
		return;
	}

	if ( preg_match( '/^.+Controller$/', $class_name ) ) {
		$path .= '/controllers/' . $class_name . '.php';
	} else {
		$path .= '/models/' . $class_name . '.php';
	}

	if ( file_exists( $path ) ) {
		include $path;
	}
}

// Add the autoloader.
spl_autoload_register( 'frm_campaignmonitor_forms_autoloader' );

FrmCMHooksController::load_hooks();
