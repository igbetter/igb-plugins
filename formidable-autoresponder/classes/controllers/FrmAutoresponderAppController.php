<?php

/**
 * FrmAutoresponderAppController
 *
 * This class is the main controller for the Formidable Autoresponder
 * plugin.  Its purpose is to hook into formidable to run the autoresponder
 * functionality.
 */
class FrmAutoresponderAppController {

	/**
	 * The name of the option that holds the trigger counts.
	 *
	 * @var string
	 */
	public static $option_name = 'frm_autoresponder_sent_count';

	/**
	 * Initialize everything
	 *
	 * @return void
	 */
	public static function init() {
		// Easiest way to detect if Formidable Pro 2.
		if ( ! function_exists( 'frm_forms_autoloader' ) ) {
			add_action( 'admin_notices', __CLASS__ . '::incompatible_version' );
			return;
		}

		self::load_lang();
		add_action( 'formidable_send_autoresponder', __CLASS__ . '::send_autoresponder', 10, 2 );

		// Important, if you add another listener to frm_trigger_email_action, make sure you
		// remove it before actually doing that action in the send_autoresponder method.
		foreach ( FrmAutoresponderHelper::allowed_actions() as $action ) {
			self::load_hooks( $action );
		}

		add_filter( 'frm_skip_form_action', __CLASS__ . '::check_all_actions', 10, 2 );
		add_action( 'frm_after_update_entry', __CLASS__ . '::check_update_actions', 20 );
		add_action( 'frm_before_destroy_entry', __CLASS__ . '::unschedule_all_events_for_entry' );

		self::load_admin_hooks();
	}

	/**
	 * Load textdomain for translations.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	private static function load_lang() {
		load_plugin_textdomain( 'formidable-autoresponder', false, basename( FrmAutoresponderHelper::plugin_path() ) . '/languages/' );
	}

	private static function load_hooks( $action ) {
		add_action( 'frm_trigger_' . $action . '_action', __CLASS__ . '::pre_trigger_email', 2 );
		add_action( 'frm_trigger_' . $action . '_action', __CLASS__ . '::post_trigger_email', 1000 );
		add_action( 'frm_trigger_' . $action . '_action', __CLASS__ . '::trigger_email', 10, 2 );
	}

	/**
	 * Load hooks needed for settings in back-end.
	 *
	 * @since 2.0
	 */
	private static function load_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		$class = 'FrmAutoresponderSettingsController';
		add_action( 'frm_additional_action_settings', $class . '::form_action_settings', 10, 2 );
		add_action( 'admin_init', $class . '::include_updater', 1 );

		add_action( 'wp_ajax_formidable_autoresponder_logview', $class . '::log_viewer' );
		add_action( 'wp_ajax_formidable_autoresponder_delete_log', $class . '::delete_log_ajax' );
		add_action( 'wp_ajax_formidable_autoresponder_delete_queue_item', $class . '::delete_queue_item_ajax' );

