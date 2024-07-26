<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 5.3
 */
class FrmViewsSettings extends FrmSettings {

	/**
	 * @var string
	 */
	public $option_name = 'frmviews_options';

	/**
	 * @var string Option for customizing text in a Load More button (AJAX Pagination).
	 */
	public $load_more_button_text;

	/**
	 * @return array<string,string>
	 */
	public function default_options() {
		return array(
			'load_more_button_text' => __( 'Load More', 'formidable-views' ),
		);
	}

	public function set_default_options() {
		$this->fill_with_defaults();
	}

	/**
	 * @param array $params
	 */
	public function fill_with_defaults( $params = array() ) {
		$params['additional_filter_keys'] = array( 'load_more_button_text' );
		parent::fill_with_defaults( $params );
	}

	/**
	 * @param array $params
	 * @return void
	 */
	public function update( $params ) {
		if ( isset( $params['load_more_button_text'] ) ) {
			$this->load_more_button_text = $params['load_more_button_text'];
		}

		$this->fill_with_defaults( $params );
	}

	/**
	 * @return void
	 */
	public function store() {
		update_option( $this->option_name, $this, 'no' );
		delete_transient( $this->option_name );
		set_transient( $this->option_name, $this );
	}
}
