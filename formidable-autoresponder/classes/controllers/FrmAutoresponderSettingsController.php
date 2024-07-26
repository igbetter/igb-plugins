<?php
/**
 * Show the form action settings and handle the ajax requests.
 */
class FrmAutoresponderSettingsController {

	public static function include_updater() {
		FrmAutoresponderUpdate::load_hooks();
	}

	/**
	 * Setup and include the settings view
	 *
	 * @param object $form_action The form action to get automation settings for.
	 * @param array  $atts Extra information about the form and form action.
	 * @return void
	 */
	public static function form_action_settings( $form_action, $atts ) {
		if ( ! FrmAutoresponderHelper::is_allowed_action( $form_action->post_excerpt ) ) {
			return;
		}

		$form = $atts['form'];
		$action_key = $atts['action_key'];
		$fields = $atts['values']['fields'];

		$has_number_field = false;
		$date_fields = array();
		$time_fields = array();
		foreach ( $fields as $field ) {
			if ( 'number' === $field['type'] ) {
				$has_number_field = true;
			} elseif ( 'date' === $field['type'] ) {
				$date_fields[] = $field;
			} elseif ( 'time' === $field['type'] ) {
				$time_fields[] = $field;
			}
		}

		$input_name = $atts['action_control']->get_field_name( 'autoresponder' );
		$autoresponder = FrmAutoresponder::get_autoresponder( $form_action, false );
		if ( ! $autoresponder ) {
			$autoresponder              = FrmAutoresponder::get_default_autoresponder();
			$autoresponder['is_active'] = false;
		}
		$is_active = $autoresponder['is_active'];
		$time_units = array(
			'days'    => __( 'Days', 'formidable-autoresponder' ),
			'years'   => __( 'Years', 'formidable-autoresponder' ),
			'months'  => __( 'Months', 'formidable-autoresponder' ),
			'hours'   => __( 'Hours', 'formidable-autoresponder' ),
			'minutes' => __( 'Minutes', 'formidable-autoresponder' ),
		);

		$debug_urls = self::get_latest_debug_urls( $form_action );
		$debug_urls_more = 5; // The number of logs to show initially before the "more" link.

		$queue = self::get_queue( $form_action->ID );
		$queue_more = 5;

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		include( FrmAutoresponderHelper::plugin_path( 'classes/views/settings.php' ) );

		static $once;
		if ( ! isset( $once ) ) {
			$once = 'done';
			?>
			<style type="text/css">
			.dashicons.spin {
				animation: dashicons-spin 1s infinite;
				animation-timing-function: linear;
			}

			@keyframes dashicons-spin {
				0% {
					transform: rotate( 0deg );
				}
				100% {
					transform: rotate( 360deg );
				}
			}
			</style>
			<?php
		}
	}

	/**
	 * Gets a list of whatever is in the queue
	 *
	 * @param int $action_id The action id.
	 *
	 * @return array
	 */
	public static function get_queue( $action_id ) {
		$queue = new FrmAutoresponderQueue( compact( 'action_id' ) );
		return $queue->get_all();
	}

	/**
	 * The AJAX listener for deleting a particular queue item.  It is based on the posted
	 * values for timestamp, entry_id and action_id
	 * POST includes the keys 'timestamp', 'entry_id', 'action_id' ( array or url parameter string )
	 *
	 * @return void
	 */
	public static function delete_queue_item_ajax() {
		FrmAppHelper::permission_check( 'frm_edit_forms' );
		check_ajax_referer( 'frm_ajax', 'nonce' );

		$args = wp_parse_args( $_POST );
		$queue = new FrmAutoresponderQueue( $args );
		$queue->unschedule();

		wp_die();
	}

	/**
	 * Get the latest debug log files for the given action as an array of URLs
	 *
	 * @param mixed $action
	 *
	 * @return array|bool
	 */
	public static function get_latest_debug_urls( $action ) {
		if ( empty( $action ) ) {
			return false;
		}

		if ( is_numeric( $action ) ) {
			$action = FrmAutoresponder::get_action( $action );
			if ( ! $action ) {
				return false;
			}
		}

		$log = new FrmAutoresponderLog( compact( 'action' ) );
		return $log->get_urls();
	}

	/**
	 * A listener for the ajax action, takes the log entry in the autoresponder logs directory and displays it nice and
	 * pretty like
	 *
	 * @return void
	 */
	public static function log_viewer() {
		FrmAppHelper::permission_check( 'frm_edit_forms' );
		check_ajax_referer( 'frm_ajax', 'nonce' );

		if ( isset( $_REQUEST['log'] ) ) {
			$url = sanitize_text_field( wp_unslash( $_REQUEST['log'] ) );
			$log = new FrmAutoresponderLog();
			$log->get_content( $url );
		}

		wp_die();
	}

	/**
	 * The AJAX listener for deleting a log file.  Does a sanity check to make sure that we are only attempting to
	 * delete real autoresponder log files.  Deletes the file based on the $_POST['url'] variable.
	 *
	 * @return void
	 */
	public static function delete_log_ajax() {
		FrmAppHelper::permission_check( 'frm_edit_forms' );
		check_ajax_referer( 'frm_ajax', 'nonce' );

		$url = isset( $_POST['url'] ) ? sanitize_text_field( wp_unslash( $_POST['url'] ) ) : '';
		$log = new FrmAutoresponderLog();
		$log->delete( $url );

		wp_die();
	}

	/**
	 * Enqueue admin side js.
	 *
	 * @deprecated 2.06
	 * @return void
	 */
	public static function admin_js() {
		_deprecated_function( __METHOD__, '2.06', __METHOD__ );
		FrmAutoresponderAppController::enqueue_admin_js( FrmAutoresponderHelper::plugin_version() );
	}
}
