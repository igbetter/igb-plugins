<?php

class FrmChatHtmlHelper {

	/**
	 * @var bool
	 */
	private $is_mobile;

	/**
	 * @var int $index
	 */
	private $index;

	/**
	 * @var stdClass $form
	 */
	private $form;

	/**
	 * @var int $row_size
	 */
	private $row_size;

	/**
	 * @var FrmChatHtmlHelper|null $section_helper
	 */
	private $section_helper;

	/**
	 * @var bool $in_first_section
	 */
	private $in_first_section;

	/**
	 * @var int $section_id
	 */
	private $section_id;

	/**
	 * @var string $section_key
	 */
	private $section_key;

	/**
	 * @var array $checked_fields tracked to avoid incrementing index for the same field twice. The before_replace_shortcodes filter may be called multiple times.
	 */
	private $checked_fields;

	/**
	 * @var string $section_type either 'section', 'repeater', or 'form'.
	 */
	private $section_type;

	/**
	 * @var string $title
	 */
	private $title;

	/**
	 * @var string $description
	 */
	private $description;

	/**
	 * @var array|null $visible_embedded_form_ids
	 */
	private static $visible_embedded_form_ids;

	/**
	 * @var array<stdClass>|null $fields. All fields in form. Only gets set after calling count_questions_in_form.
	 */
	private $fields;

	/**
	 * @var bool|null $is_multipage true if the form has page breaks. Only gets set after calling count_questions_in_form.
	 */
	private $is_multipage;

	/**
	 * @var array Track if a likert check exists or not, to avoid multiple database calls for a target likert ID.
	 */
	private $likert_field_checks;

	/**
	 * @param stdClass $form
	 * @param int      $section_id
	 * @param string   $section_key
	 * @param string   $section_type
	 * @param bool     $in_first_section
	 */
	public function __construct( $form, $section_id = 0, $section_key = '', $section_type = 'section', $in_first_section = false ) {
		$this->is_mobile           = wp_is_mobile();
		$this->index               = 0;
		$this->form                = $form;
		$this->row_size            = 0;
		$this->section_id          = $section_id;
		$this->section_key         = $section_key;
		$this->checked_fields      = array();
		$this->section_type        = $section_type;
		$this->in_first_section    = $in_first_section;
		$this->title               = '';
		$this->description         = '';
		$this->likert_field_checks = array();
	}

