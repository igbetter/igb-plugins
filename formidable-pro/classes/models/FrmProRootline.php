<?php
/**
 * Rootline class
 *
 * @since 6.9
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProRootline {

	/**
	 * Rootline type.
	 *
	 * @var string
	 */
	public $type = 'progress';

	/**
	 * Rootline position.
	 *
	 * @var string
	 */
	public $position = '';

	/**
	 * Shows titles or not?
	 *
	 * @var bool
	 */
	public $show_titles = false;

	/**
	 * Shows numbers or not?
	 *
	 * @var bool
	 */
	public $show_numbers = true;

	/**
	 * Shows lines or not?
	 *
	 * @var bool
	 */
	public $show_lines = true;

	/**
	 * Rootline titles.
	 *
	 * @var array
	 */
	public $titles = array();

	/**
	 * Form ID.
	 *
	 * @var int
	 */
	public $form_id = 0;

	/**
	 * Form array.
	 *
	 * @var array
	 */
	public $form_array = array();

	/**
	 * Array of page break fields.
	 *
	 * @var array
	 */
	public $page_breaks = array();

	/**
	 * Constructor.
	 *
	 * @param array $form_array Form array.
	 */
	public function __construct( $form_array ) {
		$this->form_id    = intval( $form_array['id'] );
		$this->form_array = $form_array;

		$this->type         = FrmProFormsHelper::get_form_option( $this->form_id, 'rootline' );
		$this->position     = FrmProFormsHelper::get_form_option( $this->form_id, 'pagination_position' );
		$this->show_titles  = FrmProFormsHelper::get_form_option( $this->form_id, 'rootline_titles_on' );
		$this->show_numbers = ! FrmProFormsHelper::get_form_option( $this->form_id, 'rootline_numbers_off' );
		$this->show_lines   = ! FrmProFormsHelper::get_form_option( $this->form_id, 'rootline_lines_off' );
		$this->titles       = FrmProFormsHelper::get_form_option( $this->form_id, 'rootline_titles', array() );
		$this->page_breaks  = FrmField::get_all_types_in_form( $this->form_id, 'break' );
	}

	/**
	 * Is rootline enabled?
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ! empty( $this->type );
	}

	/**
	 * Shows backend output.
	 */
	public function backend_output() {
		$wrapper_attrs = $this->get_backend_wrapper_attrs();
		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-forms/rootline-backend.php';
	}

	/**
	 * Gets backend rootline wrapper attributes.
	 *
	 * @return array
	 */
	protected function get_backend_wrapper_attrs() {
		$attrs = array(
			'id'        => 'frm-backend-rootline',
			'class'     => 'frm-backend-rootline',
			'data-type' => $this->type,
		);

		if ( ! $this->show_lines ) {
			$attrs['class'] .= ' frm-rootline-no-lines';
		}

		if ( ! $this->show_titles ) {
			$attrs['class'] .= ' frm-rootline-no-titles';
		}

		if ( ! $this->show_numbers ) {
			$attrs['class'] .= ' frm-rootline-no-numbers';
		}

		return $attrs;
	}
}
