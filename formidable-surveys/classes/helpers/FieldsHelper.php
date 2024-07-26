<?php
/**
 * Fields helper
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\helpers;

use FrmAppHelper;
use FrmField;
use FrmProAppHelper;
use FrmSurveys\controllers\RadioController;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Helper functions for fields.
 */
class FieldsHelper {

	/**
	 * Gets extra options for radio and checkbox fields.
	 *
	 * @return string[]
	 */
	public static function get_extra_display_format_options() {
		return array(
			'use_images_in_buttons' => '',
			'image_align'           => 'left',
			'text_align'            => 'left',
		);
	}

	/**
	 * Shows custom display format options.
	 *
	 * @see FrmProImages::show_image_choices()
	 *
	 * @param array  $args Arguments.
	 * @param string $type The field type.
	 */
	public static function show_images_and_buttons_options( $args, $type = 'radio' ) {
		$field = $args['field'];
		if ( isset( $field['post_field'] ) && 'post_category' === $field['post_field'] ) {
			return;
		}

		echo '<div class="frm_grid_container frm_priority_field_choices frm_surveys_priority_field_choices" data-fid="' . intval( $field['id'] ) . '">';
		self::include_custom_display_settings( $field, $type );
		echo '</div>';
	}

	/**
	 * Includes the view file for displaying settings like the display format, use separate option value.
	 *
	 * @since 1.0.08
	 *
	 * @param array  $field The field.
	 * @param string $type  The field type.
	 *
	 * @return void
	 */
	private static function include_custom_display_settings( $field, $type ) {
		if ( 'scale' === $field['type'] ) {
			include AppHelper::plugin_path() . '/classes/views/fields/backend/scale-display-format.php';
			return;
		}
		include AppHelper::plugin_path() . '/classes/views/fields/backend/' . $type . '-options.php';
	}

	/**
	 * Custom front file path for radio and checkbox fields.
	 *
	 * @param array $field Field data.
	 * @return string
	 */
	public static function custom_include_front_form_file( $field ) {
		$default_path = FrmAppHelper::plugin_path() . '/classes/views/frm-fields/front-end/' . $field['type'] . '-field.php';
		if ( 'post_category' === FrmField::get_option( $field, 'post_field' ) ) {
			return $default_path;
		}

		if ( RadioController::is_image_enabled( $field ) ) {
			// Use image view when display as buttons with images and use CSS to change the display.
			return FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/front-end/image-options.php';
		}

		return $default_path;
	}

	/**
	 * Find a field in passed array. If it's missing, check the DB.
	 *
	 * @since 1.0
	 *
	 * @param int   $id     Field ID.
	 * @param array $fields The list of fields.
	 * @return object
	 */
	public static function get_field_from_array( $id, $fields ) {
		if ( is_array( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( $field->id === $id ) {
					return $field;
				}
			}
		}
		return FrmField::getOne( $id );
	}
}