	/**
	 * @param array $args with keys 'html', 'field', 'errors', 'form'.
	 * @return string
	 */
	public function maybe_hide_field( $args ) {
		$field = $args['field'];
		$type  = $field['type'];

		if ( $this->field_is_likert_child( $field ) ) {
			return $args['html'];
		}

		if ( ! empty( $this->section_helper ) ) {
			$section_key = $this->section_helper->get_section_key();
			if ( array_key_exists( $section_key, $field ) && $this->section_helper->matches_section( $field[ $section_key ] ) ) {
				if ( 'section' === $this->section_type ) {
					$args['html'] = $this->add_class_to_html( $args['html'], 'frm_inactive_chat_field' );
					return $args['html'];
				}
				return $this->section_helper->maybe_hide_field( $args );
			}
			unset( $this->section_helper );
		}

		$field_id  = $field['id'];
		$field_key = $field['field_key'];

		if ( $this->field_is_hidden_by_shortcode( $field_id, $field_key ) ) {
			$this->checked_fields[ $field_id ] = false;
			return $args['html'];
		}

		if ( 'form' === $type && ! empty( $field['form_select'] ) ) {
			if ( ! isset( self::$visible_embedded_form_ids ) ) {
				self::$visible_embedded_form_ids = array();
			}
			self::$visible_embedded_form_ids[] = (int) $field['form_select'];
		}

		$checked_field      = $this->checked_field( $field );
		$size_of_this_field = 0;

		if ( $checked_field ) {
			$is_visible = $this->checked_fields[ $field_id ];
		} elseif ( $this->adding_new_repeater_row() ) {
			$is_visible = true;
		} else {
			$size_of_this_field = $this->get_size_of_field( $field );
			$is_visible         = ! $this->should_include_start_page() && $this->is_first() && ( ! $this->section_id || ( $this->in_first_section && 'section' !== $this->section_type ) );
			if ( $this->row_size && $this->should_advance_index( $field, $size_of_this_field ) ) {
				$this->row_size = 0;
				++$this->index;
				$is_visible = false;
			}
		}

		$class_to_add    = $is_visible ? 'frm_active_chat_field' : 'frm_inactive_chat_field';
		$is_section_type = in_array( $type, array( 'divider', 'form' ), true );

		if ( $is_section_type ) {
			$class_to_add .= ' frm_chat_section';
		}
		if ( $this->is_autoadvance_field( $field ) ) {
			$class_to_add .= ' frm_autoadvance';
		}
		if ( $this->field_type_requires_tabindex_attr( $field ) ) {
			$args['html'] = preg_replace( '/class="/', 'tabindex="0" class="', $args['html'], 1 );
		}
		$args['html'] = $this->add_class_to_html( $args['html'], $class_to_add );

		if ( 'divider' === $type && empty( $field['repeat'] ) ) {
			// Move the content after the section instead of inside of it and add a section page before the section fields.
			$args['html']  = str_replace( '[collapse_this]', '', $args['html'] );
			$args['html'] .= '[collapse_this]';

			// Replace section description div with a field label.
			$args['html'] = preg_replace(
				'/<div(.*?)class="(.*?)frm_description(.*?)">(.*?)<\/div>/',
				'<label class="frm_primary_label">$4</label>',
				$args['html'],
				1
			);
		}

		if ( $checked_field ) {
			return $args['html'];
		}

		// only increment if this is the last field in a row.
		$should_increment = 12 === $size_of_this_field;

		if ( $is_section_type ) {
			$section_type = $type;
			if ( 'divider' === $section_type ) {
				$section_type = ! empty( $field['repeat'] ) ? 'repeater' : 'section';
			}
			$this->section_helper = new self( $this->form, $field_id, $field_key, $section_type, $this->is_first() );
		}

		if ( $should_increment ) {
			$this->row_size = 0;
			++$this->index;
		} else {
			$this->row_size += $size_of_this_field;
		}

		$this->checked_fields[ $field_id ] = $is_visible;

		return $args['html'];
	}

	/**
	 * Check that a field has a likert_id option and that the likert field is actually a likert field.
	 *
	 * @since 1.1.1
	 *
	 * @param array $field_options
	 * @return bool
	 */
	private function field_is_likert_child( $field_options ) {
		if ( empty( $field_options['likert_id'] ) ) {
			return false;
		}
		return $this->likert_field_exists( $field_options['likert_id'] );
	}

	/**
	 * Check if a likert field exists.
	 * Some fields will have a likert_id set to a field that does not exist.
	 *
	 * @since 1.1.1
	 *
	 * @param string|int $likert_id
	 * @return bool
	 */
	private function likert_field_exists( $likert_id ) {
		if ( ! array_key_exists( $likert_id, $this->likert_field_checks ) ) {
			$this->likert_field_checks[ $likert_id ] = 'likert' === FrmField::get_type( $likert_id );
		}
		return $this->likert_field_checks[ $likert_id ];
	}

	/**
	 * @return bool
	 */
	public function is_first() {
		return 0 === $this->index;
	}

	/**
	 * @param int|string $field_id
	 * @param string     $field_key
	 * @return bool
	 */
	private function field_is_hidden_by_shortcode( $field_id, $field_key ) {
		if ( class_exists( 'FrmProGlobalVarsHelper' ) ) {
			$field = array(
				'id'        => $field_id,
				'field_key' => $field_key,
			);
			return ! FrmProGlobalVarsHelper::get_instance()->field_is_visible( $field );
		}

		global $frm_vars;
		if ( empty( $frm_vars['show_fields'] ) || ! is_array( $frm_vars['show_fields'] ) ) {
			return false;
		}
		list( $ids, $keys ) = self::pull_ids_and_keys( $frm_vars['show_fields'] );
		return ! in_array( (int) $field_id, $ids, true ) && ! in_array( $field_key, $keys, true );
	}

