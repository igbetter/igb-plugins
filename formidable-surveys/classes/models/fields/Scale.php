<?php
/**
 * Custom scale field
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\models\fields;

use FrmField;
use FrmProFieldScale;
use FrmSurveys\helpers\FieldsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Extend Scale class.
 *
 * @since 1.0
 */
class Scale extends FrmProFieldScale {

	/**
	 * Load field options.
	 *
	 * @since 1.0.08
	 *
	 * @return array
	 */
	protected function extra_field_opts() {
		return array_merge(
			parent::extra_field_opts(),
			array(
				'image_options' => 0,
			)
		);
	}

	/**
	 * Shows custom display format options.
	 *
	 * @since 1.0.08
	 *
	 * @param array $args Arguments.
	 *
	 * @return void
	 */
	public function show_priority_field_choices( $args = array() ) {
		FieldsHelper::show_images_and_buttons_options( $args );
	}

	/**
	 * Probably update and then echo the Scale field options labels.
	 *
	 * @since 1.0.08
	 *
	 * @param string $opt The option label that is being printed.
	 *
	 * @return void
	 */
	public function echo_option_label( $opt ) {
		if ( 'buttons' === FrmField::get_option( $this->field, 'image_options' ) ) {
			// Add a wrapper div to change styling of the label.
			echo '<div class="frm_label_button_container">' . esc_html( $opt ) . '</div>';
			return;
		}

		echo esc_html( $opt );
	}

	/**
	 * Overriding the method here so that we can see the extra field settings section.
	 *
	 * @since 1.0.08
	 *
	 * @param array $field This parameter is not used.
	 *
	 * @return array
	 */
	public function displayed_field_type( $field ) {
		return array( 'scale' => true );
	}
}
