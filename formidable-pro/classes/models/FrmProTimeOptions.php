<?php
/**
 * Handle time options
 *
 * @package FormidablePro
 * @since 6.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProTimeOptions {

	const HOUR_IN_MS = 3600000;

	const MINUTE_IN_MS = 60000;

	const SECOND_IN_MS = 1000;

	/**
	 * Time step object.
	 *
	 * @var FrmProTimeStep
	 */
	private $step;

	/**
	 * Start hour.
	 *
	 * @var int
	 */
	private $h_start;

	/**
	 * Start minute.
	 *
	 * @var int
	 */
	private $m_start;

	/**
	 * Start second.
	 *
	 * @var int
	 */
	private $s_start;

	/**
	 * Start millisecond.
	 *
	 * @var int|null
	 */
	private $ms_start;

	/**
	 * End hour.
	 *
	 * @var int
	 */
	private $h_end;

	/**
	 * End minute.
	 *
	 * @var int
	 */
	private $m_end;

	/**
	 * End second.
	 *
	 * @var int
	 */
	private $s_end;

	/**
	 * End millisecond.
	 *
	 * @var int
	 */
	private $ms_end;

	/**
	 * Start time in millisecond.
	 *
	 * @var int
	 */
	private $start_in_ms;

	/**
	 * End time in millisecond.
	 *
	 * @var int
	 */
	private $end_in_ms;

	/**
	 * Step unit.
	 *
	 * @var string
	 */
	private $step_unit;

	/**
	 * Should prepend zero to hour value?
	 *
	 * @var bool
	 */
	private $zeroise_hour = false;

	/**
	 * Clock base.
	 *
	 * @var int
	 */
	private $clock_base = 12;

	/**
	 * Constructor.
	 *
	 * @param array $values Processed time field values.
	 */
	public function __construct( $values ) {
		$this->h_start = $values['start_time'][0];
		$this->m_start = $values['start_time'][1];
		$this->s_start = $values['start_time'][2];

		if ( isset( $values['start_time'][3] ) ) {
			$this->ms_start = $values['start_time'][3];
		}

		$this->h_end = $values['end_time'][0];
		$this->m_end = $values['end_time'][1];
		$this->s_end = $values['end_time'][2];

		if ( isset( $values['end_time'][3] ) ) {
			$this->ms_end = $values['end_time'][3];
		}

		$this->start_in_ms = $this->get_start_time_in_ms();
		$this->end_in_ms   = $this->get_end_time_in_ms();

		$this->step_unit = FrmProTimeFieldsController::get_step_unit( $values );

		// Build step object.
		if ( FrmProTimeFieldsController::STEP_UNIT_MILLISECOND === $this->step_unit ) {
			$step_in_ms = $values['step'];
		} elseif ( FrmProTimeFieldsController::STEP_UNIT_SECOND === $this->step_unit ) {
			$step_in_ms = $values['step'] * self::SECOND_IN_MS;
		} else {
			$step_in_ms = $values['step'] * self::MINUTE_IN_MS;
		}
		$this->step = new FrmProTimeStep( $step_in_ms );

		// Clock base.
		$this->clock_base = intval( $values['clock'] );

		if ( 24 === $this->clock_base ) {
			// Prepend `0` to hour options if showing 24 hours clock.
			$this->zeroise_hour = true;
		}
	}

	/**
	 * Gets start time in millisecond.
	 *
	 * @return int
	 */
	private function get_start_time_in_ms() {
		return $this->ms_start + self::SECOND_IN_MS * $this->s_start + self::MINUTE_IN_MS * $this->m_start + self::HOUR_IN_MS * $this->h_start;
	}

	/**
	 * Gets end time in millisecond.
	 *
	 * @return int
	 */
	private function get_end_time_in_ms() {
		return $this->ms_end + self::SECOND_IN_MS * $this->s_end + self::MINUTE_IN_MS * $this->m_end + self::HOUR_IN_MS * $this->h_end;
	}

	/**
	 * Checks if the time between start and end time is less than one second.
	 *
	 * @return bool
	 */
	private function range_less_than_one_second() {
		return $this->end_in_ms - $this->start_in_ms < self::SECOND_IN_MS;
	}

	/**
	 * Checks if the time between start and end time is less than one minute.
	 *
	 * @return bool
	 */
	private function range_less_than_one_minute() {
		return $this->end_in_ms - $this->start_in_ms < self::MINUTE_IN_MS;
	}

	/**
	 * Checks if the time between start and end time is less than one hour.
	 *
	 * @return bool
	 */
	private function range_less_than_one_hour() {
		return $this->end_in_ms - $this->start_in_ms < self::HOUR_IN_MS;
	}

	/**
	 * Gets hour options.
	 *
	 * @return array
	 */
	public function get_hour_options() {
		$options = $this->range_options(
			$this->h_start,
			$this->h_end,
			$this->step->get_hour_step(),
			$this->zeroise_hour ? 2 : 1, // Do not prepend `0` to the hour value if using 12 hours clock.
			$this->clock_base
		);

		// Change 0 to 12 in 12-hour clock.
		if ( $options && 12 === $this->clock_base && $options[0] < 1 ) {
			$options[0] = 12;
		}

		return $options;
	}

	/**
	 * Gets minute options.
	 *
	 * @return array
	 */
	public function get_minute_options() {
		if ( $this->range_less_than_one_minute() ) {
			// Example: time range is 00:03:00:000 - 00:03:59:000, minute can be 03 only.
			return array( zeroise( $this->m_start, 2 ) );
		}

		$step = $this->step->get_minute_step();
		if ( ! $step ) {
			// Example: step is 1 hour, so minute won't change.
			return array( zeroise( $this->m_start, 2 ) );
		}

		if ( ! $this->range_less_than_one_hour() ) {
			// Minutes will repeat, show all.
			$start_option = $this->m_start % $step;
			return $this->range_options( $start_option, $start_option + 59, $step );
		}

		if ( $this->m_start < $this->m_end ) {
			// Example: 00:10 - 00:40, show possible values between 10 and 40.
			return $this->range_options( $this->m_start, $this->m_end, $step );
		}

		// Example: 00:40 - 01:10, show possible values that not between 10 and 40.
		return $this->range_options( $this->m_start, $this->m_end + 60, $step );
	}

	/**
	 * Gets second options.
	 *
	 * @return array
	 */
	public function get_second_options() {
		if ( $this->range_less_than_one_second() ) {
			// Example: time range is 00:03:02:000 - 00:03:02:999, second can be 02 only.
			return array( zeroise( $this->s_start, 2 ) );
		}

		$step = $this->step->get_second_step();
		if ( ! $step ) {
			return array( zeroise( $this->m_start, 2 ) );
		}

		if ( ! $this->range_less_than_one_minute() ) {
			// Seconds will repeat, show all.
			$start_option = $this->s_start % $step;
			return $this->range_options( $start_option, $start_option + 59, $step );
		}

		if ( $this->s_start < $this->s_end ) {
			// Example: 00:10 - 00:40, show possible values between 10 and 40.
			return $this->range_options( $this->s_start, $this->s_end, $step );
		}

		// Example: 00:40 - 01:10, show possible values that not between 10 and 40.
		return $this->range_options( $this->s_start, $this->s_end + 60, $step );
	}

	/**
	 * Gets millisecond options.
	 *
	 * @return array
	 */
	public function get_millisecond_options() {
		$step = $this->step->get_millisecond_step();
		if ( ! $step ) {
			return array( zeroise( $this->ms_start, 3 ) );
		}

		if ( ! $this->range_less_than_one_second() ) {
			// Milliseconds will repeat, show all.
			$start_option = $this->ms_start % $step;
			return $this->range_options( $start_option, $start_option + 1000, $step, 3, 1000 );
		}

		if ( $this->ms_start < $this->ms_end ) {
			// Example: 00:100 - 00:400, show possible values between 100 and 400.
			return $this->range_options( $this->ms_start, $this->ms_end, $step, 3, 1000 );
		}

		// Example: 00:400 - 01:100, show possible values that not between 100 and 400.
		return $this->range_options( $this->ms_start, $this->ms_end + 1000, $step, 3, 1000 );
	}

	/**
	 * Gets options from given range.
	 *
	 * @param int $start Start value.
	 * @param int $end   End value.
	 * @param int $step  Step value. Default is `1`.
	 * @param int $num_length Number of character in the option. Example, `num_length` is `3`, option will be `001`.
	 * @param int $max_value  Max value of option. If option is greater or equal to this value, it will subtract this value.
	 * @return array
	 */
	private function range_options( $start, $end, $step = 1, $num_length = 2, $max_value = 60 ) {
		$options = array();
		for ( $i = intval( $start ); $i <= intval( $end ); $i += $step ) {
			$options[] = zeroise( $i >= $max_value ? $i - $max_value : $i, $num_length );
		}

		$options = array_unique( $options );
		sort( $options );
		return $options;
	}
}
