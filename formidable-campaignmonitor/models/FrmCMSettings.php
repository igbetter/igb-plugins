<?php
/**
 * Formidable Campaign Monitor Settings.
 *
 * @package CampaignMonitor
 * @author Strategy11
 */

/**
 * Get and save the Global settings.
 */
class FrmCMSettings {

	/**
	 * Campaign Monitor Settings Object
	 *
	 * @var stdClass
	 */
	public $settings;

	public function __construct() {
		$this->set_default_options();
	}

	private function default_options() {
		return array(
			'api_key'            => '',
			'client_api_key'     => '',
			'debug_mode'         => '',
		);
	}

	public function set_default_options( $settings = false ) {
		$default_settings = $this->default_options();

		if ( ! $settings ) {
			$settings = $this->get_options();
		} elseif ( true === $settings ) {
			$settings = new stdClass();
		}

		if ( ! isset( $this->settings ) ) {
			$this->settings = new stdClass();
		}

		foreach ( $default_settings as $setting => $default ) {
			if ( is_object( $settings ) && isset( $settings->{$setting} ) ) {
				$this->settings->{$setting} = $settings->{$setting};
			}

			if ( ! isset( $this->settings->{$setting} ) ) {
				$this->settings->{$setting} = $default;
			}
		}
	}

	public function get_options() {
		$settings = get_option( 'frm_campaignmonitor_options' );

		if ( empty( $settings ) ) {
			$this->set_default_options( true );
		} else {
			$this->settings = (object) $settings;
		}

		return $this->settings;
	}

	public function update() {
		$settings = $this->default_options();

		foreach ( $settings as $setting => $default ) {
			if ( isset( $_POST[ 'frm_campaignmonitor_' . $setting ] ) ) {
				$this->settings->{$setting} = FrmAppHelper::get_param( 'frm_campaignmonitor_' . $setting, '', 'post', 'sanitize_text_field' );
			}

			unset( $setting, $default );
		}
	}

	public function store() {
		// save settings.
		update_option( 'frm_campaignmonitor_options', $this->settings );
	}
}