		add_action( 'frm_enqueue_builder_scripts', __CLASS__ . '::enqueue_admin_scripts' );
		add_filter( 'frm_global_switch_fields', __CLASS__ . '::switch_autoresponder_fields_after_form_duplicate' );
		add_filter( 'frm_maybe_switch_field_ids', __CLASS__ . '::maybe_switch_field_ids' );
		add_action( 'after_delete_post', __CLASS__ . '::maybe_delete_events_for_action', 10, 2 );
		add_action( 'save_post_frm_form_actions', __CLASS__ . '::maybe_delete_old_autoresponder_events', 10, 2 );
	}

	/**
	 * Enqueue admin side js and css
	 *
	 * @since 2.06
	 *
	 * @return void
	 */
	public static function enqueue_admin_scripts() {
		$action = FrmAppHelper::simple_get( 'frm_action', 'sanitize_title' );
		if ( ! FrmAppHelper::is_admin_page() || $action !== 'settings' ) {
			return;
		}
		$version = FrmAutoresponderHelper::plugin_version();

		self::enqueue_admin_js( $version );
		self::enqueue_admin_styles( $version );
	}

	/**
	 * Enqueue our Javascript.
	 *
	 * @since 2.06 Moved from FrmAutoresponderSettingsController.
	 *
	 * @param string $version
	 * @return void
	 */
	public static function enqueue_admin_js( $version ) {
		wp_register_script( 'frm-autoresponder-admin', FrmAutoresponderHelper::plugin_url( 'js/frm-autoresponder-admin.js' ), array( 'formidable_admin' ), $version, true );
		wp_enqueue_script( 'frm-autoresponder-admin' );
	}

	/**
	 * Enqueue admin stylesheets
	 *
	 * @param string $version
	 *
	 * @return void
	 */
	private static function enqueue_admin_styles( $version ) {
		wp_register_style( 'formidable-autoresponder-admin', FrmAutoresponderHelper::plugin_url() . 'css/frm-autoresponder-admin.css', array(), $version );
		wp_enqueue_style( 'formidable-autoresponder-admin' );
	}

	/**
	 * Checks if the form action has an autoresponder and deletes any old events if not.
	 *
	 * @param int    $post_id ID of the form action.
	 * @param object $post    The form action post object.
	 */
	public static function maybe_delete_old_autoresponder_events( $post_id, $post ) {
		$autoresponder = FrmAutoresponder::get_autoresponder( $post_id );
		if ( ! $autoresponder ) {
			self::delete_events_for_action( $post_id, $post->menu_order );
		}
	}

	/**
	 * Checks if the deleted post is a form action and call function that deletes events attached to it.
	 *
	 * @param int    $post_id ID of the form action.
	 * @param object $post    The form action post object.
	 */
	public static function maybe_delete_events_for_action( $post_id, $post ) {

		if ( 'frm_form_actions' === $post->post_type ) {
			self::delete_events_for_action( $post_id, $post->menu_order );
		}
	}

	/**
	 * Deletes all events attached to the action, for the form entries.
	 *
	 * @param int $action_id ID of the form action.
	 * @param int $form_id   ID of the form.
	 */
	private static function delete_events_for_action( $action_id, $form_id ) {
		$entry_ids = FrmDb::get_col( 'frm_items', array( 'form_id' => $form_id ) );
		foreach ( $entry_ids as $entry_id ) {
			self::unqueue_autoresponder( $entry_id, $action_id );
		}
	}

	/**
	 * Unqueues autoresponder action for a given form entry.
	 *
	 * @param int $entry_id  ID of the form entry.
	 * @param int $action_id ID of the form action.
	 */
	private static function unqueue_autoresponder( $entry_id, $action_id ) {
		$args  = compact( 'entry_id', 'action_id' );
		$queue = new FrmAutoresponderQueue( $args );
		$queue->unschedule();
	}

	/**
	 * Keep record of all actions just in case the
	 * logic or triggers aren't met. This will allow
	 * us to stop scheduled events when logic is not met.
	 *
	 * @since 1.04
	 * @param bool  $skip
	 * @param array $atts
	 */
	public static function check_all_actions( $skip, $atts ) {
		if ( FrmAutoresponderHelper::is_allowed_action( $atts['action']->post_excerpt ) && 'update' === $atts['event'] ) {
			$autoresponder = FrmAutoresponder::get_autoresponder( $atts['action'] );
			if ( $autoresponder ) {
				self::add_action_to_global( $atts );
			}
		}
		return $skip;
	}

	/**
	 * After the entry is updated, run checks for actions that didn't trigger
	 *
	 * @since 1.04
	 * @param int $entry_id - The id of the entry to check.
	 */
	public static function check_update_actions( $entry_id ) {
		global $frm_vars;
		if ( isset( $frm_vars['action_check'] ) ) {
			foreach ( $frm_vars['action_check'] as $action_info ) {
				self::maybe_unschedule_skipped_action( $action_info, $entry_id );
			}
		}
	}

	/**
	 * When an entry is updated, check if it should be rescheduled.
	 *
	 * @since 1.04
	 * @param array $atts
	 * @param int   $entry_id
	 */
	private static function maybe_unschedule_skipped_action( $atts, $entry_id ) {
		$entry = $atts['entry'];
		if ( ! is_object( $entry ) ) {
			$entry = FrmEntry::getOne( $entry, true );
		}

		if ( $entry->id == $entry_id ) {
			$conditions_not_met = FrmFormAction::action_conditions_met( $atts['action'], $entry );
			if ( $conditions_not_met ) {
				self::unschedule_entry_events(
					array(
						'entry_id'  => $entry->id,
						'action_id' => $atts['action']->ID,
					)
				);
			} else {
				// The conditions were met, but the action isn't set to trigger on update.
				self::trigger_email( $atts['action'], $entry );
			}
		}
	}

	/**
	 * Indicate if a form action needs to be checked for automation.
	 *
	 * @since 1.04
	 * @param array $atts
	 */
	private static function add_action_to_global( $atts ) {
		global $frm_vars;
		if ( ! isset( $frm_vars['action_check'] ) ) {
			$frm_vars['action_check'] = array();
		}
		$frm_vars['action_check'][ $atts['action']->ID ] = $atts;
	}

	/**
	 * Remove an action if it shouldn't be triggered.
	 *
	 * @since 1.04
	 * @param array $atts
	 */
	private static function remove_action_from_global( $atts ) {
		global $frm_vars;
		if ( isset( $frm_vars['action_check'] ) && isset( $frm_vars['action_check'][ $atts['action']->ID ] ) ) {
			unset( $frm_vars['action_check'][ $atts['action']->ID ] );
		}
	}

	/**
	 * This trigger listens for the frm_trigger_email_action and executes very early in the queue.  Its purpose is to
	 * check for autoresponder actions where the "ignore default" is set to true.  If that condition is found, then it
	 * removes the FrmNotification::trigger_email action on 'frm_trigger_email_action'.  If it removes it, it will get
	 * added back in in the post_trigger_email method below.
	 *
	 * @param object $action - a post object for the action.
	 *
	 * @return void
	 */
	public static function pre_trigger_email( $action ) {
		$autoresponder = FrmAutoresponder::get_autoresponder( $action );
		if ( $autoresponder ) {
			// The conditions are met, so it's already handled from here.
			self::remove_action_from_global( compact( 'action' ) );

			// It's has an autoresponder component to the notification.  Is it set to ignore the default action?
			if ( 'no' === $autoresponder['do_default_trigger'] ) {
				if ( 'email' === $action->post_excerpt ) {
					self::stop_form_emails();
				} elseif ( 'twilio' === $action->post_excerpt ) {
					remove_action( 'frm_trigger_twilio_action', 'FrmTwloAppController::trigger_sms', 10 );
				} elseif ( 'api' === $action->post_excerpt ) {
					remove_action( 'frm_trigger_api_action', 'FrmAPISettingsController::trigger_api', 10 );
				} elseif ( 'register' === $action->post_excerpt ) {
					remove_action( 'frm_trigger_register_action', 'FrmRegUserController::register_user', 10 );
				} elseif ( 'zapier' === $action->post_excerpt ) {
					remove_action( 'frm_trigger_zapier_action', 'FrmZapApiController::send_entry_to_zapier', 10 );
				}
			}
		}
	}

	/**
	 * Stop the email notifications since most users won't need these
	 *
	 * @since 2.0
	 */
	private static function stop_form_emails() {
		if ( is_callable( 'FrmNotification::stop_emails' ) ) {
			FrmNotification::stop_emails();
		} else {
			remove_action( 'frm_trigger_email_action', 'FrmNotification::trigger_email', 10 );
		}
	}

	/**
	 * Attach the email functions to the form actions
	 *
	 * @since 2.0
	 */
	private static function hook_email_function_to_action() {
		if ( is_callable( 'FrmNotification::hook_emails_to_action' ) ) {
			FrmNotification::hook_emails_to_action();
		} else {
			add_action( 'frm_trigger_email_action', 'FrmNotification::trigger_email', 10, 3 );
		}
	}

	/**
	 * This trigger listens for the frm_trigger_email_action and executes very late in the queue.  Its purpose is to
	 * check for autoresponder actions where the "ignore default" is set to true.  If that condition is found, then it
	 * adds back in the FrmNotification::trigger_email listener on 'frm_trigger_email_action' that was removed in
	 * the pre_trigger_email method above.
	 *
	 * It needs to get added back in in case there subsequent Form Actions that maybe want to email.
	 *
	 * @param object $action - a post object for the action.
	 *
	 * @return void
	 */
	public static function post_trigger_email( $action ) {
		$autoresponder = FrmAutoresponder::get_autoresponder( $action );
		if ( $autoresponder ) {
			// It's has an autoresponder component to the notification.  Is it set to ignore the default action?
			if ( 'no' === $autoresponder['do_default_trigger'] ) {
				if ( 'email' === $action->post_excerpt ) {
					self::hook_email_function_to_action();
				} elseif ( 'twilio' === $action->post_excerpt ) {
					add_action( 'frm_trigger_twilio_action', 'FrmTwloAppController::trigger_sms', 10, 3 );
				} elseif ( 'api' === $action->post_excerpt ) {
					add_action( 'frm_trigger_api_action', 'FrmAPISettingsController::trigger_api', 10, 3 );
				} elseif ( 'register' === $action->post_excerpt ) {
					add_action( 'frm_trigger_register_action', 'FrmRegUserController::register_user', 10, 3 );
				} elseif ( 'zapier' === $action->post_excerpt ) {
					add_action( 'frm_trigger_zapier_action', 'FrmZapApiController::send_entry_to_zapier', 10, 4 );
				}
			}
		}
	}

	/**
	 * This is the method that will actually schedule the Autoresponder.
	 *
	 * @param object $action - a post object for the action.
	 * @param object $entry  - an object for the entry.
	 *
	 * @return void
	 */
	public static function trigger_email( $action, $entry ) {
		self::unschedule_entry_events(
			array(
				'entry_id'  => $entry->id,
				'action_id' => $action->ID,
			)
		);

		if ( FrmAutoresponder::has_autoresponder( $action ) ) {
			self::trigger_autoresponder( $entry, $action );
		}
	}

	/**
	 * This is the method that gets called to schedule the initial autoresponder.  This happens after the form has
	 * been created or updated.
	 *
	 * Note, there's a bit of a gotcha in here.  The user can setup the autoresponder to trigger after the update date,
	 * but if the notification itself is not setup to trigger off of the update event, then it'll never get here.  @todo
	 * would be to fix that.
	 *
	 * Second note: this does not actually send out the email.  This just schedules a cron job to send it out.
	 *
	 * @param object $entry  - an object for the entry.
	 * @param object $action - a post object for the action.
	 *
	 * @return void
	 */
	public static function trigger_autoresponder( $entry, $action ) {
		$autoresponder = FrmAutoresponder::get_autoresponder( $action );
		if ( ! $autoresponder ) {
			return;
		}

		if ( $entry->is_draft && ! in_array( 'draft', $action->post_content['event'], true ) ) {
			return;
		}

		$reference_date = self::get_trigger_date( compact( 'entry', 'action', 'autoresponder' ) );

		if ( empty( $reference_date ) ) {
			// No date supplied, nothing to do.
			return;
		}

		$trigger_ts = self::calculate_trigger_timestamp( $reference_date, compact( 'autoresponder', 'entry' ) );

		// 'initial' means this is the initial autoresponder.
		$trigger_ts = apply_filters( 'formidable_autoresponder_trigger_timestamp', $trigger_ts, $reference_date, $entry, $action, 'initial' );

		if ( ! $trigger_ts ) {
			/* translators: %1$s: Action name, %2$d: Entry ID, %3$s: the settings */
			self::debug( sprintf( __( 'Not scheduling "%1$s" action for entry #%2$d because the settings are invalid: %3$s.', 'formidable-autoresponder' ), $action->post_title, $entry->id, print_r( $autoresponder, true ) ), $action );
		} elseif ( $trigger_ts < time() && ! ( self::autoresponder_is_scheduled_after_create_date( $autoresponder ) ) ) {
			/* translators: %1$s: Action name, %2$d: Entry ID, %3$s: Date */
			self::debug( sprintf( __( 'Not scheduling "%1$s" action for entry #%2$d for %3$s because the time has already passed.', 'formidable-autoresponder' ), $action->post_title, $entry->id, gmdate( 'Y-m-d H:i:s', $trigger_ts ) ), $action );
		} else {
			self::schedule_event(
				array(
					'timestamp' => $trigger_ts,
					'entry_id'  => $entry->id,
					'action'    => $action,
				)
			);
		}

		/* translators: %s: Timestamp */
		self::debug( sprintf( __( 'Reference TS: %s', 'formidable-autoresponder' ), self::format_timestamp( strtotime( $reference_date ) ) ), $action );
	}

	/**
	 * Returns true if the autoresponder is scheduled after the created date.
	 *
	 * @param array $autoresponder
	 *
	 * @return bool
	 */
	private static function autoresponder_is_scheduled_after_create_date( $autoresponder ) {
		return $autoresponder['send_before_after'] === 'after' && $autoresponder['send_date'] === 'create';
	}

	/**
	 * Get the date from the entry to trigger the action.
	 *
	 * @since 2.0
	 *
	 * @param array $atts
	 * @return string
	 */
	private static function get_trigger_date( $atts ) {
		$send_date      = $atts['autoresponder']['send_date'];
		$reference_date = '';
		if ( strpos( $send_date, '-' ) ) {
			// Based on a date and time field.
			list( $date_field, $time_field ) = explode( '-', $send_date );
			$reference_date                  = $atts['entry']->metas[ $date_field ];
			$atts['time']                    = $atts['entry']->metas[ $time_field ];
			if ( $reference_date ) {
				self::localize_date( $reference_date, $atts );
			}
		} elseif ( is_numeric( $send_date ) ) {
			// Based on a date field.
			if ( ! empty( $atts['entry']->metas[ $send_date ] ) ) {
				$reference_date = $atts['entry']->metas[ $send_date ];
				self::localize_date( $reference_date, $atts );
			}
		} elseif ( 'update' === $send_date ) {
			$reference_date = self::filter_time_for_datetime_value( $atts['entry']->updated_at, $atts );
		} else {
			$reference_date = self::filter_time_for_datetime_value( $atts['entry']->created_at, $atts );
		}
		return $reference_date;
	}

	/**
	 * Convert the date from the user's timezone to GMT.
	 *
	 * @since 1.2
	 *
	 * @param string $reference_date The original date in the user's timezone.
	 * @param array  $atts           Includes 'time'.
	 * @return void
	 */
	private static function localize_date( &$reference_date, $atts ) {
		$atts['date']   = $reference_date;
		$trigger_time   = self::filter_trigger_time( $atts );
		$trigger_time   = gmdate( 'H:i:s', strtotime( $trigger_time ) );
		$reference_date = gmdate( 'Y-m-d H:i:s', strtotime( $reference_date . ' ' . $trigger_time ) );
		$reference_date = get_gmt_from_date( $reference_date );
	}

	/**
	 * Break up a datetime string and filter the time before imploding it back together.
	 *
	 * @since x.x
	 *
	 * @param string $datetime Date string in Y-m-d H:i:s format.
	 * @param array  $atts
	 * @return string Date string in Y-m-d H:i:s format.
	 */
	private static function filter_time_for_datetime_value( $datetime, $atts ) {
		list( $atts['date'], $atts['time'] ) = explode( ' ', $datetime );
		$filtered_date = $atts['date'] . ' ' . self::filter_trigger_time( $atts );
		if ( $filtered_date !== $datetime ) {
			// If the value is filtered, convert it to UTC for consistency with localize_date.
			$filtered_date = get_gmt_from_date( $filtered_date );
		}
		return $filtered_date;
	}

	/**
	 * Check the time from the attributes and then filter it through frm_autoresponder_time.
	 * If the attributes do not include time, 00:00:00 is used by default.
	 *
	 * @since x.x
	 *
	 * @param array $atts
	 * @return string
	 */
	private static function filter_trigger_time( $atts ) {
		if ( ! empty( $atts['time'] ) ) {
			$time = $atts['time'];
		} else {
			$time         = '00:00:00';
			$atts['time'] = $time;
		}

		/**
		 * Filter the trigger time so it can be modified with custom code.
		 *
		 * @since 1.03
		 *
		 * @param string $time
		 * @param array  $atts
		 */
		$filtered_time = apply_filters( 'frm_autoresponder_time', $time, $atts );

		if ( is_string( $filtered_time ) ) {
			$time = $filtered_time;
		} else {
			_doing_it_wrong( __METHOD__, esc_html__( 'Only a string should be passed as the autoresponder trigger time.', 'formidable-autoresponder' ), 'x.x' );
		}

		return $time;
	}

	/**
	 * Given the reference date, which is anything that passes strtotime(), and the settings, calculate the next trigger
	 * timestamp.
	 *
	 * @param string $reference_date any date that satisfies strtotime().  For example, YYYY-mm-dd.
	 * @param array  $atts
	 *               - entry
	 *               - autoresponder  the settings for this particular autoresponder.  We pay attention to
	 *                                  - send_before_after - which says if we should trigger before or after the reference
	 *                                    date
	 *                                  - send_unit - which is 'minutes', 'hours', 'days', 'months', 'years'
	 *                                  - send_interval which is how many send_units we should calculate
	 *
	 * @return int|boolean a timestamp if all is good, false if $reference_date does not translate to a date
	 */
	public static function calculate_trigger_timestamp( $reference_date, $atts ) {
		$autoresponder = $atts['autoresponder'];
		$reference_ts = strtotime( $reference_date );
		if ( ! $reference_ts ) {
			return false;
		}
		$valid_parms = array(
			'send_before_after' => array( 'before', 'after' ),
			'send_unit' => array( 'minutes', 'hours', 'days', 'months', 'years' ),
			'send_interval' => 'is_numeric',
		);
		foreach ( $valid_parms as $parm => $valid ) {
			if ( ! isset( $autoresponder[ $parm ] ) ) {
				return false;
			}

			$is_invalid_php = is_callable( $valid ) && ! $valid( $autoresponder[ $parm ] );
			$not_in_valid_array = is_array( $valid ) && ! in_array( $autoresponder[ $parm ], $valid );
			if ( $not_in_valid_array || $is_invalid_php ) {
				return false;
			}
		}

		$one = ( 'before' === $autoresponder['send_before_after'] ) ? -1 : 1;
		$multiplier = ( 1 === $one ? '+' : '' ) . $one * $autoresponder['send_interval'];
		$trigger_on = strtotime( $multiplier . ' ' . $autoresponder['send_unit'], $reference_ts );

		if ( $trigger_on < time() ) {
			self::get_future_date(
				$trigger_on,
				array(
					'autoresponder' => $autoresponder,
					'entry'         => $atts['entry'],
				)
			);
		}

		return $trigger_on;
	}

	/**
	 * Get the next date in the future to schedule the action.
	 *
	 * @since 1.2
	 * @param string $trigger_on - The next time to trigger the action.
	 * @param array  $atts - Contains 'entry' and 'autoresponder'.
	 */
	private static function get_future_date( &$trigger_on, $atts ) {

		$autoresponder = $atts['autoresponder'];
		if ( empty( $autoresponder['send_after'] ) ) {
			// Don't trigger if date has passed, and it is repeating.
			return;
		}

		$autoresponder = self::get_repeat_settings( $autoresponder, $atts['entry'] );

		if ( empty( $autoresponder['send_interval'] ) ) {
			// If the interval is 0, prevent an infinite loop.
			$autoresponder['send_interval'] = 1;
			$autoresponder['send_unit'] = 'minutes';
		}

		while ( $trigger_on < time() ) {
			$trigger_on = strtotime( '+' . $autoresponder['send_interval'] . ' ' . $autoresponder['send_unit'], $trigger_on );
		}
	}

	/**
	 * The method that listens to the cron job action 'formidable_send_autoresponder'.  It is passed in an entry id
	 * and action id.  It looks both of those up and if they both exist, then it checks to see if the action conditions
	 * are still met.  If they are, then it increments the sent counter for this entry id (stored in an option)
	 *
	 * @param int $entry_id - The id of the entry.
	 * @param int $action_id - The id of the form action.
	 */
	public static function send_autoresponder( $entry_id, $action_id ) {
		self::unschedule_entry_events( compact( 'entry_id', 'action_id' ) );

		$entry = FrmEntry::getOne( $entry_id, true );
		if ( empty( $entry ) ) {
			// Entry no longer exists, do not send the autoresponder.
			return;
		}

		$action = FrmAutoresponder::get_action( $action_id );
		if ( empty( $action ) ) {
			return;
		}

		$autoresponder = FrmAutoresponder::get_autoresponder( $action );

		$stop = FrmFormAction::action_conditions_met( $action, $entry );
		if ( $stop ) {
			/* translators: %1$s: Action name, %2$d: Entry ID */
			self::debug( sprintf( __( 'Conditions for "%1$s" action for entry #%2$d not met. Halting.', 'formidable-autoresponder' ), $action->post_title, $entry->id, gmdate( 'Y-m-d H:i:s' ) ), $action );
			return;
		}

		/* translators: %1$s: Action name, %2$d: Entry ID */
		self::debug( sprintf( __( 'Conditions for "%1$s" action for entry #%2$d met. Proceeding.', 'formidable-autoresponder' ), $action->post_title, $entry->id, gmdate( 'Y-m-d H:i:s' ) ), $action );

		// If the count is needed, get it.
		$has_limit  = $autoresponder['send_after'] && $autoresponder['send_after_limit'];
		$sent_count = $has_limit ? self::get_sent_count( $entry_id, $action_id ) : 0;

		$limit_reached = $has_limit && $sent_count >= $autoresponder['send_after_count'];
		$should_send   = ! $sent_count || ! $autoresponder['send_after'] || ! $limit_reached;

		if ( ! $should_send ) {
			/* translators: %1$s: Action name, %2$d: number */
			self::debug( sprintf( __( 'Not triggering %1$s because there we have already sent out the limit of %2$d.', 'formidable-autoresponder' ), $action->post_excerpt, $sent_count ), $action );
			return;
		}

		// Make sure hooks are loaded.
		new FrmNotification();

		// First, remove our pre/post listeners ( this is a scheduled autoresponder, not something triggered
		// immediately after creating/updating the record ).
		$trigger_name = 'frm_trigger_' . $action->post_excerpt . '_action';
		remove_action( $trigger_name, __CLASS__ . '::pre_trigger_email', 2 );
		remove_action( $trigger_name, __CLASS__ . '::post_trigger_email', 1000 );
		remove_action( $trigger_name, __CLASS__ . '::trigger_email', 10 );

		// Now, do the action ( this will trigger FrmNotification::trigger_email() ).
		/* translators: %1$s: Action type, %2$s: Action name */
		self::debug( sprintf( __( 'Triggering %1$s action for "%2$s"', 'formidable-autoresponder' ), $action->post_excerpt, $action->post_title ), $action );
		do_action( $trigger_name, $action, $entry, FrmForm::getOne( $entry->form_id ), 'create' );

		$sent_count++;

		if ( $has_limit ) {
			// If the count is needed, save it.
			self::update_sent_count( $entry_id, $action_id, $sent_count );
		}

		// If necessary, setup the next event.
		if ( $autoresponder['send_after'] ) {
			if ( ! $autoresponder['send_after_limit'] || $sent_count < $autoresponder['send_after_count'] ) {
				self::schedule_next_event( $autoresponder, $entry, $action );
			}
		}

		// Replace actions for other responders.
		self::load_hooks( $action->post_excerpt );
	}

	/**
	 * Get the number of times this entry has triggered the action.
	 * Check the transient as a fallback and delete it if exists since it's no longer needed.
	 *
	 * @param int $entry_id - The current entry.
	 * @param int $action_id - The current form action.
	 *
	 * @since 2.04
	 */
	private static function get_sent_count( $entry_id, $action_id ) {
		$old_name   = self::$option_name . '_' . $entry_id . '_' . $action_id;
		$sent_count = get_transient( $old_name );
		if ( $sent_count !== false ) {
			delete_transient( $old_name );
			return intval( $sent_count );
		}

		$all_count = get_option( self::$option_name );
		if ( $all_count && ! empty( $all_count[ $action_id ][ $entry_id ] ) ) {
			$sent_count = $all_count[ $action_id ][ $entry_id ];
		}

		if ( ! $sent_count ) {
			$sent_count = 0;
		} else {
			$sent_count = intval( $sent_count );
		}
		return $sent_count;
	}

	/**
	 * If the old transient exists, delete it since it's getting saved in a single spot.
	 *
	 * @param int $entry_id - The current entry.
	 * @param int $action_id - The current form action.
	 * @param int $count - The number triggered.
	 *
	 * @since 2.04
	 */
	private static function update_sent_count( $entry_id, $action_id, $count ) {
		$name     = self::$option_name;
		$old_name = $name . '_' . $entry_id . '_' . $action_id;
		delete_transient( $old_name );

		$all_count = get_option( $name );
		if ( ! $all_count ) {
			$all_count = array();
		}

		if ( ! isset( $all_count[ $action_id ] ) ) {
			$all_count[ $action_id ] = array();
		}
		$all_count[ $action_id ][ $entry_id ] = $count;
		update_option( $name, $all_count, 'no' );
	}

	/**
	 * Get the timestamp for the next event and schedule.
	 *
	 * @param array  $autoresponder - The settings for the current action.
	 * @param object $entry - The current entry.
	 * @param object $action - The current form action.
	 *
	 * @since 2.04
	 */
	private static function schedule_next_event( $autoresponder, $entry, $action ) {
		$after_settings = self::get_repeat_settings( $autoresponder, $entry );

		$reference_date = gmdate( 'Y-m-d H:i:s' );
		$trigger_ts     = self::calculate_trigger_timestamp(
			$reference_date,
			array(
				'autoresponder' => $after_settings,
				'entry'         => $entry,
			)
		);

		// 'followup' means this is a followup autoresponder
		$trigger_ts = apply_filters( 'formidable_autoresponder_trigger_timestamp', $trigger_ts, $reference_date, $entry, $action, 'followup' );

		self::schedule_event(
			array(
				'timestamp' => $trigger_ts,
				'entry_id'  => $entry->id,
				'action'    => $action,
			)
		);
	}

	/**
	 * Schedule a new event based on the entry and form action.
	 *
	 * @since 1.03
	 * @param array $atts - The info about the event to schedule.
	 */
	private static function schedule_event( $atts ) {
		/* translators: %1$s: Action name, %2$d: entry id, %3$s: timestamp */
		self::debug( sprintf( __( 'Scheduling "%1$s" action for entry #%2$d for %3$s', 'formidable-autoresponder' ), $atts['action']->post_title, $atts['entry_id'], self::format_timestamp( $atts['timestamp'] ) ), $atts['action'] );

		$queue = new FrmAutoresponderQueue(
			array(
				'entry_id'  => $atts['entry_id'],
				'action_id' => $atts['action']->ID,
				'timestamp' => $atts['timestamp'],
			)
		);
		$queue->schedule();
	}

	/**
	 * Clear out all scheduled hooks for this entry/action
	 *
	 * @param array $atts includes entry_id and/or action_id.
	 * @since 1.03
	 */
	public static function unschedule_entry_events( $atts ) {
		$queue = new FrmAutoresponderQueue( $atts );
		$queue->unschedule();
	}

	/**
	 * Clear out all scheduled hooks for all actions for a deleted entry
	 *
	 * @since 2.0
	 * @param int $entry_id - The id of the entry to schedule.
	 */
	public static function unschedule_all_events_for_entry( $entry_id ) {
		self::unschedule_entry_events( compact( 'entry_id' ) );
	}

	private static function get_repeat_settings( $autoresponder, $entry ) {
		return array_merge(
			$autoresponder,
			array(
				'send_before_after' => 'after',
				'send_interval'     => ( 'field' === $autoresponder['send_after_interval_type'] ) ? $entry->metas[ $autoresponder['send_after_interval_field'] ] : $autoresponder['send_after_interval'],
				'send_unit'         => $autoresponder['send_after_unit'],
			)
		);
	}

	/**
	 * Helps to format a timestamp date to get it in the defined local timezone instead of UTC
	 *
	 * @param  string $timestamp
	 * @param  string $date_format
	 *
	 * @return string
	 */
	private static function format_timestamp( $timestamp, $date_format = 'Y-m-d H:i:s' ) {
		return FrmAppHelper::get_localized_date( $date_format, gmdate( $date_format, $timestamp ) );
	}

	/**
	 * If the site is running Formidable Pro 1.x, then this plugin will not work.  Show a notification.
	 *
	 * @return void
	 */
	public static function incompatible_version() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'Formidable Autoresponder requires that Formidable Pro version 2.0 or greater be installed.  Until then, keep Formidable Autoresponder activated only to continue enjoying this insightful message.', 'formidable-autoresponder' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Print a message out to a debug file.  This is useful for debugging
	 * to make sure that the emails are getting triggered.
	 *
	 * @param string $message - what to debug.
	 * @param object $action - The form action.
	 *
	 * @return void
	 */
	public static function debug( $message, $action ) {
		$log = new FrmAutoresponderLog( compact( 'action' ) );
		$log->add( $message );
	}

	/**
	 * Add new fields that must be swtiched after duplicating a form
	 *
	 * @param  array $switch fields to be swtiched.
	 *
	 * @return array         fields to be swtiched
	 */
	public static function switch_autoresponder_fields_after_form_duplicate( $switch ) {
		$switch['autoresponder'] = array( 'send_date', 'send_after_interval_field' );
		return $switch;
	}

	/**
	 * Switch multiple field ids separated by hyphens [12-34].
	 * The hyphen is used for the Date + Time setting used in Automation settings.
	 *
	 * @param  string $ids Field ids to be switched.
	 * @return string
	 */
	public static function maybe_switch_field_ids( $ids ) {
		if ( false === strpos( $ids, '-' ) ) {
			return $ids;
		}

		$pieces = explode( '-', $ids );
		if ( 2 !== count( $pieces ) ) {
			// Avoid false positives.
			// We do not want to match something like format="Y-m-d".
			return $ids;
		}

		$new_field_ids = array_map( __CLASS__ . '::trim_and_switch_field_ids', $pieces );
		return implode( '-', $new_field_ids );
	}

	/**
	 * Switch field ids in bracket format
	 *
	 * @param  string $field_id
	 *
	 * @return string
	 */
	private static function trim_and_switch_field_ids( $field_id ) {
		return trim( FrmFieldsHelper::switch_field_ids( '[' . $field_id . ']' ), '[]' );
	}

	public static function get_action( $action_id ) {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponder::' . __FUNCTION__ );
		return FrmAutoresponder::get_action( $action_id );
	}

	public static function plugin_url( $subpath = '' ) {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderHelper::' . __FUNCTION__ );
		return FrmAutoresponderHelper::plugin_url( $subpath );
	}

	public static function plugin_path( $subpath = '' ) {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderHelper::' . __FUNCTION__ );
		return FrmAutoresponderHelper::plugin_path( $subpath );
	}

	public static function get_queue( $action_id ) {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderSettingsController::' . __FUNCTION__ );
		$queue = new FrmAutoresponderQueue( compact( 'action_id' ) );
		return $queue->get_all();
	}

	public static function get_latest_debug_urls( $action_id ) {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderSettingsController::' . __FUNCTION__ );
		return FrmAutoresponderSettingsController::get_latest_debug_urls( $action_id );
	}

	public static function log_viewer() {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderSettingsController::' . __FUNCTION__ );
		FrmAutoresponderSettingsController::log_viewer();
	}

	public static function delete_log_ajax() {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderSettingsController::' . __FUNCTION__ );
		FrmAutoresponderSettingsController::delete_log_ajax();
	}

	public static function delete_log( $args ) {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderLog()->delete' );
		$url = sanitize_text_field( $args['url'] );
		$log = new FrmAutoresponderLog();
		$log->delete( $url );
	}

	public static function delete_queue_item_ajax() {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderSettingsController::' . __FUNCTION__ );
		FrmAutoresponderSettingsController::delete_queue_item_ajax();
	}

	public static function delete_queue_item( $args ) {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderQueue->unschedule' );
		$args = wp_parse_args( $args );
		$queue = new FrmAutoresponderQueue( $args );
		$queue->unschedule();
	}

	public static function form_action_settings( $form_action, $atts ) {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderSettingsController::' . __FUNCTION__ );
		FrmAutoresponderSettingsController::form_action_settings( $form_action, $atts );
	}

	public static function admin_js() {
		_deprecated_function( __FUNCTION__, '2.0', 'FrmAutoresponderSettingsController::' . __FUNCTION__ );
		FrmAutoresponderSettingsController::admin_js();
	}
}
