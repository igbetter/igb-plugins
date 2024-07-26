<?php
/**
 * NPS field type class
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\models\fields;

use FrmFieldType;
use FrmSurveys\helpers\AppHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Extend Field class.
 *
 * @since 1.0
 */
class NPS extends FrmFieldType {

	/**
	 * Set the field type key.
	 *
	 * @var string
	 */
	protected $type = 'nps';

	/**
	 * Don't include "for" in the HTML.
	 *
	 * @var bool
	 */
	protected $has_for_label = false;

	/**
	 * Which built-in settings this field supports?
	 *
	 * @return array
	 */
	protected function field_settings_for_type() {
		$settings = array(
			// Don't use the regular placeholder option.
			'clear_on_focus' => false,
			'logic'          => true,
			'visibility'     => true,
		);

		return $settings;
	}

	/**
	 * Gets extra field options.
	 *
	 * @return array
	 */
	protected function extra_field_opts() {
		$extra_options = parent::extra_field_opts();

		$extra_options['negative_statement'] = __( 'Not at all', 'formidable-surveys' );
		$extra_options['positive_statement'] = __( 'Extremely likely', 'formidable-surveys' );

		return $extra_options;
	}

	/**
	 * Set default field name for new fields.
	 *
	 * @since 1.0
	 */
	protected function get_new_field_name() {
		return __( 'How likely are you to recommend us to a friend or colleague?', 'formidable-surveys' );
	}

	/**
	 * Shows primary options.
	 *
	 * @since 1.0
	 *
	 * @param array $args Includes 'field', 'display', and 'values'.
	 */
	public function show_primary_options( $args ) {
		$field = $args['field'];
		include AppHelper::plugin_path() . '/classes/views/fields/backend/nps-options.php';
		parent::show_primary_options( $args );
	}

	/**
	 * Show NPS in form builder.
	 *
	 * @return string The file path to include on the form builder
	 */
	protected function include_form_builder_file() {
		return AppHelper::plugin_path() . '/classes/views/fields/frontend/nps.php';
	}

	/**
	 * Show NPS in the form.
	 *
	 * @since 1.0
	 */
	protected function include_front_form_file() {
		return AppHelper::plugin_path() . '/classes/views/fields/frontend/nps.php';
	}

	/**
	 * Add translatable strings for WPML.
	 */
	public function translatable_strings() {
		$strings   = parent::translatable_strings();
		$strings[] = 'negative_statement';
		$strings[] = 'positive_statement';
		return $strings;
	}

	/**
	 * Sanitizes value.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value Value need to sanitize.
	 */
	public function sanitize_value( &$value ) {
		if ( '' === $value ) {
			return;
		}

		$value = intval( $value );
		if ( $value < 0 ) {
			$value = 0;
		} elseif ( $value >= 10 ) {
			$value = 10;
		}
	}
}
