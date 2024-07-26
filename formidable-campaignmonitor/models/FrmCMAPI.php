<?php
/**
 * Formidable Campaign Monitor communication with Campaign Monitor API.
 *
 * @package CampaignMonitor
 * @author Strategy11
 */

/**
 * FrmCMAPI Class Communicates with Campaign Monitor API.
 */
class FrmCMAPI {

	/**
	 * The id of the entry for the current API request.
	 *
	 * @var int
	 */
	protected $entry_id = 0;

	/**
	 * The form action object for the current api request.
	 *
	 * @var object
	 */
	protected $action;

	/**
	 * The Frm_Campaign_Monitor class for making API calls.
	 *
	 * @var Frm_Campaign_Monitor
	 */
	protected $api;

	/**
	 * The API key from the global settings.
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * The Client ID from the global settings.
	 *
	 * @var string
	 */
	protected $client_id;

	/**
	 * Is debug mode turned on in the global settings?
	 *
	 * @var bool
	 */
	protected $debug = false;

	public function __construct( $atts = array() ) {
		$settings = new FrmCMSettings();
		$this->api_key   = $settings->settings->api_key;
		$this->client_id = $settings->settings->client_api_key;
		$this->debug     = 'yes' === $settings->settings->debug_mode;

		if ( isset( $atts['entry_id'] ) ) {
			$this->entry_id = $atts['entry_id'];
		}

		if ( isset( $atts['action'] ) ) {
			$this->action = $atts['action'];
		}
	}

	public function get_lists() {
		$lists = get_transient( 'frm_cm_lists' );
		if ( ! empty( $lists ) ) {
			return $lists;
		}

		$lists = array();
		$this->include_api();
		if ( empty( $this->api ) ) {
			return;
		}

		try {
			$lists = $this->api->get_lists();
			set_transient( 'frm_cm_lists', $lists, 60 * 60 * 60 );
		} catch ( Exception $e ) {
			$this->show_error( $e->getMessage() );
		}

		return $lists;
	}

	public function get_list_fields( $list_id ) {
		$custom_fields = get_transient( 'frm_cm_list_fields' );
		if ( ! empty( $custom_fields ) ) {
			return $custom_fields;
		}

		$this->include_api();
		if ( empty( $this->api ) ) {
			return;
		}

		try {
			$custom_fields = $this->api->get_list_custom_fields( $list_id );
			$custom_fields = array_merge( $this->base_fields(), $custom_fields );
		} catch ( Exception $e ) {
			$this->show_error( $e->getMessage() );
		}

		set_transient( 'frm_cm_list_fields', $custom_fields, 60 * 60 * 60 );

		return $custom_fields;
	}

	/**
	 * Include the non-custom fields too.
	 */
	private function base_fields() {
		return array(
			array(
				'name' => __( 'Email Address', 'formidable' ),
				'req'  => true,
				'tag'  => 'email',
			),
			array(
				'name' => __( 'Name', 'formidable' ),
				'req'  => true,
				'tag'  => 'fullname',
			),
		);
	}

	public function add_subscriber( $list_id, $subscriber ) {
		$this->include_api();
		if ( empty( $this->api ) ) {
			return;
		}

		try {
			$result = $this->api->subscribe( $list_id, $subscriber );
			$this->log_results(
				array(
					'message' => 'Success',
					'code'    => 200,
					'request' => $subscriber,
				)
			);
		} catch ( Exception $e ) {
			$this->log_results(
				array(
					'message' => $e->getMessage(),
					'code'    => 400,
					'request' => $subscriber,
				)
			);

			if ( $this->debug ) {
				$this->show_error( $e->getMessage() );
			}
		}
	}

	/**
	 * Include the Campaign Monitor SDK.
	 */
	private function include_api() {
		if ( ! class_exists( 'Frm_Campaign_Monitor' ) ) {
			require_once( FrmCMAppController::path() . '/api/campaign-monitor.php' );
		}

		if ( ! empty( $this->api ) ) {
			return;
		}

		try {
			$this->api = new Frm_Campaign_Monitor( $this->api_key, $this->client_id );
		} catch ( Exception $e ) {
			$this->log_results(
				array(
					'message' => $e->getMessage(),
					'code'    => 400,
				)
			);

			if ( $this->debug || $this->is_settings_page() ) {
				$this->show_error( $e->getMessage() );
			}
		}
	}

	/**
	 * If this is an admin settings page, show the error message.
	 */
	private function is_settings_page() {
		$is_settings   = FrmAppHelper::is_formidable_admin();
		$adding_action = false;
		if ( FrmAppHelper::doing_ajax() ) {
			$action = FrmAppHelper::get_param( 'action', '', 'post', 'sanitize_text_field' );
			$adding_action = 'frm_add_form_action' === $action || 'frm_add_form_action' === $action;
		}

		return $is_settings || $adding_action;
	}

	private function show_error( $response ) {
		echo '<pre>';
		echo esc_html( print_r( $response, 1 ) );
		echo '</pre>';
		wp_die();
	}

	private function log_results( $atts ) {
		if ( ! class_exists( 'FrmLog' ) || empty( $this->entry_id ) ) {
			return;
		}

		$content = array(
			'title'   => 'Campaign Monitor: ' . $this->action->post_title,
			'content' => $atts['message'],
			'fields'  => array(
				'entry'   => $this->entry_id,
				'action'  => $this->action->ID,
				'code'    => isset( $atts['code'] ) ? $atts['code'] : '',
			),
		);

		if ( isset( $atts['request'] ) ) {
			$content['fields']['request'] = json_encode( $atts['request'] );
		}

		$log = new FrmLog();
		$log->add( $content );
	}
}
