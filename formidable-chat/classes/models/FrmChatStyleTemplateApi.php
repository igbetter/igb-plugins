<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmChatStyleTemplateApi extends FrmFormApi {

	/**
	 * @var string $style_template
	 */
	private static $style_template = 'lines-no-boxes';

	/**
	 * @var string $base_api_url
	 */
	private static $base_api_url = 'https://formidableforms.com/wp-json/style-templates/v1/list';

	/**
	 * @return string
	 */
	protected function api_url() {
		return self::$base_api_url;
	}

	/**
	 * @return void
	 */
	protected function set_cache_key() {
		$this->cache_key = 'frm_chat_style_templates_l' . ( empty( $this->license ) ? '' : md5( $this->license ) );
	}

	/**
	 * @return int|false style template id if there is a match, false otherwise.
	 */
	public function get_conversational_style() {
		$frm_style = new FrmStyle();
		$styles    = $frm_style->get_all();
		foreach ( $styles as $style ) {
			if ( self::$style_template === $style->post_name ) {
				return (int) $style->ID;
			}
		}
		return false;
	}

	/**
	 * @return bool true on success.
	 */
	public function install_style() {
		$style_url = $this->get_chat_form_style_url();
		if ( false === $style_url ) {
			return false;
		}

		$xml = $this->download_and_prepare_xml( $style_url );
		return (bool) FrmXMLHelper::import_xml_now( $xml, true );
	}

	/**
	 * @param string $url
	 * @return SimpleXMLElement
	 */
	private function download_and_prepare_xml( $url ) {
		$response = wp_remote_get( $url );
		$body     = wp_remote_retrieve_body( $response );
		return simplexml_load_string( $body );
	}

	/**
	 * @return string|false
	 */
	private function get_chat_form_style_url() {
		return $this->get_download_url_for_style( self::$style_template );
	}

	/**
	 * Get a style from style template API data.
	 *
	 * @param string $key
	 * @return string|false
	 */
	private function get_download_url_for_style( $key ) {
		$styles = $this->get_api_info();
		foreach ( $styles as $style ) {
			if ( is_array( $style ) && isset( $style['slug'] ) && $key === $style['slug'] ) {
				if ( ! empty( $style['url'] ) ) {
					return $style['url'];
				}
				break;
			}
		}
		return false;
	}
}
