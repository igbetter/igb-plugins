<?php
/**
 * App helper
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * App helper
 */
class AppHelper {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public static $plug_version = '1.1.1';

	/**
	 * Gets plugin version
	 *
	 * @return string
	 */
	public static function plugin_version() {
		return self::$plug_version;
	}

	/**
	 * Gets plugin folder name.
	 *
	 * @return string
	 */
	public static function plugin_folder() {
		return basename( self::plugin_path() );
	}

	/**
	 * Gets plugin path.
	 *
	 * @return string
	 */
	public static function plugin_path() {
		return dirname( dirname( dirname( __FILE__ ) ) );
	}

	/**
	 * Gets plugin URL.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url( '', self::plugin_path() . '/formidable-surveys.php' );
	}

	/**
	 * Gets plugin relative URL.
	 *
	 * @return string
	 */
	public static function relative_plugin_url() {
		return str_replace( array( 'https:', 'http:' ), '', self::plugin_url() );
	}

	/**
	 * Checks if Formidable Forms is installed and has the compatible version.
	 *
	 * @return bool
	 */
	public static function is_formidable_lite_compatible() {
		$required_version = '5.0.04';
		return class_exists( 'FrmAppHelper' ) && version_compare( \FrmAppHelper::$plug_version, $required_version, '>=' );
	}

	/**
	 * Checks if Formidable Pro is installed and has the compatible version.
	 *
	 * @return bool
	 */
	public static function is_formidable_pro_compatible() {
		$required_version = '5.0.04';
		return class_exists( 'FrmProDb' ) && version_compare( \FrmProDb::$plug_version, $required_version, '>=' );
	}

	/**
	 * Checks if this plugin is safe to run.
	 *
	 * @return bool
	 */
	public static function is_compatible() {
		return self::is_formidable_lite_compatible() && self::is_formidable_pro_compatible();
	}
}
