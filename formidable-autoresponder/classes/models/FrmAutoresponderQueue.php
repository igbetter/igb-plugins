<?php
/**
 * Add and remove form actions to the WP schedule.
 */
class FrmAutoresponderQueue {

	protected $action_id = 0;

	protected $entry_id = 0;

	protected $timestamp = 0;

	/**
	 * The timestamp of the current event in the loop
	 *
	 * @var string $temp_timestamp In string time format.
	 */
	private $temp_timestamp = 0;

	private $queue = array();

	private $hook = 'formidable_send_autoresponder';

	public function __construct( $args ) {
		$this->set( 'action_id', $args );
		$this->set( 'entry_id', $args );
		$this->set( 'timestamp', $args );
	}

	private function set( $name, $args ) {
		if ( isset( $args[ $name ] ) ) {
			$this->{$name} = $args[ $name ];
		}
	}

	public function schedule() {
		$args = array( intval( $this->entry_id ), intval( $this->action_id ) );
		wp_schedule_single_event( $this->timestamp, $this->hook, $args );
	}

	/**
	 * Remove scheduled events from the cron.
	 *
	 * @since 2.0
	 */
	public function unschedule() {
		$this->get_all();

		foreach ( $this->queue as $cron ) {
			$args = array( $cron['entry_id'], $cron['action_id'] );
			wp_unschedule_event( $cron['timestamp'], $this->hook, $args );
		}
	}

	/**
	 * Gets a list of whatever is in the queue
	 *
	 * @return array
	 */
	public function get_all() {
		if ( empty( $this->entry_id ) && empty( $this->action_id ) ) {
			// We need at least the entry or action id to get the queued events.
			return array();
		}

		$this->queue = array();
		$this->set_queue();
		return $this->queue;
	}

	protected function set_queue() {
		$cron = _get_cron_array();
		if ( $this->timestamp ) {
			$this->temp_timestamp = $this->timestamp;
			if ( isset( $cron[ $this->timestamp ] ) ) {
				$this->check_for_matching_event( $cron[ $this->timestamp ] );
			}
		} else {
			foreach ( $cron as $timestamp => $events ) {
				$this->temp_timestamp = $timestamp;
				$this->check_for_matching_event( $events );
			}
		}
	}

	protected function check_for_matching_event( $events ) {
		if ( isset( $events[ $this->hook ] ) ) {
			foreach ( $events[ $this->hook ] as $autoresponder ) {
				$this->prepare_cron_for_array( $autoresponder );
			}
		}
	}

	protected function prepare_cron_for_array( $autoresponder ) {
		$cron_entry_id = $autoresponder['args'][0];
		$cron_action_id = $autoresponder['args'][1];
		$matches_entry = $this->entry_id ? $this->entry_id == $cron_entry_id : true;
		$matches_action = $this->action_id ? $this->action_id == $cron_action_id : true;

		if ( $matches_entry && $matches_action ) {
			$this->queue[] = array(
				'timestamp'   => $this->temp_timestamp,
				'pretty_time' => gmdate( 'Y-m-d H:i:s', $this->temp_timestamp ),
				'entry_id'    => $cron_entry_id,
				'action_id'   => $cron_action_id,
			);
		}
	}
}
