<?php
/**
 * Likert field class
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\models\fields;

use FrmAppHelper;
use FrmEntry;
use FrmEntryMeta;
use FrmFieldType;
use FrmProEntriesController;
use FrmProEntry;
use FrmProEntryMetaHelper;
use FrmSurveys\controllers\LikertController;
use FrmSurveys\helpers\AppHelper;
use FrmSurveys\helpers\FieldsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Extend Field class.
 *
 * @since 1.0
 */
class Likert extends FrmFieldType {

	/**
	 * Flag to check if likert script is loaded.
	 *
	 * @since 1.1.1
	 *
	 * @var bool
	 */
	private static $loaded_likert_script = false;

	/**
	 * Set the field type key.
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $type = 'likert';

	/**
	 * Does the html for this field label need to include "for"?
	 *
	 * @var bool
	 * @since 1.0
	 */
	protected $has_for_label = false;

	/**
	 * Gets default field options.
	 *
	 * @since 1.1.1
	 *
	 * @return array
	 */
	public function get_default_field_options() {
		$opts = parent::get_default_field_options();

		$opts['blank'] = __( 'Please complete all options in [field_name]', 'formidable-surveys' );
		return $opts;
	}

	/**
	 * Which built-in settings this field supports?
	 *
	 * @return array
	 */
	protected function field_settings_for_type() {
		$settings = array(
			'default_value'  => false,
			'default'        => false,

			// Don't use the regular placeholder option.
			'clear_on_focus' => false,
			'logic'          => true,
			'visibility'     => true,
		);

		return $settings;
	}

	/**
	 * Add new field option defaults.
	 */
	protected function extra_field_opts() {
		return array_merge(
			parent::extra_field_opts(),
			array(
				'multi_selection' => '',
				'inline_column'   => '',
				'separate_value'  => '',
			)
		);
	}

	/**
	 * Show field options in builder above collapsed settings.
	 *
	 * @param array $args Includes 'field' array.
	 */
	public function show_primary_options( $args ) {
		$field = (array) $args['field'];
		include AppHelper::plugin_path() . '/classes/views/fields/backend/likert-primary.php';

		parent::show_primary_options( $args );
	}

