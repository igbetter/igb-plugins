<?php
/**
 * Handle step of options in time dropdowns
 *
 * @package FormidablePro
 * @since 6.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProTimeStep {

	/**
	 * Step hour.
	 *
	 * @var float
	 */
	private $h;

	/**
	 * Step minute.
	 *
	 * @var float
	 */
	private $m;

	/**
	 * Step second.
	 *
	 * @var float
	 */
	private $s;

	/**
	 * Step millisecond.
	 *
	 * @var float
	 */
	private $ms;

	/**
	 * Step value in millisecond.
	 *
	 * @var int
	 */
	private $step_in_ms;

	/**
	 * Constructor.
	 *
	 * @param int $step_in_ms Step value in millisecond.
	 */
	public function __construct( $step_in_ms ) {
		$this->step_in_ms = intval( $step_in_ms );

		$this->h  = floor( $this->step_in_ms / 3600000 );
		$this->m  = floor( ( $this->step_in_ms % 3600000 ) / 60000 );
		$this->s  = floor( ( $this->step_in_ms % 60000 ) / 1000 );
		$this->ms = $this->step_in_ms % 1000;
	}

	/**
	 * Checks if step is full hour.
	 *
	 * @return bool
	 */
	public function is_full_hour() {
		return ! $this->m && ! $this->s && ! $this->ms;
	}


	/**
	 * Checks if step is full minute.
	 *
	 * @return bool
	 */
	public function is_full_minute() {
		return ! $this->s && ! $this->ms;
	}

	/**
	 * Checks if step is full second.
	 *
	 * @return bool
	 */
	public function is_full_second() {
		return ! $this->ms;
	}

	/**
	 * Gets step of hour options.
	 *
	 * @return int
	 */
	public function get_hour_step() {
		if ( $this->is_full_hour() ) {
			// If step is 2 hour, the step of hour options will be 2.
			return $this->h;
		}
		return 1;
	}

	/**
	 * Gets value to divide to find the step.
	 *
	 * @param int $value The value.
	 * @param int $max   Maximum value (1000 for millisecond, 60 for second).
	 * @return int Return the value to divide, or `0` if don't need to divide to find the step.
	 */
	private function get_value_to_divide( $value, $max ) {
		if ( $value === $max ) {
			return 0;
		}

		if ( $value > $max ) {
			return $value % $max;
		}

		if ( $value > $max / 2 ) {
			return $max - $value;
		}

		return $value;
	}

	/**
	 * Gets step of minute options.
	 *
	 * @return int
	 */
	public function get_minute_step() {
		if ( $this->is_full_hour() ) {
			return 0; // Just show one minute option is the start minute.
		}

		if ( ! $this->is_full_minute() ) {
			return 1; // It's hard to get possible minutes in this case, so we will show all minutes.
		}

		$value_to_divide = $this->get_value_to_divide( $this->m, 60 );
		if ( ! $value_to_divide ) {
			return 0;
		}

		$remainder = 60 % $value_to_divide;
		if ( ! $remainder ) { // 1, 2, 3, 12, 15,...
			return $value_to_divide;
		}

		return 1;
	}

	/**
	 * Gets step for second options.
	 *
	 * @return int
	 */
	public function get_second_step() {
		if ( $this->is_full_minute() ) {
			return 0;
		}

		if ( ! $this->is_full_second() ) {
			return 1;
		}

		$value_to_divide = $this->get_value_to_divide( $this->s, 60 );
		$remainder       = 60 % $value_to_divide;
		if ( ! $remainder ) {
			return $value_to_divide;
		}

		return 1;
	}

	/**
	 * Gets step for millisecond options.
	 *
	 * @return int
	 */
	public function get_millisecond_step() {
		if ( $this->is_full_second() ) {
			return 0;
		}

		$value_to_divide = $this->get_value_to_divide( $this->ms, 1000 );
		$remainder       = 1000 % $value_to_divide;
		if ( ! $remainder ) {
			return $value_to_divide;
		}

		return 1;
	}
}
