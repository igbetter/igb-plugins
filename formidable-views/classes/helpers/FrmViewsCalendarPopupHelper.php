<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsCalendarPopupHelper {

	private $thumnbail_url = null;
	private $title         = null;
	private $description   = null;
	private $time          = null;
	private $dates         = array();

	private $entry;
	private $view;

	public function __construct( $view, $entry ) {
		if ( 1 !== (int) $view->frm_calendar_event_popup ) {
			return;
		}

		$this->view  = $view;
		$this->entry = $entry;
		$this->build_data();
	}


	/**
	 * Builds the data for the calendar popup.
	 *
	 * This method iterates through the given field IDs and retrieves the corresponding field objects.
	 * It then prepares the displayed value for each field based on its field type.
	 * The prepared values are stored in the respective properties of the class.
	 *
	 * @since 5.6
	 * @return void
	 */
	private function build_data() {

		if ( ! empty( $this->view->frm_calendar_options ) ) {
			foreach ( $this->view->frm_calendar_options as $option ) {
				$field_row = FrmField::getOne( (int) $option['value'] );
				if ( empty( $field_row ) ) {
					continue;
				}
				$field = new FrmFieldValue( $field_row, $this->entry );

				switch ( $option['name'] ) {
					case 'thumbnail':
						$field->prepare_displayed_value();
						$this->thumnbail_url = $this->build_thumbnail_url( $field->get_saved_value() );
						break;

					default:
						$field->prepare_displayed_value();
						$this->{$option['name']} = wp_strip_all_tags( $field->get_displayed_value() );
						break;
				}
			}
		}

		if ( $this->view->frm_date_field_id ) {
			if ( ! (int) $this->view->frm_date_field_id ) {
				$this->dates[] = $this->entry->{ $this->view->frm_date_field_id };
			} else {
				$field_row = FrmField::getOne( (int) $this->view->frm_date_field_id );
				$this->prepare_dates( $field_row );
			}
		}

		if ( $this->view->frm_edate_field_id ) {
			if ( ! (int) $this->view->frm_edate_field_id ) {
				$this->dates[] = $this->entry->{ $this->view->frm_edate_field_id };
			} else {
				$field_row = FrmField::getOne( (int) $this->view->frm_edate_field_id );
				$this->prepare_dates( $field_row );

			}
		}
	}

	/**
	 * Prepares dates array.
	 *
	 * @param array $field_row The field row DB data.
	 * @return void
	 */
	private function prepare_dates( $field_row ) {
		if ( empty( $field_row ) ) {
			return;
		}
		$field = new FrmFieldValue( $field_row, $this->entry );
		$field->prepare_displayed_value();
		$this->dates[] = wp_strip_all_tags( $field->get_displayed_value() );
	}

	/**
	 * Builds the thumbnail URL for the given attachment ID.
	 *
	 * @since 5.6
	 * @param int $attachment_id The ID of the attachment.
	 * @return string|null The URL of the thumbnail image, or null if not found.
	 */
	private function build_thumbnail_url( $attachment_id ) {
		$url = wp_get_attachment_image_src( $attachment_id, 'medium' );
		if ( $url && isset( $url[0] ) ) {
			return $url[0];
		}
		return null;
	}

	/**
	 * Returns the HTML representation of the dates.
	 *
	 * @since 5.6
	 * @return string The HTML representation of the dates.
	 */
	private function get_dates_string() {
		if ( empty( $this->dates ) ) {
			return '';
		}

		$html  = $this->dates[0];
		$html .= ! empty( $this->dates[1] ) ? ' - ' . $this->dates[1] : '';
		return $html;
	}

	/**
	 * Retrieves the attribute data for the calendar popup.
	 *
	 * This method returns a string containing the attribute data for the calendar popup.
	 * The attribute data includes the title, description, thumbnail image URL, dates, and times.
	 *
	 * @since 5.6
	 * @param bool $is_multid_day_event Whether the event is a multi-day event.
	 *
	 * @return string The attribute data for the calendar popup.
	 */
	public function get_attr_data( $is_multid_day_event ) {

		if ( ! $this->view || 1 !== (int) $this->view->frm_calendar_event_popup ) {
			return '';
		}

		$attr = '';
		if ( $this->title ) {
			$attr .= ' data-calpopup-title="' . esc_attr( $this->title ) . '"';
		};
		if ( $this->description ) {
			$attr .= ' data-calpopup-description="' . esc_attr( $this->description ) . '"';
		};
		if ( $this->thumnbail_url ) {
			$attr .= ' data-calpopup-image="' . esc_url( $this->thumnbail_url ) . '"';
		};
		if ( ! empty( $this->dates ) ) {
			$attr .= ' data-calpopup-date="' . esc_attr( $this->get_dates_string() ) . '"';
		}

		if ( ! $is_multid_day_event && ! empty( $this->time ) ) {
			$attr .= ' data-calpopup-time="' . esc_attr( $this->time ) . '"';
		};

		return $attr;
	}
}
