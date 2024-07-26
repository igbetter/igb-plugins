<?php
/**
 * WC_Shipping_Debug class.
 */

class WCShippingDebug {
	/**
	 * Array of note strings to be displayed in notes section of debug notice.
	 *
	 * @var array
	 */
	private $notes;

	/**
	 * Array of API requests as an XML string.
	 *
	 * @var array
	 */
	private $requests;

	/**
	 * Array of API responses as an XML string.
	 *
	 * @var array
	 */
	private $responses;

	/**
	 * Shipping service name to be displayed in debug notice.
	 *
	 * @var string
	 */
	private $service_name;

	/**
	 * Constructor.
	 *
	 * @param string $service_name Shipping service name to be displayed in debug notice.
	 */
	public function __construct( $service_name ) {
		$this->service_name = $service_name;
		$this->notes        = array();
		$this->requests     = array();
		$this->responses    = array();
	}

	/**
	 * Enqueue style and script for debug notice.
	 */
	public static function enqueue_resources() {
		if ( self::should_display_debug() ) {
			wp_enqueue_script( 'woocommerce-shipping-debug-viewer-js', plugin_dir_url( __FILE__ ) . 'shipping-debug.js', array( 'jquery-ui-accordion' ) );
			wp_enqueue_style( 'woocommerce-shipping-debug-viewer-style', plugin_dir_url( __FILE__ ) . 'shipping-debug.css' );
		}
	}

	/**
	 * Print all debug info as WC admin notice.
	 */
	public function display() {
		if ( self::should_display_debug() ) {
			$notes        = $this->notes;
			$requests     = array_map( array( $this, 'try_prettify_xml' ), $this->requests );
			$responses    = array_map( array( $this, 'try_prettify_xml' ), $this->responses );
			$service_name = $this->service_name;

			ob_start();
			include( 'html-shipping-debug-notice.php' );
			$notice_html = ob_get_clean();

			if ( ! wc_has_notice( $notice_html ) ) {
				wc_add_notice( $notice_html );
			}
		}
	}

	/**
	 * Whether or not debug mode should be displayed.
	 */
	public static function should_display_debug() {
		return ( current_user_can( 'manage_options' ) && ( is_cart() || is_checkout() ) );
	}

	/**
	 * Prettify XML.
	 *
	 * @param string $maybe_xml String to be prettified as XML.
	 *
	 * @return string
	 */
	protected function try_prettify_xml( $maybe_xml ) {
		if ( class_exists( 'DOMDocument' ) ) {
			// Many APIs have info before header, so separate out so they can be parsed.
			$xml_start = strpos( $maybe_xml, '<' );
			$pre_xml   = substr( $maybe_xml, 0, $xml_start );
			$xml       = substr( $maybe_xml, $xml_start );

			// Prettify xml.
			$dom                     = new DOMDocument;
			$dom->preserveWhiteSpace = false;
			$dom->loadXML( $xml );
			$dom->formatOutput = true;
			return $pre_xml . $dom->saveXML();
		}
		return $maybe_xml;
	}

	/**
	 * Add note to notes array to be displayed in notes section of debug notice.
	 *
	 * @param string $note Note text to be added.
	 */
	public function add_note( $note ) {
		array_push( $this->notes, $note );
	}

	/**
	 * Add request XML to be displayed in requests section of debug notice.
	 *
	 * @param string $request Request XML string.
	 */
	public function add_request( $request ) {
		array_push( $this->requests, $request );
	}

	/**
	 * Add response XML to be displayed in responses section of debug notice.
	 *
	 * @param string $response Response XML string.
	 */
	public function add_response( $response ) {
		array_push( $this->responses, $response );
	}
}
