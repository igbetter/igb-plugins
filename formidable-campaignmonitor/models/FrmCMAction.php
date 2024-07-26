<?php
/**
 * Formidable Campaign Monitor form actions handler.
 *
 * @package CampaignMonitor
 */

/**
 * FrmCMAction Class creates and manages form actions.
 *
 * @category Class
 * @author Strategy11
 */
class FrmCMAction extends FrmFormAction {

	public function __construct() {
		$action_ops = array(
			'classes'  => 'frm_campaignmonitor_icon frm_icon_font',
			'limit'    => 99,
			'active'   => true,
			'priority' => 25,
			'event'    => array( 'create', 'update' ),
			'color'    => 'var(--purple)',
		);

		$this->FrmFormAction( 'campaignmonitor', 'Campaign Monitor', $action_ops );
	}

	public function form( $form_action, $args = array() ) {
		$form = $args['form'];

		$list_options = $form_action->post_content;
		$list_id      = $list_options['list_id'];

		$api = new FrmCMAPI();
		$lists = $api->get_lists();

		if ( ! empty( $list_id ) ) {
			$list_fields = $api->get_list_fields( $list_id );
		}

		if ( method_exists( $this, 'get_form_fields' ) ) {
			$form_fields = $this->get_form_fields( $form->id );
		} else {
			$form_fields = FrmField::getAll( 'fi.form_id=' . (int) $form->id . " and fi.type not in ('break', 'divider', 'end_divider', 'html', 'captcha', 'form')", 'field_order' );
		}

		$action_control = $this;

		include( FrmCMAppController::path() . '/views/action-settings/options.php' );
		include_once( FrmCMAppController::path() . '/views/action-settings/_action_scripts.php' );
	}

	public function get_defaults() {
		return array(
			'list_id'   => '',
			'fields'    => array(),
		);
	}

	public function get_switch_fields() {
		return array(
			'fields' => array(),
			'groups' => array( array( 'id' ) ),
		);
	}
}
