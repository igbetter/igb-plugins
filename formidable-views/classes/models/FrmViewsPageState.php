<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsPageState {

	/**
	 * @var FrmViewsPageState
	 */
	private static $instance;

	/**
	 * @var array
	 */
	private $state;

	private function __construct() {
		$this->state = array();
	}

	public static function set_initial_value( $key, $value ) {
		self::maybe_initialize();
		self::$instance->set( $key, $value );
	}

	/**
	 * @return bool true if just initialized.
	 */
	private static function maybe_initialize() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
			return true;
		}
		return false;
	}

	/**
	 * @return void
	 */
	public static function reset() {
		if ( isset( self::$instance ) ) {
			self::$instance = new self();
		}
	}

	public function set( $key, $value ) {
		$this->state[ $key ] = $value;
	}

	public static function get_from_request( $key, $default ) {
		if ( self::maybe_initialize() ) {
			self::get_state_from_request();
		}
		return self::$instance->get( $key, $default );
	}

	/**
	 * @return array
	 */
	public static function get_shortcode_atts_from_request() {
		if ( self::maybe_initialize() ) {
			self::get_state_from_request();
		}
		return self::$instance->get_shortcode_atts();
	}

	/**
	 * @return array
	 */
	public function get_shortcode_atts() {
		$keys = array(
			'id',
			'entry_id',
			'filter',
			'user_id',
			'limit',
			'page_size',
			'offset',
			'order_by',
			'order',
			'drafts',
			'wpautop',
		);
		$atts = array();
		foreach ( $keys as $key ) {
			if ( isset( $this->state[ $key ] ) ) {
				$atts[ $key ] = $this->state[ $key ];
			}
		}

		if ( isset( $this->state['get'] ) ) {
			foreach ( $this->state['get'] as $key => $value ) {
				$_GET[ $key ] = $value;
			}
		}

		return $atts;
	}

	public function get( $key, $default ) {
		if ( isset( $this->state[ $key ] ) ) {
			return $this->state[ $key ];
		}
		return $default;
	}

	/**
	 * Get an encrypted state string to use in HTML comment. String may be empty.
	 *
	 * @return string
	 */
	public static function maybe_get_state_string() {
		if ( empty( self::$instance ) && ! self::get_state_from_request() ) {
			return '';
		}
		return self::$instance->get_state_string();
	}

	/**
	 * @return bool true if there is valid state data in the request.
	 */
	private static function get_state_from_request() {
		$encrypted_state = FrmAppHelper::get_post_param( 'frm_view_page_state', '', 'sanitize_text_field' );
		if ( ! $encrypted_state ) {
			return false;
		}
		$secret          = self::get_encryption_secret();
		$decrypted_state = openssl_decrypt( $encrypted_state, 'AES-128-ECB', $secret );
		if ( false === $decrypted_state ) {
			return false;
		}
		$decoded_state = json_decode( $decrypted_state, true );
		if ( ! is_array( $decoded_state ) ) {
			return false;
		}
		foreach ( $decoded_state as $key => $value ) {
			self::set_initial_value( self::decompressed_key( $key ), $value );
		}
		return true;
	}

	/**
	 * @return string
	 */
	public function get_state_string() {
		$secret           = self::get_encryption_secret();
		$compressed_state = $this->compressed_state();
		$json_encoded     = json_encode( $compressed_state );
		$encrypted        = openssl_encrypt( $json_encoded, 'AES-128-ECB', $secret );
		return $encrypted;
	}

	/**
	 * @return array
	 */
	private function compressed_state() {
		$compressed = array();
		foreach ( $this->state as $key => $value ) {
			$compressed[ self::compressed_key( $key ) ] = $value;
		}
		return $compressed;
	}

	/**
	 * Get a reduced size key string (a single characters) to use for encrypted state to keep the string smaller.
	 *
	 * @param string $key
	 * @return string
	 */
	private static function compressed_key( $key ) {
		if ( 'order_by' === $key ) {
			return 'b';
		}
		if ( 'offset' === $key ) {
			return 'of';
		}
		if ( 'get_value' === $key ) {
			return 'v';
		}
		if ( 'load_more' === $key ) {
			return 'm';
		}
		if ( 'global_post' === $key ) {
			return 'gp';
		}
		if ( 'seed' === $key ) {
			return 'seed';
		}
		return $key[0];
	}

	/**
	 * @param string $key
	 * @return string
	 */
	private static function decompressed_key( $key ) {
		switch ( $key ) {
			case 'b':
				return 'order_by';
			case 'd':
				return 'drafts';
			case 'f':
				return 'filter';
			case 'g':
				return 'get';
			case 'i':
				return 'id';
			case 'l':
				return 'limit';
			case 'm':
				return 'load_more';
			case 'o':
				return 'order';
			case 'p':
				return 'page_size';
			case 's':
				return 'site_url';
			case 'u':
				return 'user_id';
			case 'v':
				return 'get_value';
			case 'w':
				return 'wpautop';
			case 'gp':
				return 'global_post';
			case 'of':
				return 'offset';
		}
		return $key;
	}

	public static function set_get_param( $key, $value ) {
		self::maybe_initialize();
		$get         = self::$instance->get( 'get', array() );
		$get[ $key ] = $value;
		self::set_initial_value( 'get', $get );
	}

	/**
	 * @return string
	 */
	private static function get_encryption_secret() {
		$secret_key = get_option( 'frm_views_page_state_key' );

		// If we already have the secret, send it back.
		if ( false !== $secret_key ) {
			return base64_decode( $secret_key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		}

		// We don't have a secret, so let's generate one.
		$secret_key = is_callable( 'sodium_crypto_secretbox_keygen' ) ? sodium_crypto_secretbox_keygen() : wp_generate_password( 32, true, true );
		add_option( 'frm_views_page_state_key', base64_encode( $secret_key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		return $secret_key;
	}

	/**
	 * @param mixed $view
	 * @return void
	 */
	public static function sync_view_info_to_state( $view ) {
		if ( ! is_object( $view ) || ( empty( $view->frm_ajax_pagination ) && empty( $view->frm_ajax_refresh ) ) ) {
			return;
		}

		if ( ! is_numeric( $view->frm_ajax_pagination ) ) {
			self::set_initial_value( 'load_more', 1 );
		}

		self::set_initial_value( 'site_url', self::get_base_url() );

		if ( FrmViewsDisplaysHelper::is_table_type( $view ) ) {
			self::set_initial_value( 'table', 1 );
		}

		global $post;
		if ( $post ) {
			self::set_initial_value( 'global_post', $post->ID );
		}

		if ( in_array( 'rand', $view->frm_order_by, true ) && ! self::get_from_request( 'seed', 0 ) ) {
			$seed = FrmViewsDisplay::get_seed();
			self::set_initial_value( 'seed', $seed );
		}
	}

	/**
	 * @return string
	 */
	private static function get_base_url() {
		global $post;
		$base_url = $post ? get_permalink( $post->ID ) : FrmAppHelper::get_server_value( 'REQUEST_URI' );
		return untrailingslashit( $base_url );
	}
}
