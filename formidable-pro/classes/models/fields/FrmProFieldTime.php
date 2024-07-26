<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 3.0
 */
class FrmProFieldTime extends FrmFieldType {

	/**
	 * @var string
	 * @since 3.0
	 */
	protected $type = 'time';

	/**
	 * Fix WCAG errors when multiple dropdowns for the time field.
	 *
	 * @var bool
	 * @since 3.06.01
	 */
	protected $has_for_label = false;

	/**
	 * @var bool
	 */
	protected $array_allowed = false;

	public function show_on_form_builder( $name = '' ) {
		$field          = FrmFieldsHelper::setup_edit_vars( $this->field );
		$field['value'] = $field['default_value'];

		$field_name = $this->html_name( $name );
		$html_id    = $this->html_id();

		$this->show_time_field( compact( 'field', 'html_id', 'field_name' ) );
	}

	protected function field_settings_for_type() {
		$settings = array(
			'autopopulate' => true,
			'size'         => true,
			'unique'       => true,
			'read_only'    => true,
			'invalid'      => true,
		);

		FrmProFieldsHelper::fill_default_field_display( $settings );
		return $settings;
	}

	/**
	 * Registers extra field options.
	 *
	 * @since 6.9 Added `step_unit`.
	 *
	 * @return array
	 */
	protected function extra_field_opts() {
		return array(
			'start_time'  => '00:00',
			'end_time'    => '23:30',
			'clock'       => 12,
			'single_time' => 0,
			'step'        => 30,
			'step_unit'   => FrmProTimeFieldsController::STEP_UNIT_MINUTE,
		);
	}

	/**
	 * @since 4.0
	 * @param array $args - Includes 'field', 'display', and 'values'
	 */
	public function show_primary_options( $args ) {
		$field = $args['field'];
		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/clock-settings.php';

		$this->auto_width_setting( $args );

		parent::show_primary_options( $args );
	}

	/**
	 * @since 4.0
	 */
	public function default_value_to_string( &$default_value ) {
		if ( is_array( $default_value ) ) {
			$this->time_array_to_string( $default_value );
		}
	}

	protected function fill_default_atts( &$atts ) {
		$defaults = array(
			'format' => $this->get_time_format_for_field(),
		);

		$atts = wp_parse_args( $atts, $defaults );
	}

	public function prepare_front_field( $values, $atts ) {
		$values['options'] = $this->get_options( $values );
		$values['value']   = $this->prepare_field_value( $values['value'], $atts );

		return $values;
	}

	public function prepare_field_value( $value, $atts ) {
		return $this->get_display_value( $value, $atts );
	}

	public function get_options( $values ) {
		if ( empty( $values ) ) {
			// use a text field for conditional logic
			return parent::get_options( $values );
		}

		$this->prepare_time_settings( $values );

		$step_unit = FrmProTimeFieldsController::get_step_unit( $values );

		if ( FrmProTimeFieldsController::STEP_UNIT_MINUTE !== $step_unit ) {
			$values['step_unit'] = $step_unit;

			// Currently, we don't support single dropdown if step unit isn't minute.
			return $this->get_multiple_options_with_sec_or_millisec( $values );
		}

		$options = array();
		$this->get_single_time_field_options( $values, $options );

		$use_single_dropdown = FrmField::is_option_true( $values, 'single_time' );
		if ( ! $use_single_dropdown ) {
			$this->get_multiple_time_field_options( $values, $options );
		}

		return $options;
	}

	/**
	 * Gets options for multiple dropdowns when step unit is second or millisecond.
	 *
	 * @since 6.9
	 *
	 * @param array $values Processed field values.
	 * @return array Array contains `H`, `m`, `s`, and maybe `ms`.
	 */
	private function get_multiple_options_with_sec_or_millisec( $values ) {
		$time_options = new FrmProTimeOptions( $values );

		$options = array(
			'H' => $time_options->get_hour_options(),
			'm' => $time_options->get_minute_options(),
			's' => $time_options->get_second_options(),
		);

		// Add an empty option at the top of each dropdown.
		$this->add_empty_options( $options['H'] );
		$this->add_empty_options( $options['m'] );
		$this->add_empty_options( $options['s'] );

		if ( FrmProTimeFieldsController::STEP_UNIT_MILLISECOND === $values['step_unit'] ) {
			$options['ms'] = $time_options->get_millisecond_options();
			$this->add_empty_options( $options['ms'] );
		}

		$this->maybe_add_am_pm_options( $options, $values );

		return $options;
	}

