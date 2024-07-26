<?php
/**
 * Custom checkbox field
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\models\fields;

use FrmAppHelper;
use FrmField;
use FrmProAppHelper;
use FrmProFieldCheckbox;
use FrmProFieldRadio;
use FrmSurveys\controllers\RadioController;
use FrmSurveys\helpers\AppHelper;
use FrmSurveys\helpers\FieldsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Extend Checkbox class.
 *
 * @since 1.0
 */
class Checkbox extends FrmProFieldCheckbox {

	/**
	 * Load field options.
	 *
	 * @since 1.0
	 */
	protected function extra_field_opts() {
		return array_merge(
			parent::extra_field_opts(),
			FieldsHelper::get_extra_display_format_options()
		);
	}

	/**
	 * Shows custom display format options.
	 *
	 * @param array $args Arguments.
	 */
	public function show_priority_field_choices( $args = array() ) {
		FieldsHelper::show_images_and_buttons_options( $args );
	}

	/**
	 * Show checkbox buttons in the form.
	 *
	 * @since 1.0
	 */
	protected function include_front_form_file() {
		return FieldsHelper::custom_include_front_form_file( (array) $this->field );
	}
}
