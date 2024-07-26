<?php
/**
 * Plugin Name: Formidable PDFs
 * Description: Export entry as PDF and attach PDF file to email
 * Version: 2.0.4
 * Plugin URI: https://formidableforms.com/
 * Author URI: https://formidableforms.com/
 * Author: Strategy11
 * Text Domain: formidable-pdfs
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Loads all the classes for this plugin.
 *
 * @param string $class_name The name of the class to load.
 */
function frm_pdfs_autoloader( $class_name ) {
	$path = dirname( __FILE__ );

	// Only load Frm classes here.
	if ( ! preg_match( '/^FrmPdfs.+$/', $class_name ) ) {
		return;
	}

	if ( preg_match( '/^.+Controller$/', $class_name ) ) {
		$path .= '/classes/controllers/' . $class_name . '.php';
	} elseif ( preg_match( '/^.+Helper$/', $class_name ) ) {
		$path .= '/classes/helpers/' . $class_name . '.php';
	} else {
		$path .= '/classes/models/' . $class_name . '.php';
	}

	if ( file_exists( $path ) ) {
		include $path;
	}
}

// Load the copied \Dompdf\Helpers class from our /helpers before it gets auto-loaded from the Dompdf source.
// This way we can overwrite the functionality of Helpers.php without modifying a file directly in the Dompdf source code.
include __DIR__ . '/classes/helpers/Helpers.php';

spl_autoload_register( 'frm_pdfs_autoloader' );

add_filter( 'frm_load_controllers', array( 'FrmPdfsHooksController', 'add_hooks_controller' ) );