	/**
	 * Define parameters and include the field on form builder
	 *
	 * @since 1.0
	 *
	 * @param string $name  The field name.
	 * @param array  $field The field array.
	 */
	protected function include_on_form_builder( $name, $field ) {
		$field_name = $this->html_name( $name );
		$html_id    = $this->html_id();
		$read_only  = isset( $field['read_only'] ) ? $field['read_only'] : 0;

		$field['html_name']     = $field_name;
		$field['html_id']       = $html_id;
		FrmAppHelper::unserialize_or_decode( $field['default_value'] );

		$display = $this->display_field_settings();

		$replaces   = array();
		$row_fields = LikertController::get_row_fields( $this->field );

		foreach ( $row_fields as $row_field ) {
			$replaces[] = 'id="field_' . $row_field->field_key . '_label" ';
			unset( $row_field );
		}

		ob_start();
		include $this->include_form_builder_file();
		$output = ob_get_clean();

		// Remove id from row field labels to remove the conflict with clickLabel() in formidable_admin.js.
		echo str_replace( $replaces, '', $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Show field in the form builder.
	 *
	 * @since 1.0
	 */
	protected function include_form_builder_file() {
		return AppHelper::plugin_path() . '/classes/views/fields/backend/likert.php';
	}

	/**
	 * Show field in the form.
	 *
	 * @since 1.0
	 * @since 1.0.04 Load likert frontend js.
	 */
	protected function include_front_form_file() {
		return AppHelper::plugin_path() . '/classes/views/fields/frontend/likert.php';
	}

	/**
	 * Prepares the display value.
	 * This also handles the shortcode output. Support [id], [id row=1] (the first row), [id row="Column name"].
	 *
	 * @param mixed $value Field value before processing.
	 * @param array $atts  Shortcode attributes.
	 * @return string      Most of cases, this will return string.
	 */
	protected function prepare_display_value( $value, $atts ) {
		if ( ( empty( $atts['entry_id'] ) && empty( $atts['entry'] ) ) || empty( $this->field ) ) {
			return '';
		}

		$atts['row_ids'] = LikertController::get_row_ids( $this->field );

		$atts['field'] = $this->field;
		$row_values    = $this->get_value_from_row( $atts );

		// Format values when showing multiple rows.
		if ( empty( $atts['row'] ) ) {
			$display_value = '';

			/**
			 * Change the HTML for a likert field using the field name and value.
			 *
			 * @since 1.0
			 */
			$likert_html = apply_filters( 'frm_likert_html', "\n" . '<div><strong>%1$s:</strong> %2$s</div>' );

			foreach ( $row_values as $field_name => $value ) {
				$has_value = '' !== $value && array() !== $value && null !== $value;
				if ( $has_value ) {
					$display_value .= sprintf( $likert_html, esc_html( $field_name ), esc_html( $value ) );
				}
			}
			return $display_value;
		}

		return implode( ' ', $row_values );
	}

	/**
	 * Add likert field names and values into an array.
	 *
	 * @param array $args The arguments.
	 * @return array
	 */
	private function get_value_from_row( $args ) {
		$value = array();
		foreach ( $args['row_ids'] as $index => $id ) {
			$field   = FieldsHelper::get_field_from_array( $id, isset( $args['fields'] ) ? $args['fields'] : array() );
			$include = empty( $args['row'] ) || $args['row'] === $field->name || intval( $args['row'] ) === $index + 1;
			if ( $field && $include ) {
				$row_value = $this->get_entry_meta_value_from_args( $args, $field );
				if ( is_array( $row_value ) ) {
					$row_value = implode( $args['sep'], $row_value );
				}
				$value[ $field->name ] = FrmProEntriesController::get_option_label_for_saved_value( $row_value, $field, $args );
			}

			unset( $field );
		}
		return $value;
	}

	/**
	 * Gets entry meta value from arguments of LikertController::get_likert_display_value().
	 *
	 * @param array  $args  The arguments.
	 * @param object $field Field object.
	 * @return mixed
	 */
	private function get_entry_meta_value_from_args( $args, $field ) {
		if ( ! empty( $args['entry'] ) ) {
			$entry = $args['entry'];
		} elseif ( ! empty( $args['entry_id'] ) ) {
			$entry = FrmEntry::getOne( $args['entry_id'] );
		}

		if ( empty( $entry ) ) {
			return '';
		}

		if ( ! isset( $entry->metas ) ) {
			$entry = FrmEntry::get_meta( $entry );
		}

		if ( isset( $entry->metas[ $field->id ] ) ) {
			// When use Summary field.
			return $entry->metas[ $field->id ];
		}

		if ( intval( $field->form_id ) === intval( $entry->form_id ) ) {
			return FrmEntryMeta::get_meta_value( $entry, $field->id );
		}

		// Get entry ids linked through repeater field or embedded form. Copied from FrmProContent::replace_single_shortcode().
		$child_entries = FrmProEntry::get_sub_entries( $entry->id, true );
		$child_value   = FrmProEntryMetaHelper::get_sub_meta_values( $child_entries, $field, $args );
		return FrmAppHelper::array_flatten( $child_value );
	}

	/**
	 * Add translatable strings for WPML.
	 */
	public function translatable_strings() {
		$strings = parent::translatable_strings();
		return $strings;
	}

	/**
	 * Default Customize HTML setting.
	 *
	 * @return string
	 */
	public function default_html() {
		if ( ! $this->has_html ) {
			return '';
		}

		$input = $this->input_html();
		$for   = $this->for_label_html();
		$label = $this->primary_label_element();

		$default_html = <<<DEFAULT_HTML
<div id="frm_field_[id]_container" class="frm_form_field form-field [required_class][error_class]">
    <$label $for id="field_[key]_label" class="frm_primary_label">[field_name]
        <span class="frm_required" aria-hidden="true">[required_label]</span>
    </$label>
    $input
    [if description]<div class="frm_description" id="frm_desc_field_[key]">[description]</div>[/if description]
    [if error]<div class="frm_error" id="frm_error_field_[key]">[error]</div>[/if error]
</div>
DEFAULT_HTML;

		return $default_html;
	}

	/**
	 * Loads field scripts.
	 *
	 * @since 1.1.1
	 *
	 * @param array $args Args.
	 */
	protected function load_field_scripts( $args ) {
		if ( FrmAppHelper::doing_ajax() ) {
			// Print script instead of enqueuing when loading field via AJAX. Check to not print multiple scripts.
			if ( ! self::$loaded_likert_script ) {
				// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
				echo '<script src="' . esc_url( AppHelper::plugin_url() ) . '/js/frm-surveys-likert.js?ver=' . esc_attr( AppHelper::$plug_version ) . '" id="frm-surveys-likert-js"></script>';
				self::$loaded_likert_script = true;
			}
		} else {
			wp_enqueue_script( 'frm-surveys-likert' );
		}
	}
}
