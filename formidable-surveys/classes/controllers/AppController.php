<?php
/**
 * App controller
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\controllers;

use FrmField;
use FrmSurveys\helpers\AppHelper;
use FrmSurveys\models\DB;
use FrmSurveys\models\Update;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class AppController
 */
class AppController {

	/**
	 * Init app.
	 */
	public static function initialize() {
		self::include_updater();
	}

	/**
	 * Tie into the main stylesheet updater. This is triggered when Formidable is updated
	 * and when the form css is loaded.
	 *
	 * @param bool $needs_upgrade - True if the stylesheet should be updated.
	 *
	 * @return bool - True if needs upgrade.
	 */
	public static function needs_upgrade( $needs_upgrade = false ) {
		if ( $needs_upgrade ) {
			return $needs_upgrade;
		}

		$db = new DB();
		return $db->need_to_migrate();
	}

	/**
	 * The Formidable update is running. Tie into it.
	 */
	public static function trigger_upgrade() {
		$db = new DB();
		if ( $db->need_to_migrate() ) {
			$db->migrate();
		}
	}

	/**
	 * Init translation.
	 */
	public static function init_translation() {
		load_plugin_textdomain( 'formidable-surveys', false, AppHelper::plugin_folder() . '/languages/' );
	}

	/**
	 * Shows incompatible notice.
	 */
	public static function show_incompatible_notice() {
		// translators: %1$s: plugin name.
		$message = __( 'You are running an outdated version of %1$s. The Formidable Surveys plugin will not work correctly if you do not update %1$s.', 'formidable-surveys' );

		if ( ! AppHelper::is_formidable_lite_compatible() ) :
			?>
			<div class="notice notice-error">
				<p><?php printf( esc_html( $message ), 'Formidable Forms' ); ?></p>
			</div>
			<?php
		endif;

		if ( ! AppHelper::is_formidable_pro_compatible() ) :
			?>
			<div class="notice notice-error">
				<p><?php printf( esc_html( $message ), 'Formidable Pro' ); ?></p>
			</div>
			<?php
		endif;
	}

	/**
	 * Update the Formidable stylesheet so it includes add-on styling.
	 */
	public static function update_stylesheet_on_activation() {
		if ( ! AppHelper::is_formidable_lite_compatible() || ! AppHelper::is_formidable_pro_compatible() ) {
			return;
		}

		$db = new DB();
		if ( ! $db->need_to_migrate() ) {
			$db->reduce_db_version();
		}
	}

	/**
	 * Link up Surveys as a Formidable add-on.
	 */
	private static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			Update::load_hooks();
		}
	}


	/**
	 * Changes options of Display format setting of Radio field.
	 *
	 * @since 1.0.09
	 *
	 * @param array $options The options array.
	 * @return array
	 */
	public static function change_field_display_format_options( $options ) {
		if ( isset( $options['buttons']['addon'] ) ) {
			unset( $options['buttons']['addon'] );
		}
		return $options;
	}

	/**
	 * Changes args of Display format setting of Radio field.
	 *
	 * @since 1.0.09
	 *
	 * @param array $args        The arguments.
	 * @param array $method_args The arguments from the method. Contains `field`, `options`.
	 * @return array
	 */
	public static function change_field_display_format_args( $args, $method_args ) {
		$field               = $method_args['field'];
		$args['selected']    = FrmField::get_option( $field, 'image_options' );
		$args['input_attrs'] = array(
			'name'     => 'field_options[image_options_' . intval( $field['id'] ) . ']',
			'class'    => 'frm_radio_display_format',
			'data-fid' => intval( $field['id'] ),
		);

		return $args;
	}

	/**
	 * Updates a flag that determines whether Bulk edit option should be visible on page load.
	 *
	 * @since 1.1.1
	 *
	 * @param bool   $should_hide_bulk_edit Whether to hide the bulk edit option.
	 * @param string $display_format        The current display format setting.
	 * @param array  $args                  Additional arguments. Includes field.
	 *
	 * @return bool
	 */
	public static function update_bulk_edit_visibility( $should_hide_bulk_edit, $display_format, $args ) {
		if ( $display_format === 'buttons' ) {
			return FrmField::get_option( $args['field'], 'use_images_in_buttons' ) === '1';
		}

		return $display_format === '1';
	}
}