	/**
	 * Adds an empty option at the top of options.
	 *
	 * @since 6.9
	 *
	 * @param array $options Array of options.
	 */
	private function add_empty_options( &$options ) {
		array_unshift( $options, '' );
	}

	public function front_field_input( $args, $shortcode_atts ) {
		ob_start();

		$this->show_time_field(
			array(
				'html_id'    => $args['html_id'],
				'field_name' => $args['field_name'],
			)
		);
		$input_html = ob_get_contents();
		ob_end_clean();

		return $input_html;
	}

	/**
	 * @param array $values
	 */
	private function show_time_field( $values ) {
		if ( isset( $values['field'] ) ) {
			$field = $values['field'];
		} else {
			$field           = $this->field;
			$values['field'] = $field;
		}

		$values['field_value'] = $field['value'];
		$this->set_field_column( 'options', $field['options'] );

		$hidden = $this->maybe_include_hidden_values( $values );
		$this->maybe_format_time( $values['field_value'] );

		if ( isset( $field['options']['H'] ) ) {
			$this->time_string_to_array( $values['field_value'] );
			$this->time_string_to_array( $values['field']['default_value'] );

			$html  = '<div class="frm_time_wrap">';
			$html .= '<span dir="ltr">' . "\r\n";

			$values['combo_name'] = 'H';
			$html                .= $this->get_time_component_html( $values, $field['name'], __( 'hour', 'formidable-pro' ) );

			$this->add_dropdown_separator( $html );

			$values['combo_name'] = 'm';
			$html                .= $this->get_time_component_html( $values, $field['name'], __( 'minute', 'formidable-pro' ) );

			// Add second dropdown.
			if ( isset( $field['options']['s'] ) ) {
				$this->add_dropdown_separator( $html );

				$values['combo_name'] = 's';
				$html                .= $this->get_time_component_html( $values, $field['name'], __( 'second', 'formidable-pro' ) );
			}

			// Add millisecond dropdown.
			if ( isset( $field['options']['ms'] ) ) {
				$this->add_dropdown_separator( $html );

				$values['combo_name'] = 'ms';
				$html                .= $this->get_time_component_html( $values, $field['name'], __( 'millisecond', 'formidable-pro' ) );
			}

			$html .= '</span>' . "\r\n";

			if ( isset( $field['options']['A'] ) ) {
				$values['combo_name'] = 'A';
				$html                .= $this->get_time_component_html( $values, $field['name'] );
			}

			$html .= '</div>';
		} else {
			$labeled_by = 'aria-labelledby="' . esc_attr( $values['html_id'] ) . '_label" ';
			$this->time_array_to_string( $values['field_value'] );
			$html = $this->get_select_box( $values );
			$html = str_replace( '<select ', '<select ' . $labeled_by, $html );
		}

		echo $hidden . $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Adds dropdown separator.
	 *
	 * @since 6.9
	 *
	 * @param string $html Time field HTML.
	 */
	private function add_dropdown_separator( &$html ) {
		// Use aria-hidden so a screen reader doesn't read the colon out loud.
		$html .= '<span class="frm_time_sep" aria-hidden="true">:</span>' . "\r\n";
	}

	/**
	 * Composes the html for a single time field, like hour or minute.
	 *
	 * @since 6.1.2
	 *
	 * @param array $values
	 * @param string $field_name
	 * @param string $time_string
	 *
	 * @return string
	 */
	private function get_time_component_html( $values, $field_name, $time_string = '' ) {
		$select_box = $this->get_select_box( $values ) . "\r\n";
		$aria_label = 'aria-label="' . esc_attr( $field_name );
		if ( $time_string ) {
			$aria_label .= ' ... ' . esc_attr( $time_string );
		}
		$aria_label .= '" ';
		return str_replace( '<select ', '<select ' . $aria_label, $select_box );
	}

	/**
	 * If the value was in a hidden field on a previous page,
	 * it may still be in the database format
	 *
	 * @since 3.02.01
	 */
	private function maybe_format_time( &$time ) {
		if ( ! is_array( $time ) && ! strpos( $time, ' ' ) ) {
			$time = $this->get_display_value(
				$time,
				array(
					'format' => $this->get_time_format_for_field(),
				)
			);
		}
	}

	/**
	 * Add extra classes on front-end input
	 *
	 * @since 3.01.04
	 */
	protected function get_input_class() {
		$class       = '';
		$is_separate = $this->get_field_column( 'options' );
		$combo_name  = FrmField::get_option( $this->field, 'combo_name' );
		if ( isset( $is_separate['H'] ) || ! empty( $combo_name ) ) {
			$class = 'auto_width frm_time_select';
		}

		return $class;
	}

	protected function show_readonly_hidden() {
		return true;
	}

	public function validate( $args ) {
		$errors = array();

		if ( is_array( $args['value'] ) ) {
			$this->time_array_to_string( $args['value'] );
			FrmEntriesHelper::set_posted_value( $this->field, $args['value'], $args );
		}

		$is_required = FrmField::is_required( (array) $this->field );
		$is_empty    = ! is_array( $args['value'] ) && trim( $args['value'] ) == '';
		if ( $is_required && $is_empty ) {
			$errors[ 'field' . $args['id'] ] = FrmFieldsHelper::get_error_msg( $this->field, 'blank' );
		} elseif ( ! $is_empty && ! $this->in_time_range( $args['value'] ) ) {
			$errors[ 'field' . $args['id'] ] = FrmFieldsHelper::get_error_msg( $this->field, 'invalid' );
		}

		return $errors;
	}

	private function in_time_range( $time ) {
		$values = $this->field->field_options;
		$this->fill_start_end_times( $values );

		$time_format = $this->get_time_format_for_field( array(), true );
		$time        = $this->maybe_format_time_with_ms( $time, $time_format );
		return $time >= $values['start_time'] && $time <= $values['end_time'];
	}

	/**
	 * Maybe format time with millisecond, this is added because strtotime() doesn't support millisecond.
	 *
	 * @since 6.9
	 *
	 * @param string $time   Time string.
	 * @param string $format The desire time format.
	 * @return string
	 */
	public function maybe_format_time_with_ms( $time, $format = 'H:i' ) {
		if ( ! strpos( $format, 'v' ) ) {
			// If the desire format doesn't contain millisecond, use the old method.
			return FrmProAppHelper::format_time( $time, $format );
		}

		$parts = explode( ':', $time );
		if ( 4 > count( $parts ) ) {
			// If the source time string doesn't contain millisecond, use the old method.
			return FrmProAppHelper::format_time( $time, $format );
		}

		// Get the millisecond part, format without millisecond, then replace the millisecond back.
		$ms_am = explode( ' ', $parts[3] );

		$time_without_ms = $parts[0] . ':' . $parts[1] . ':' . $parts[2];
		if ( ! empty( $ms_am[1] ) ) {
			$time_without_ms = $time_without_ms . ' ' . $ms_am[1];
		}
		$formatted_time = FrmProAppHelper::format_time( $time_without_ms, $format );
		return str_replace( '000', $ms_am[0], $formatted_time );
	}

	private function prepare_time_settings( &$values ) {
		$this->fill_start_end_times( $values );

		$values['start_time_str'] = $values['start_time'];
		$values['end_time_str']   = $values['end_time'];

		$step_unit = FrmProTimeFieldsController::get_step_unit( $values );

		$this->split_time_setting( $values['start_time'] );
		$this->split_time_setting( $values['end_time'] );

		// Step could be string of H:i:s:ms, convert it to int.
		$this->convert_step_to_int( $values['step'], $step_unit );

		$this->set_step( $values['step'] );

		if ( FrmProTimeFieldsController::STEP_UNIT_MINUTE === $step_unit ) {
			$values['hour_step'] = floor( $values['step'] / 60 );
			if ( ! $values['hour_step'] ) {
				$values['hour_step'] = 1;
			}
		}

		if ( $values['end_time'][0] < $values['start_time'][0] ) {
			$values['end_time'][0] += 12;
		}
	}

	private function fill_start_end_times( &$values ) {
		$values['clock']      = isset( $values['clock'] ) ? $values['clock'] : 12;
		$values['start_time'] = isset( $values['start_time'] ) ? $values['start_time'] : '';
		$values['end_time']   = isset( $values['end_time'] ) ? $values['end_time'] : '';
		$step_unit            = FrmProTimeFieldsController::get_step_unit( $values );
		$this->format_time( $this->get_default_time_str( $step_unit ), $values['start_time'], $step_unit );
		$this->format_time( $this->get_default_time_str( $step_unit, true ), $values['end_time'], $step_unit );
	}

	public function is_not_unique( $value, $entry_id ) {
		$used  = false;
		$value = $this->maybe_format_time_with_ms( $value, $this->get_time_format_for_field( array(), true ) );

		if ( FrmProEntryMetaHelper::value_exists( $this->get_field_column( 'id' ), $value, false ) ) {

			$first_date_field = FrmProFormsHelper::has_field( 'date', $this->get_field_column( 'form_id' ) );

			if ( $first_date_field ) {
				$item_meta = FrmAppHelper::get_post_param( 'item_meta', array() );

				$values = array(
					'time_field' => 'field_' . $this->field->field_key,
					'date_field' => 'field_' . $first_date_field->field_key,
					'time_key'   => $this->field->id,
					'date_key'   => $first_date_field->id,
					'date'       => isset( $item_meta[ $first_date_field->id ] ) ? sanitize_text_field( $item_meta[ $first_date_field->id ] ) : '', //TODO: repeat name
					'time'       => $value,
					'entry_id'   => $entry_id,
				);

				$not_allowed = array();
				$this->get_disallowed_times( $values, $not_allowed );
				if ( ! empty( $not_allowed ) ) {
					$used = true;
				}
			} else {
				$used = true;
			}
		}

		return $used;
	}

	/**
	 * Prepare the global time field JS information
	 *
	 * @since 3.0
	 *
	 * @param array $values
	 */
	protected function load_field_scripts( $values ) {
		if ( $this->field['unique'] && $this->field['single_time'] && isset( $values['html_id'] ) ) {
			global $frm_vars;

			if ( ! isset( $frm_vars['timepicker_loaded'] ) || ! is_array( $frm_vars['timepicker_loaded'] ) ) {
				$frm_vars['timepicker_loaded'] = array();
			}

			if ( ! isset( $frm_vars['timepicker_loaded'][ $values['html_id'] ] ) ) {
				$frm_vars['timepicker_loaded'][ $values['html_id'] ] = true;
			}
		}
	}

	/**
	 * @param array $values
	 * @param array $remove
	 * @return void
	 */
	public function get_disallowed_times( $values, &$remove ) {
		$values['date'] = FrmProAppHelper::maybe_convert_to_db_date( $values['date'], 'Y-m-d' );

		$remove = apply_filters( 'frm_allowed_times', $remove, $values );
		array_walk_recursive( $remove, 'FrmProAppHelper::format_time_by_reference' );

		$values['date_entries'] = $this->get_entry_ids_for_date( $values );
		if ( empty( $values['date_entries'] ) ) {
			return;
		}

		$used_times = $this->get_used_times_for_entries( $values );
		if ( empty( $used_times ) ) {
			return;
		}

		$number_allowed = apply_filters( 'frm_allowed_time_count', 1, $values['time_key'], $values['date_key'] );
		$count          = array();
		foreach ( $used_times as $used ) {
			if ( isset( $remove[ $used ] ) ) {
				continue;
			}

			if ( ! isset( $count[ $used ] ) ) {
				$count[ $used ] = 0;
			}
			++$count[ $used ];

			if ( $count[ $used ] >= $number_allowed ) {
				$remove[ $used ] = $used;
			}
		}
	}

	private function get_entry_ids_for_date( $values ) {
		$query = array( 'meta_value' => $values['date'] );
		FrmProEntryMeta::add_field_to_query( $values['date_key'], $query );

		return FrmEntryMeta::getEntryIds( $query );
	}

	private function get_used_times_for_entries( $values ) {
		$query = array( 'it.item_id' => $values['date_entries'] );
		FrmProEntryMeta::add_field_to_query( $values['time_key'], $query );

		if ( $values['entry_id'] ) {
			$query['it.item_id !'] = $values['entry_id'];
		}
		if ( ! empty( $values['time'] ) ) {
			$query['meta_value'] = $values['time'];
		}

		global $wpdb;
		$select = $wpdb->prefix . 'frm_item_metas it';
		if ( ! is_numeric( $values['time_key'] ) ) {
			$select .= ' LEFT JOIN ' . $wpdb->prefix . 'frm_fields fi ON (it.field_id = fi.id)';
		}

		$used_times = FrmDb::get_col( $select, $query, 'meta_value' );
		return $used_times;
	}

	private function split_time_setting( &$time ) {
		$format = $this->get_time_format_for_field( array(), true );
		$time   = $this->maybe_format_time_with_ms( $time, $format );

		$separator = ':';
		$time      = explode( $separator, $time );
	}

	private function step_in_minutes( &$step ) {
		$separator = ':';
		$step      = explode( $separator, $step );
		$step      = isset( $step[1] ) ? ( $step[0] * 60 ) + $step[1] : $step[0];
		if ( empty( $step ) ) {
			// force an hour step if none was defined to prevent infinite loop
			$step = 60;
		}
	}

	/**
	 * Converts step value to int if step is a string.
	 *
	 * @since 6.9
	 *
	 * @param int|string $step      Step in setting.
	 * @param string     $step_unit Step unit.
	 */
	private function convert_step_to_int( &$step, $step_unit ) {
		if ( FrmProTimeFieldsController::STEP_UNIT_MINUTE === $step_unit ) {
			$this->step_in_minutes( $step );
			return;
		}

		if ( is_numeric( $step ) ) {
			return;
		}

		$separator = ':';
		$step      = explode( $separator, $step );

		$hour      = $step[0];
		$min       = isset( $step[1] ) ? $step[1] : 0;
		$sec       = isset( $step[2] ) ? $step[2] : 0;
		$milli_sec = isset( $step[3] ) ? $step[3] : 0;

		if ( FrmProTimeFieldsController::STEP_UNIT_MILLISECOND === $step_unit ) {
			$step = $milli_sec + 1000 * $sec + 60000 * $min + 3600000 * $hour;
		} elseif ( FrmProTimeFieldsController::STEP_UNIT_SECOND === $step_unit ) {
			$step = $sec + 60 * $min + 3600 * $hour;
		}
	}

	/**
	 * @param array $options
	 * @return void
	 */
	private function get_single_time_field_options( $values, &$options ) {
		$time     = strtotime( $values['start_time_str'] );
		$end_time = strtotime( $values['end_time_str'] );
		$format   = $values['clock'] == 24 ? 'H:i' : 'g:i A';

		$this->set_step( $values['step'] );
		$values['step'] = max( $values['step'] * 60, 60 ); //switch minutes to seconds

		$options[] = '';
		while ( $time <= $end_time ) {
			$options[] = gmdate( $format, $time );
			$time     += $values['step'];
		}
	}

	/**
	 * @since 4.04.04
	 */
	private function set_step( &$step ) {
		if ( ! is_numeric( $step ) ) {
			$step = 30;
		}
	}

	private function get_multiple_time_field_options( $values, &$options ) {
		$all_times = $options;

		$options['H'] = array( '' );
		$options['m'] = array( '' );

		$this->get_hours( $all_times, $options );
		$this->get_minutes( $all_times, $options );

		$this->maybe_add_am_pm_options( $options, $values );
	}

	/**
	 * Maybe add AM/PM options.
	 *
	 * @since 6.9
	 *
	 * @param array $options Time options.
	 * @param array $values  Processed field values.
	 */
	private function maybe_add_am_pm_options( &$options, $values ) {
		if ( 24 === intval( $values['clock'] ) ) {
			return;
		}

		if ( intval( $values['start_time'][0] ) < 12 && intval( $values['end_time'][0] ) < 12 ) {
			$options['A'] = array( 'AM' );
			return;
		}

		if ( intval( $values['start_time'][0] ) > 11 && intval( $values['end_time'][0] ) > 11 ) {
			$options['A'] = array( 'PM' );
			return;
		}

		$options['A'] = array( 'AM', 'PM' );
	}

	/**
	 * Get the hour options for a three-dropdown time field
	 *
	 * @since 3.0
	 *
	 * @param array $all_times
	 * @param array $options
	 */
	private function get_hours( $all_times, &$options ) {
		foreach ( $all_times as $time ) {
			if ( $time == '' ) {
				$options['H'][] = '';
				continue;
			}

			$colon_position = strpos( $time, ':' );
			if ( $colon_position !== false ) {
				$hour           = substr( $time, 0, $colon_position );
				$options['H'][] = $hour;
			}
		}
		unset( $time );

		$options['H'] = array_unique( $options['H'] );
	}

	/**
	 * Get the minute options for a three-dropdown time field
	 *
	 * @since 3.0
	 *
	 * @param array $all_times
	 * @param array $options
	 */
	private function get_minutes( $all_times, &$options ) {

		foreach ( $all_times as $time ) {

			if ( $time == '' ) {
				$options['m'][] = '';
				continue;
			}

			$colon_position = strpos( $time, ':' );
			if ( $colon_position !== false ) {

				$minute = substr( $time, $colon_position + 1 );
				if ( strpos( $minute, 'M' ) ) {
					// AM/PM is included, so strip it off
					$minute = str_replace( array( ' AM', ' PM' ), '', $minute );
				}

				$options['m'][] = $minute;
			}
		}
		unset( $time );

		$options['m'] = array_unique( $options['m'] );
		sort( $options['m'] );
	}

	/**
	 * Format the start and end time
	 *
	 * @since 3.0
	 * @since 6.9 Added `$step_unit` parameter.
	 *
	 * @param string $default
	 * @param string $time
	 * @param string $step_unit Step unit.
	 */
	private function format_time( $default, &$time, $step_unit = '' ) {
		$str_length = $this->get_time_str_length( $step_unit );
		if ( strlen( $time ) === $str_length - 1 && substr( $time, 1, 1 ) === ':' ) {
			$time = '0' . $time;
		} elseif ( ! preg_match( $this->get_time_str_regex( $step_unit ), $time ) || strlen( $time ) !== $str_length || $time === '' ) {
			$time = $default;
		}
	}

	/**
	 * Gets default time string from given step unit and type of time.
	 *
	 * @since 6.9
	 *
	 * @param string $step_unit Step unit.
	 * @param bool   $end       Is `true` if this is end time.
	 * @return string
	 */
	private function get_default_time_str( $step_unit, $end = false ) {
		if ( FrmProTimeFieldsController::STEP_UNIT_SECOND === $step_unit ) {
			return $end ? '23:59:59' : '00:00:00';
		}

		if ( FrmProTimeFieldsController::STEP_UNIT_MILLISECOND === $step_unit ) {
			return $end ? '23:59:59:999' : '00:00:00:000';
		}

		return $end ? '23:59' : '00:00';
	}

	/**
	 * Gets time string length based on given time unit.
	 *
	 * @since 6.9
	 *
	 * @param string $step_unit Step unit.
	 * @return int
	 */
	private function get_time_str_length( $step_unit ) {
		if ( FrmProTimeFieldsController::STEP_UNIT_MILLISECOND === $step_unit ) {
			return 12;
		}

		if ( FrmProTimeFieldsController::STEP_UNIT_SECOND === $step_unit ) {
			return 8;
		}

		return 5;
	}

	/**
	 * Gets regex for time based on step unit.
	 *
	 * @since 6.9
	 *
	 * @param string $step_unit Step unit.
	 * @return string
	 */
	private function get_time_str_regex( $step_unit ) {
		$regex = '/^(?:2[0-3]|[01][0-9]):[0-5][0-9]';

		switch ( $step_unit ) {
			case FrmProTimeFieldsController::STEP_UNIT_SECOND:
				$regex .= ':[0-5][0-9]';
				break;

			case FrmProTimeFieldsController::STEP_UNIT_MILLISECOND:
				$regex .= ':[0-5][0-9]:\\d\\d\\d';
				break;
		}

		$regex .= '$/';

		return $regex;
	}

	public function set_value_before_save( $value ) {
		$this->default_value_to_string( $value );
		$time_format = $this->get_time_format_for_field( array(), true );
		return $this->maybe_format_time_with_ms( $value, $time_format );
	}

	protected function prepare_display_value( $value, $atts ) {
		if ( empty( $value ) ) {
			return $value;
		}

		if ( is_array( $value ) && isset( $value['H'] ) ) {
			$this->time_array_to_string( $value );
		} elseif ( ! is_array( $value ) && strpos( $value, ',' ) ) {
			$value = explode( ',', $value );
		}

		return FrmProFieldsHelper::format_values_in_array( $value, $atts['format'], array( $this, 'maybe_format_time_with_ms' ) );
	}

	/**
	 * @param array|string $value
	 * @return void
	 */
	public function time_array_to_string( &$value ) {
		if ( $this->is_time_empty( $value ) ) {
			$value = '';
		} elseif ( is_array( $value ) ) {
			$new_value = $value['H'] . ':' . $value['m'];

			if ( isset( $value['s'] ) ) {
				$new_value .= ':' . $value['s'];
			}

			if ( isset( $value['ms'] ) ) {
				$new_value .= ':' . $value['ms'];
			}

			$new_value .= ( isset( $value['A'] ) ? ' ' . $value['A'] : '' );
			$value      = $new_value;
		}
	}

	private function time_string_to_array( &$value ) {
		// H for hour, m for minute, A for am or pm, s for second, and ms for millisecond.
		$defaults = array( 'H' => '', 'm' => '', 'A' => '', 's' => '', 'ms' => '' );

		if ( is_array( $value ) ) {
			$value = wp_parse_args( $value, $defaults );
		} elseif ( is_string( $value ) && strpos( $value, ':' ) !== false ) {
			$time_array = array();

			// Get am/pm.
			$parts           = explode( ' ', $value );
			$time_array['A'] = isset( $parts[1] ) ? $parts[1] : '';

			// Get H, m, s, ms.
			$parts = explode( ':', $parts[0] );

			$time_array['H'] = $parts[0];
			if ( isset( $parts[1] ) ) {
				$time_array['m'] = $parts[1];
			}
			if ( isset( $parts[2] ) ) {
				$time_array['s'] = $parts[2];
			}
			if ( isset( $parts[3] ) ) {
				$time_array['ms'] = $parts[3];
			}

			$value = $time_array;
		} else {
			$value = $defaults;
		}
	}

	public function is_time_empty( $value ) {
		$empty_string = ! is_array( $value ) && $value == '';
		$empty_s      = isset( $value['s'] ) && '' === $value['s'];
		$empty_ms     = isset( $value['ms'] ) && '' === $value['ms'];
		$empty_array  = is_array( $value ) && ( $value['H'] == '' || $value['m'] == '' || $empty_s || $empty_ms );
		return $empty_string || $empty_array;
	}

	protected function prepare_import_value( $value, $atts ) {
		$parts = explode( ':', $value );

		switch ( count( $parts ) ) {
			case 4:
				$format = 'H:i:s:v';
				break;

			case 3:
				$format = 'H:i:s';
				break;

			default:
				$format = 'H:i';
		}

		return $this->maybe_format_time_with_ms( $value, $format );
	}

	/**
	 * Gets time format for field.
	 *
	 * @since 3.02.01
	 * @since 6.9 Added the second parameter to force using 24 hours clock.
	 *
	 * @param array|object $field          Field array or object. If this is empty, `$this->field` will be used.
	 * @param bool         $force_24_clock Force using 24-hour clock, to be used in time comparison.
	 * @return string
	 */
	public function get_time_format_for_field( $field = array(), $force_24_clock = false ) {
		if ( empty( $field ) ) {
			$field = $this->field;
		}

		$time_clock = $force_24_clock ? 24 : FrmField::get_option( $field, 'clock' );
		$step_unit  = FrmField::get_option( $field, 'step_unit' );
		return $this->get_time_format_for_setting( $time_clock, $step_unit );
	}

	/**
	 * Gets time format for using in field settings.
	 *
	 * @since 3.02.01
	 * @since 6.9     Added `$step_unit` as the second parameter, and change the name of the first parameter.
	 *
	 * @param int    $time_clock Time clock. Can be `12` or `24`.
	 * @param string $step_unit  Step unit.
	 * @return string
	 */
	public function get_time_format_for_setting( $time_clock, $step_unit = '' ) {
		if ( 12 === intval( $time_clock ) ) {
			$hour_min = 'g:i';
			$am_or_pm = ' A';
		} else {
			$hour_min = 'H:i';
			$am_or_pm = '';
		}

		if ( FrmProTimeFieldsController::STEP_UNIT_SECOND === $step_unit ) {
			$sec       = ':s';
			$milli_sec = '';
		} elseif ( FrmProTimeFieldsController::STEP_UNIT_MILLISECOND === $step_unit ) {
			$sec       = ':s';
			$milli_sec = ':v';
		} else {
			$sec       = '';
			$milli_sec = '';
		}

		return $hour_min . $sec . $milli_sec . $am_or_pm;
	}

	/**
	 * @since 4.0.04
	 */
	public function sanitize_value( &$value ) {
		FrmAppHelper::sanitize_value( 'sanitize_text_field', $value );
	}
}