	/**
	 * @param array $values
	 * @return array<array>
	 */
	private static function pull_ids_and_keys( $values ) {
		$ids  = array();
		$keys = array();
		foreach ( $values as $field_id_or_key ) {
			if ( is_numeric( $field_id_or_key ) ) {
				$ids[] = (int) $field_id_or_key;
			} else {
				$keys[] = $field_id_or_key;
			}
		}
		return array( $ids, $keys );
	}

	/**
	 * @param int $section_id
	 * @return bool
	 */
	public function matches_section( $section_id ) {
		return $this->section_id === $section_id;
	}

	/**
	 * @return string
	 */
	public function get_section_key() {
		return 'in_section';
	}

	/**
	 * @param array $field
	 * @return bool
	 */
	private function checked_field( $field ) {
		return isset( $this->checked_fields[ $field['id'] ] );
	}

	/**
	 * @param array $field
	 * @param int   $size_of_this_field
	 * @return bool
	 */
	private function should_advance_index( $field, $size_of_this_field ) {
		if ( $this->row_size && false !== strpos( ' ' . $field['classes'] . ' ', ' frm_first ' ) ) {
			return true;
		}
		return $this->row_size + $size_of_this_field > 12;
	}

	/**
	 * @param array $field
	 * @return int
	 */
	private function get_size_of_field( $field ) {
		if ( $this->field_matches_type( $field, array( 'hidden', 'form', 'quiz_score' ) ) ) {
			$size = 0;
		} elseif ( empty( $field['classes'] ) ) {
			$size = 12;
		} else {
			$classes        = explode( ' ', $field['classes'] );
			$layout_classes = array_intersect( $classes, $this->get_grid_classes() );

			if ( $layout_classes ) {
				$layout_class = reset( $layout_classes );
				$size         = $this->get_size_of_class( $layout_class );
			} else {
				$size = 12;
			}
		}
		return $this->maybe_adjust_size_for_confirmation_field( $size, $field );
	}

	/**
	 * Confirmation fields with inline positions split the available width in half. Adjust for this.
	 *
	 * @param int   $size
	 * @param array $field
	 * @return int
	 */
	private function maybe_adjust_size_for_confirmation_field( $size, $field ) {
		if ( ! empty( $field['conf_field'] ) && 'inline' === $field['conf_field'] ) {
			return (int) ceil( $size / 2 );
		}
		return $size;
	}

	/**
	 * @param string $class
	 * @return int
	 */
	private function get_size_of_class( $class ) {
		switch ( $class ) {
			case 'frm_half':
				return 6;
			case 'frm_third':
				return 4;
			case 'frm_two_thirds':
				return 8;
			case 'frm_fourth':
				return 3;
			case 'frm_three_fourths':
				return 9;
			case 'frm_sixth':
				return 2;
		}

		if ( 0 === strpos( $class, 'frm' ) ) {
			$substr = substr( $class, 3 );
			if ( is_numeric( $substr ) ) {
				return (int) $substr;
			}
		}

		// Anything missing a layout class should be a full width row.
		return 12;
	}

	/**
	 * @return array<string>
	 */
	private function get_grid_classes() {
		return array(
			'frm_full',
			'frm_half',
			'frm_third',
			'frm_two_thirds',
			'frm_fourth',
			'frm_three_fourths',
			'frm_sixth',
			'frm1',
			'frm2',
			'frm3',
			'frm4',
			'frm5',
			'frm6',
			'frm7',
			'frm8',
			'frm9',
			'frm10',
			'frm11',
			'frm12',
		);
	}

	/**
	 * @param array $field
	 * @return bool
	 */
	private function is_autoadvance_field( $field ) {
		if ( $this->field_matches_type( $field, array( 'select', 'file' ) ) ) {
			return empty( $field['multiple'] );
		}
		if ( $this->field_matches_type( $field, array( 'data', 'lookup', 'product' ) ) ) {
			return ! empty( $field['data_type'] ) && in_array( $field['data_type'], array( 'select', 'radio' ), true );
		}
		return $this->field_matches_type( $field, array( 'radio', 'toggle', 'date', 'scale', 'star', 'nps', 'ssa-appointment' ) );
	}

