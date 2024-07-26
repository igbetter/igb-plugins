<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsCalendarHeaderHelper {

	/**
	 * The year property of the FrmViewsCalendarHeaderHelper class.
	 *
	 * @var int $year The year value.
	 */
	private $year;

	/**
	 * The month property.
	 *
	 * @var int
	 */
	private $month;

	/**
	 * The array of month names used in the FrmViewsCalendarHeaderHelper class.
	 *
	 * @var array
	 */
	private $month_names;

	/**
	 * Represents the previous month in the calendar header.
	 *
	 * @var int $prev_month The previous month.
	 */
	private $prev_month;

	/**
	 * The previous year value.
	 *
	 * @var int $prev_year The previous year value.
	 */
	private $prev_year;

	/**
	 * Represents the next month in the calendar header.
	 *
	 * @var int $next_month The next month value.
	 */
	private $next_month;

	/**
	 * The next year value.
	 *
	 * @var int
	 */
	private $next_year;

	/**
	 * The start year for the calendar header.
	 *
	 * @var int
	 */
	private $start_year;

	/**
	 * The end year for the calendar header.
	 *
	 * @var int $end_year
	 */
	private $end_year;

	/**
	 * The view property.
	 *
	 * @var object $view The view object.
	 */
	private $view;

	/**
	 * The $atts property holds the block attributes.
	 *
	 * @var array
	 */
	private $atts;

	public function __construct( $view, $atts, $args ) {

		list(
			$this->year,
			$this->month,
			$this->month_names,
			$this->prev_month,
			$this->prev_year,
			$this->next_month,
			$this->next_year,
			$this->start_year,
			$this->end_year,
		) = $args;

		$this->view = $view;
		$this->atts = $atts;
	}

	/**
	 * Returns the HTML representation of the calendar header.
	 *
	 * @return string The HTML representation of the calendar header.
	 */
	public function get_html() {
		$wrapper_classname = FrmViewsCalendarHelper::gutenberg_wrapper_classname( $this->view->ID, $this->atts );
		if ( empty( $this->view->frm_calendar_event_popup ) ) {
			$wrapper_classname .= ' frm-no-popup';
		}
		return '<div class="frmcal ' . esc_attr( $wrapper_classname ) . '" id="frmcal-' . esc_attr( $this->view->ID ) . '" data-gutenberg-classname="' . esc_attr( FrmViewsCalendarHelper::gutenberg_wrapper_classname( $this->view->ID ) ) . '">' . $this->content();
	}

	/**
	 * Returns the content of the calendar header.
	 *
	 * This method generates the HTML content for the calendar header, which includes navigation buttons, heading, and filters.
	 *
	 * @return string The HTML content of the calendar header.
	 */
	private function content() {
		return '<div class="frmcal-header">' . $this->navs() . $this->heading() . $this->filters() . '</div>';
	}

	/**
	 * Generates a navigation link for the calendar header.
	 *
	 * @param string $type The type of navigation link ('prev' or 'next').
	 * @return string The generated navigation link HTML.
	 */
	private function nav( $type ) {
		$url = add_query_arg(
			array(
				'frmcal-month' => 'prev' === $type ? $this->prev_month : $this->next_month,
				'frmcal-year'  => 'prev' === $type ? $this->prev_year : $this->next_year,
			)
		);

		$url .= '#frmcal-' . esc_attr( $this->view->ID );
		$img = '<img src="' . esc_url( FrmViewsAppHelper::plugin_url() . '/images/arrow-right.svg' ) . '" />';

		return '<a href="' . esc_url( $url ) . '" class="frmcal-' . esc_attr( $type ) . '" title="' . esc_attr( $this->month_names[ $this->next_month ] ) . '">' . $img . '</a>';
	}

	/**
	 * Generates the navigation buttons for the calendar header.
	 *
	 * @return string The HTML markup for the navigation buttons.
	 */
	private function navs() {
		return '<div class="frmcal-navs">' . $this->nav( 'prev' ) . '' . $this->nav( 'next' ) . '</div>';
	}

	/**
	 * Returns the HTML markup for the calendar heading.
	 *
	 * @return string The HTML markup for the calendar heading.
	 */
	private function heading() {
		return '<div class="frmcal-title frmcal-hide-on-mobile">'
					. '<b class="frmcal-month">' . esc_html( $this->month_names[ $this->month ] ) . '</b>&nbsp;'
					. '<span class="frmcal-year">' . esc_html( $this->year ) . '</span>'
					. $this->calendar_button_go_current_month( $this->month, $this->year )
				. '</div>';
	}

	/**
	 * Generates a calendar button to go to the current month and year.
	 *
	 * @param int $active_month The active month.
	 * @param int $active_year The active year.
	 *
	 * @return string The HTML markup for the calendar button, or null if the active month and year are already the current month and year.
	 */
	private function calendar_button_go_current_month( $active_month, $active_year ) {
		$current_month = date_i18n( 'm' );
		$current_year  = date_i18n( 'Y' );

		if ( $active_month == $current_month && $active_year == $current_year ) {
			return '';
		}

		return '<a class="frmcal-today-button" href="' . esc_url(
			add_query_arg(
				array(
					'frmcal-month' => $current_month,
					'frmcal-year'  => $current_year,
				)
			)
		) . '" class="frm-views-calendar-go-current-month">' . esc_html__( 'Today', 'formidable-views' ) . '</a>';
	}

	/**
	 * Generates a dropdown select element for filtering the month.
	 *
	 * @return string The HTML markup for the dropdown select element.
	 */
	private function filter_month() {
		$html = '<select class="frmcal-dropdown" onchange="window.location=\'' . esc_url( remove_query_arg( 'frmcal-month', add_query_arg( array( 'frmcal-year' => $this->year ) ) ) ) . '&amp;frmcal-month=\'+this.value+\'#frmcal' . esc_attr( $this->view->ID ) . '\'">';
		$options = array_map(
			function ( $key, $month_name ) {
				return '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $this->month, false ) . '>' . esc_html( $month_name ) . '</option>';
			},
			array_keys( $this->month_names ),
			$this->month_names
		);
		$html .= join( '', $options ) . '</select>';

		return $html;
	}

	/**
	 * Generates a dropdown select element for filtering the year.
	 *
	 * @return string The HTML markup for the dropdown select element.
	 */
	private function filter_year() {
		$html = '<select class="frmcal-dropdown" onchange="window.location=\'' . esc_url( remove_query_arg( 'frmcal-year', add_query_arg( array( 'frmcal-month' => $this->month ) ) ) ) . '&amp;frmcal-year=\'+this.value+\'#frmcal-' . esc_attr( $this->view->ID ) . '\'">';
		for ( $i = $this->start_year; $i <= $this->end_year; $i++ ) {
			$html .= '<option value="' . esc_attr( $i ) . '" ' . selected( $i, $this->year, false ) . '>' . esc_html( $i ) . '</option>';
		}
		return $html . '</select>';
	}

	private function filters() {
		return '<div class="frmcal-filters">' . $this->filter_month() . $this->filter_year() . '</div>';
	}
}
