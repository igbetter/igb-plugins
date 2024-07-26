<?php
/**
 * URL helper
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsUrlHelper
 */
class FrmPdfsUrlHelper {

	/**
	 * Gets encoded URL.
	 *
	 * @param string $base_url Base URL.
	 * @param array  $params   URL params.
	 * @return string
	 */
	public static function get_encoded_url( $base_url, $params ) {
		$params = self::encode_url_params( $params );
		return add_query_arg( 'frm_data', $params, $base_url );
	}

	/**
	 * Encodes the URL params.
	 *
	 * @param array $params URL params.
	 * @return string
	 */
	public static function encode_url_params( $params ) {
		$param_str = http_build_query( $params );
		return urlencode( base64_encode( $param_str ) );
	}

	/**
	 * Decodes the URL params.
	 *
	 * @param string $encode_string The encoded URL params string.
	 * @return array
	 */
	public static function decode_url_params( $encode_string ) {
		$param_str = urldecode( base64_decode( $encode_string ) );
		parse_str( $param_str, $params );
		return $params;
	}
}