	/**
	 * @param array $field
	 * @param array $types
	 * @return bool
	 */
	private function field_matches_type( $field, $types ) {
		if ( empty( $field['type'] ) ) {
			return false;
		}
		return in_array( $field['type'], $types, true );
	}

	/**
	 * @param array $field
	 * @return bool
	 */
	private function field_type_requires_tabindex_attr( $field ) {
		if ( $this->field_matches_type( $field, array( 'lookup' ) ) ) {
			return ! empty( $field['data_type'] ) && 'data' === $field['data_type'];
		}
		if ( ! empty( $field['calc'] ) && ! empty( $field['is_currency'] ) ) {
			return true;
		}
		return $this->field_matches_type( $field, array( 'html', 'file', 'summary', 'total', 'signature' ) );
	}

	/**
	 * @param string $html
	 * @param string $class
	 * @return string
	 */
	private function add_class_to_html( $html, $class ) {
		return str_replace( 'class="frm_form_field ', 'class="frm_form_field ' . $class . ' ', $html );
	}

	/**
	 * @return string
	 */
	public function get_button_wrapper() {
		$class = 'frm-chat-wrapper';
		if ( ! empty( $this->form->options['submit_align'] ) && 'none' === $this->form->options['submit_align'] ) {
			$class .= ' frm-no-submit';
		}
		return '<div class="' . esc_attr( $class ) . '">';
	}

	/**
	 * @return string
	 */
	public function maybe_get_arrow_navigation() {
		if ( empty( $this->form->options['chat_include_arrows'] ) ) {
			return '';
		}
		$prev_arrow = '<a href="#" class="frm_chat_arrow frm_chat_prev_arrow" role="button" aria-label="' . esc_attr__( 'Previous', 'formidable-chat' ) . '">' . $this->get_prev_arrow_icon() . '</a>';
		$next_arrow = '<a href="#" class="frm_chat_arrow frm_chat_next_arrow frm_button_submit" role="button" aria-label="' . esc_attr__( 'Continue', 'formidable-chat' ) . '">' . $this->get_next_arrow_icon() . $this->get_next_checkmark_icon() . '</a>';
		return '<div class="frm_chat_arrows">' . $prev_arrow . $next_arrow . '</div>';
	}

