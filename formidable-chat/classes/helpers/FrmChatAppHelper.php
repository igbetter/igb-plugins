<?php

class FrmChatAppHelper {

	/**
	 * @var string $plug_version
	 */
	public static $plug_version = '1.1.3';

	/**
	 * @return string
	 */
	public static function plugin_version() {
		return self::$plug_version;
	}

	/**
	 * @return string
	 */
	public static function path() {
		return dirname( dirname( dirname( __FILE__ ) ) );
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public static function plugin_url( $path = '' ) {
		return plugins_url( $path, self::path() . '/formidable-chat.php' );
	}

	/**
	 * @return bool
	 */
	public static function use_minified_js_file() {
		if ( self::debug_scripts_are_on() && self::has_unminified_js_file() ) {
			return false;
		}
		return self::has_minified_js_file();
	}

	/**
	 * @return bool
	 */
	public static function debug_scripts_are_on() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	}

	/**
	 * @return string
	 */
	public static function js_suffix() {
		return self::use_minified_js_file() ? '.min' : '';
	}

	/**
	 * @return bool
	 */
	public static function has_unminified_js_file() {
		return is_readable( self::path() . '/js/chat.js' );
	}

	/**
	 * @return bool
	 */
	public static function has_minified_js_file() {
		return is_readable( self::path() . '/js/chat.min.js' );
	}
}