	/**
	 * @return string
	 */
	private function get_prev_arrow_icon() {
		return '<svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.75 9.5H4.25" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.5 14.75L4.25 9.5L9.5 4.25" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/**
	 * @return string
	 */
	private function get_next_arrow_icon() {
		return '<svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.25 9.5H14.75" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.5 14.75L14.75 9.5L9.5 4.25" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/**
	 * @return string
	 */
	private function get_next_checkmark_icon() {
		return '<svg class="frm_next_checkmark" width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.5 4.875L7.25 13.125L3.5 9.375" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/**
	 * @return string
	 */
	public function maybe_get_continue_button() {
		$continue_text = $this->get_continue_text();
		$submit_text   = $this->get_submit_text();
		$icon          = $this->get_continue_button_icon();
		$button        = '<button class="button button-primary frm-button-primary frm_continue_chat frm_button_submit frm_hidden"><span class="frm_continue_text">' . esc_html( $continue_text ) . '</span><span class="frm_continue_final_text">' . esc_html( $submit_text ) . '</span> ' . $icon . '</button>';
		return '<div class="frm_continue_chat_wrapper frm_submit">' . $button . '</div>';
	}

	/**
	 * @return string
	 */
	private function get_continue_text() {
		return $this->get_button_text( 'chat_continue_text' );
	}

	/**
	 * @return string
	 */
	private function get_submit_text() {
		return $this->get_button_text( 'submit_value' );
	}

	/**
	 * Gets button text from form settings or the default value when nothing is set.
	 *
	 * @since 1.0.04
	 *
	 * @param string $key The form options key.
	 * @return string
	 */
	private function get_button_text( $key ) {
		if ( ! empty( $this->form->options[ $key ] ) ) {
			return $this->form->options[ $key ];
		}

		switch ( $key ) {
			case 'chat_start_button_text':
				return __( 'Start', 'formidable-chat' );
			case 'chat_continue_text':
				return __( 'Continue', 'formidable-chat' );
			case 'submit_value':
				return __( 'Submit', 'formidable-chat' );
		}

		return '';
	}

	/**
	 * @return string
	 */
	private function get_continue_button_icon() {
		if ( $this->is_mobile ) {
			// The continue button uses a "Press Enter" icon so don't include it on mobile.
			return '';
		}
		return '<svg width="16" height="11" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 11"><path fill="currentColor" d="M15.3 6c0 .7-.3 1.2-.7 1.7a2.2 2.2 0 0 1-1.7.7H7.4v2.3L.6 7.5l6.8-3.2v2.3H12c.3 0 .6-.1.8-.3.3-.2.4-.5.4-.8V.7h2V6Z"/></svg>';
	}

	/**
	 * @return string
	 */
	public function maybe_get_key_instructions() {
		if ( $this->is_mobile ) {
			// Do not render the keyboard instructions on a mobile device.
			return '';
		}

		if ( ! $this->is_initial_page_load() ) {
			// Do not render the keyboard instructions a second time.
			return '';
		}

		$instruction = sprintf(
			esc_html__( 'Use %1$s to go back', 'formidable-chat' ),
			'<span class="frm-instruction-key">' . esc_html__( 'Shift+Tab', 'formidable-chat' ) . '</span>'
		);
		return '<div class="frm-key-instructions">' . $instruction . '</div>';
	}

	/**
	 * @return string
	 */
	public function maybe_get_progress_html() {
		if ( empty( $this->form->options['chat_progress_type'] ) ) {
			return '';
		}

		switch ( $this->form->options['chat_progress_type'] ) {
			case 'bar':
				$html = $this->get_progress_bar();
				break;
			case 'text':
				$html = $this->get_progress_text();
				break;
			case 'both':
				$html = $this->get_progress_bar() . $this->get_progress_text();
				break;
			default:
				$html = '';
				break;
		}

		if ( '' !== $html ) {
			$html = '<div class="frm-chat-progress" ' . $this->get_progress_attrs() . '>' . $html . '</div>';
		}

		return $html;
	}

	/**
	 * @return string
	 */
	private function get_progress_bar() {
		return '<div class="frm-progress-bar"><div></div><div></div></div>';
	}

	/**
	 * @return string
	 */
	private function get_progress_text() {
		$progress_string = sprintf( __( '%1$s of %2$s', 'formidable-chat' ), '[current]', '[total]' );
		$progress_string = apply_filters( 'frm_chat_progress_text', $progress_string, $this->form );
		return '<div class="frm-progress-text" frm-progress-string="' . esc_attr( $progress_string ) . '"></div>';
	}

	/**
	 * Check if this is the initial load for a page by checking request data.
	 * In a form with one page, this is always true.
	 * In a form with multiple pages, this will be false even on the first page if moving back from the second page.
	 *
	 * @return bool
	 */
	private function is_initial_page_load() {
		if ( $this->adding_new_repeater_row() ) {
			return false;
		}

		$next_page  = FrmAppHelper::get_post_param( 'frm_next_page', 0, 'absint' );
		$page_order = FrmAppHelper::get_post_param( 'frm_page_order_' . $this->form->id, 0, 'absint' );
		return 0 === $next_page && 0 === $page_order;
	}

	/**
	 * @return bool
	 */
	private static function adding_new_repeater_row() {
		return wp_doing_ajax() && 'frm_add_form_row' === FrmAppHelper::get_param( 'action' );
	}

	/**
	 * @param string $html
	 * @return string
	 */
	public function maybe_inject_start_page( $html ) {
		if ( ! $this->should_include_start_page() ) {
			return $html;
		}

		$start_page         = $this->get_start_page();
		$form_id_input_html = '<input type="hidden" name="form_id" value="' . absint( $this->form->id ) . '"';

		// Inject the start page so it is before the hidden form id input. This way it's before other fields.
		return preg_replace( '/' . $form_id_input_html . '/', $start_page . $form_id_input_html, $html, 1 );
	}

	/**
	 * @return string
	 */
	private function get_progress_attrs() {
		$total  = $this->count_questions_in_form();
		$offset = $this->get_progress_offset();
		return 'frm-question-offset="' . absint( $offset ) . '" frm-question-total="' . absint( $total ) . '"';
	}

	/**
	 * @return int
	 */
	private function get_progress_offset() {
		if ( empty( $this->is_multipage ) ) {
			return 0;
		}

		$current_page = $this->get_active_page();
		if ( 1 === $current_page ) {
			return 0;
		}

		return $this->count_questions_in_form( $current_page );
	}

	/**
	 * @param int|false $stop_at_page_number
	 * @return int
	 */
	private function count_questions_in_form( $stop_at_page_number = false ) {
		if ( ! isset( $this->fields ) ) {
			$this->fields = FrmField::get_all_for_form( $this->form->id, '', 'include', 'exclude' );
			if ( is_callable( 'FrmSurveys\controllers\LikertController::change_field_order_keep_likert' ) ) {
				// change the field order of likerts so section data is available for children fields.
				$this->fields = FrmSurveys\controllers\LikertController::change_field_order_keep_likert( $this->fields );
			}
		}

		$count                               = 0;
		$current_total_row_size              = 0;
		$current_page                        = 1;
		$embedded_form_is_visible_by_form_id = array();
		$section_is_visible_by_id            = array();
		$sections_by_id                      = array();
		$values                              = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$active_page                         = $this->get_active_page();
		$page_is_hidden                      = false;
		foreach ( $this->fields as $field ) {
			if ( in_array( $field->type, array( 'end_divider', 'gateway', 'hidden', 'user_id', 'quiz_score' ), true ) ) {
				continue;
			}
			if ( 'captcha' === $field->type && $this->captchas_are_invisible() ) {
				continue;
			}
			if ( 'break' === $field->type ) {
				++$current_page;
				if ( false !== $stop_at_page_number && $current_page === $stop_at_page_number ) {
					if ( $current_total_row_size ) {
						++$count;
					}
					return $count;
				}
				$page_is_hidden = 1 !== $active_page && FrmProFieldsHelper::is_field_hidden( $field, $values );
				continue;
			}
			if ( 'form' === $field->type ) {
				$embedded_form_is_visible_by_form_id[ $field->field_options['form_select'] ] = ! $this->field_is_hidden_by_shortcode( $field->id, $field->field_key ) && ( $current_page >= $active_page || ! FrmProFieldsHelper::is_field_hidden( $field, $values ) );
				continue;
			}
			if ( 'divider' === $field->type && ! FrmField::get_option( $field, 'repeat' ) ) {
				$visibility                             = FrmField::get_option( $field, 'admin_only' );
				$section_is_visible_by_id[ $field->id ] = ( ! $visibility || FrmProFieldsHelper::user_has_permission( $visibility ) ) && ( $current_page >= $active_page || ! FrmProFieldsHelper::is_field_hidden( $field, $values ) );
				$sections_by_id[ $field->id ]           = $field;
				if ( $section_is_visible_by_id[ $field->id ] && ( empty( $field->field_options['label'] ) || ! in_array( $field->field_options['label'], array( 'none', 'hidden' ), true ) ) ) {
					++$count;
				}
				continue;
			}
			if ( $this->field_is_likert_child( $field->field_options ) ) {
				// Never count a field inside of a likert field because the whole likert counts as one question.
				continue;
			}
			if ( ! FrmProFieldsHelper::is_field_visible_to_user( $field ) ) {
				continue;
			}
			if ( $current_page < $active_page && FrmProFieldsHelper::is_field_hidden( $field, $values ) ) {
				continue;
			}
			if ( $page_is_hidden ) {
				continue;
			}
			if ( $this->has_class( $field->field_options, 'frm_hidden' ) || $this->has_class( $field->field_options, 'frm_invisible' ) ) {
				continue;
			}

			$section_id  = 0;
			$section_key = '';

			if ( ! empty( $field->field_options['in_section'] ) && ! empty( $sections_by_id[ $field->field_options['in_section'] ] ) ) {
				$section_id  = $field->field_options['in_section'];
				$section_key = $sections_by_id[ $field->field_options['in_section'] ]->field_key;
			}

			if ( $this->field_is_hidden_by_shortcode( $field->id, $field->field_key ) ) {
				continue;
			}

			unset( $section_key );

			if ( $this->field_is_likert_child( $field->field_options ) && ! empty( $sections_by_id[ $field->field_options['likert_id'] ] ) ) {
				$section_id = $field->field_options['likert_id'];
			}

			if ( $section_id ) {
				$section = $sections_by_id[ $section_id ];
				if ( isset( $section_is_visible_by_id[ $section_id ] ) && ! $section_is_visible_by_id[ $section_id ] ) {
					continue;
				}
			} elseif ( (int) $this->form->id !== (int) $field->form_id && isset( $embedded_form_is_visible_by_form_id[ $field->form_id ] ) && ! $embedded_form_is_visible_by_form_id[ $field->form_id ] ) {
				continue;
			}

			$size = $this->get_size_of_field(
				array(
					'type'    => $field->type,
					'classes' => isset( $field->field_options['classes'] ) ? $field->field_options['classes'] : '',
				)
			);
			if ( $current_total_row_size && ( $this->has_class( $field->field_options, 'frm_first' ) || $size + $current_total_row_size > 12 ) ) {
				++$count;
				$current_total_row_size = 0;
			}

			$current_total_row_size += $size;
		}
		if ( false === $stop_at_page_number ) {
			$this->is_multipage = $current_page > 1;
		}
		if ( $current_total_row_size ) {
			++$count;
		}
		return $count;
	}

	/**
	 * @param array  $field_options
	 * @param string $class
	 * @return bool
	 */
	private function has_class( $field_options, $class ) {
		if ( ! array_key_exists( 'classes', $field_options ) ) {
			return false;
		}
		return false !== strpos( ' ' . $field_options['classes'] . ' ', ' ' . $class . ' ' );
	}

	/**
	 * @return bool true if the global reCAPTCHA setting is set to invisible or v3 as it should not be counted.
	 */
	private function captchas_are_invisible() {
		$settings = FrmAppHelper::get_settings();
		return in_array( $settings->re_type, array( 'invisible', 'v3' ), true );
	}

	/**
	 * @return int
	 */
	private function get_active_page() {
		$page_num = 1;
		if ( FrmProFormsHelper::going_to_prev( $this->form->id ) ) {
			$this->prev_page_num( $page_num );
		} elseif ( FrmProFormsHelper::going_to_next( $this->form->id ) ) {
			$this->next_page_num( $page_num );
		}
		return $page_num;
	}

	/**
	 * @param int $page_num
	 * @return void
	 */
	private function prev_page_num( &$page_num ) {
		$next_page = FrmAppHelper::get_post_param( 'frm_next_page', 0, 'absint' );
		if ( ! $next_page ) {
			return;
		}

		$page_breaks = FrmField::get_all_types_in_form( $this->form->id, 'break' );
		$page_num    = count( $page_breaks );
		$page_breaks = array_reverse( $page_breaks );
		foreach ( $page_breaks as $index => $page_break ) {
			if ( $page_break->field_order <= $next_page ) {
				$values = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				while ( array_key_exists( $index + 1, $page_breaks ) && FrmProFieldsHelper::is_field_hidden( $page_breaks[ $index + 1 ], $values ) ) {
					$page_num--;
					$index++;
				}
				break;
			}
			$page_num--;
		}
	}

	/**
	 * @param int $page_num
	 * @return void
	 */
	private function next_page_num( &$page_num ) {
		$next_page = FrmAppHelper::get_post_param( 'frm_page_order_' . $this->form->id, 0, 'absint' );
		if ( ! $next_page ) {
			return;
		}

		$page_breaks = FrmField::get_all_types_in_form( $this->form->id, 'break' );
		foreach ( $page_breaks as $page_break ) {
			$page_num++;
			if ( $page_break->field_order >= $next_page ) {
				break;
			}
		}
	}

	/**
	 * @return string
	 */
	private function get_start_page() {
		$start_page_content = '';

		if ( '' !== $this->title && $this->check_form_state_for_visiblity( 'title' ) ) {
			$start_page_content .= str_replace( '[form_name]', $this->form->name, $this->title );
		}

		if ( '' !== $this->description && $this->check_form_state_for_visiblity( 'description' ) ) {
			$start_page_content .= str_replace( '[form_description]', $this->form->description, $this->description );
		}

		$start_page_content .= '<div class="frm_submit frm_chat_start_wrapper">' . $this->get_start_button() . $this->get_press_enter_instruction() . '</div>';
		$start_page_content  = apply_filters( 'frm_chat_start_page_content', $start_page_content, array( 'form' => $this->form ) );

		return '<div class="frm_active_chat_field frm_chat_start_page" style="--title-margin-bottom: 25px;" tabindex="0">' . $start_page_content . '</div>';
	}

	/**
	 * @since 1.0.01
	 *
	 * @return bool
	 */
	private function should_include_start_page() {
		if ( ! $this->is_initial_page_load() ) {
			return false;
		}

		if ( isset( $this->form->options['chat_show_start_page'] ) ) {
			return (bool) $this->form->options['chat_show_start_page'];
		}

		// If no setting is found for chat_show_start_page, show the start page based on visibiltiy of title or description.
		if ( '' === $this->title && '' === $this->description ) {
			return false;
		}
		if ( ! $this->check_form_state_for_visiblity( 'title' ) && ! $this->check_form_state_for_visiblity( 'description' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @since 1.0.01
	 *
	 * @param string $type 'title' or 'description'.
	 * @return bool
	 */
	private function check_form_state_for_visiblity( $type ) {
		if ( ! class_exists( 'FrmProFormState' ) || ! in_array( $type, array( 'title', 'description' ), true ) ) {
			return false;
		}
		return (bool) FrmProFormState::get_from_request( $type, false );
	}

	/**
	 * @return string
	 */
	private function get_start_button_text() {
		return $this->get_button_text( 'chat_start_button_text' );
	}

	/**
	 * @return string
	 */
	private function get_start_button() {
		return '<button class="button button-primary frm-button-primary frm_chat_start">' . esc_html( $this->get_start_button_text() ) . '</button>';
	}

	/**
	 * @return string
	 */
	private function get_press_enter_instruction() {
		if ( $this->is_mobile ) {
			return '';
		}
		return '<span class="frm_press_enter">' . esc_html__( 'press', 'formidable-chat' ) . ' <strong>' . esc_html__( 'Enter', 'formidable-chat' ) . '</strong> ' . $this->get_continue_button_icon() . '</span>';
	}

	/**
	 * @return void
	 */
	public function extract_title_and_description() {
		$html = $this->form->options['before_html'];

		$title = $this->substring_simple_shortcode( $html, 'if form_name' );
		if ( false !== $title ) {
			$html        = str_replace( '[if form_name]' . $title . '[/if form_name]', '', $html );
			$this->title = $title;
		}

		$description = $this->substring_simple_shortcode( $html, 'if form_description' );
		if ( false !== $description ) {
			$html              = str_replace( '[if form_description]' . $description . '[/if form_description]', '', $html );
			$this->description = $description;
		}

		$this->form->options['before_html'] = $html;
	}

	/**
	 * @param string $html
	 * @param string $shortcode
	 * @return string|false
	 */
	private function substring_simple_shortcode( $html, $shortcode ) {
		$start_shortcode = '[' . $shortcode . ']';

		$start = strpos( $html, $start_shortcode );
		if ( false === $start ) {
			return false;
		}

		$end_shortcode = '[/' . $shortcode . ']';
		$end           = strpos( $html, $end_shortcode, $start );
		if ( false === $end ) {
			return false;
		}

		$start += strlen( $start_shortcode );
		return substr( $html, $start, $end - $start );
	}
}
