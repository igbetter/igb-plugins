<?php
/**
 * Hook Formidable Campaign Monitor into Formidable Forms Plugin.
 *
 * @package CampaignMonitor
 * @author Strategy11
 */

/**
 * FrmCMAppController Class is the main controller to hook into formidable.
 */
class FrmCMAppController {

	/**
	 * Minimum Formidable Forms Plugin Version required
	 *
	 * @var string
	 */
	public static $min_version = '4.0';

	public static function min_version_notice() {
		$frm_version = is_callable( 'FrmAppHelper::plugin_version' ) ? FrmAppHelper::plugin_version() : 0;

		// check if Formidable meets minimum requirements.
		if ( version_compare( $frm_version, self::$min_version, '>=' ) ) {
			return;
		}

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );

		echo '<tr class="plugin-update-tr active"><th colspan="' . (int) $wp_list_table->get_column_count() . '" class="check-column plugin-update colspanchange"><div class="update-message">' .
			esc_html_e( 'You are running an outdated version of Formidable. This plugin needs Formidable v4.0 + to work correctly.', 'frm-campaignmonitor' ) .
			'</div></td></tr>';
	}

	/**
	 * Loads translation.
	 *
	 * @since 1.05
	 *
	 * @return void
	 */
	public static function load_lang() {
		load_plugin_textdomain( 'frm-campaignmonitor', false, basename( self::path() ) . '/languages/' );
	}

	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			include self::path() . '/models/FrmCMUpdate.php';
			FrmCMUpdate::load_hooks();
		}
	}

	public static function path() {
		return dirname( dirname( __FILE__ ) );
	}

	public static function plugin_url() {
		return plugins_url() . '/' . basename( self::path() );
	}

	public static function clear_cache() {
		check_ajax_referer( 'frmcampaignmonitor_ajax', 'security', true );
		delete_transient( 'frm_cm_lists' );
		delete_transient( 'frm_cm_list_fields' );

		wp_die();
	}

	public static function trigger_campaignmonitor( $action, $entry, $form ) {
		$settings = $action->post_content;
		$entry_id = $entry->id;

		$vars = self::prepare_mapped_values( $action->post_content, $entry );

		$list_id    = $settings['list_id'];
		$subscriber = self::prepare_subscriber( $vars );

		if ( ! empty( $list_id ) ) {
			$api = new FrmCMAPI( compact( 'entry_id', 'action' ) );
			$api->add_subscriber( $list_id, $subscriber );
		}
	}

	private static function prepare_mapped_values( $settings, $entry ) {
		$vars = array();

		foreach ( $settings['fields'] as $field_tag => $field_id ) {
			if ( empty( $field_id ) ) {
				// don't sent an empty value.
				continue;
			}
			$vars[ $field_tag ] = self::get_entry_or_post_value( $entry, $field_id );

			$field = FrmField::getOne( $field_id );
			if ( is_numeric( $vars[ $field_tag ] ) ) {
				if ( 'user_id' == $field->type ) {
					$user_data = get_userdata( $vars[ $field_tag ] );
					if ( 'email' == $field_tag ) {
						$vars[ $field_tag ] = $user_data->user_email;
					} elseif ( 'fullname' == $field_tag ) {
						$vars[ $field_tag ] = $user_data->first_name . ' ' . $user_data->last_name;
					} else {
						$vars[ $field_tag ] = $user_data->user_login;
					}
				} elseif ( 'date' === $field->type ) {
					self::get_display_value( compact( 'field', 'entry' ), $vars[ $field_tag ] );
					$vars[ $field_tag ] = date( 'Y/m/d', strtotime( $vars[ $field_tag ] ) );
				} else {
					self::get_display_value( compact( 'field', 'entry' ), $vars[ $field_tag ] );
				}
			}

			if ( is_array( $vars[ $field_tag ] ) && 'file' === $field->type ) {
				self::get_display_value( compact( 'field', 'entry' ), $vars[ $field_tag ] );
			} elseif ( is_array( $vars[ $field_tag ] ) && 'name' === $field->type && 'fullname' === $field_tag ) {
				$field_obj          = FrmFieldFactory::get_field_object( $field );
				$vars[ $field_tag ] = $field_obj->get_display_value( $vars[ $field_tag ] );
			}
		}

		return $vars;

	}

	private static function get_display_value( $atts, &$value ) {
		if ( 'file' === $atts['field']->type ) {
			$value = FrmProEntriesController::get_field_value_shortcode(
				array(
					'field_id' => $atts['field']->id,
					'entry_id' => $atts['entry']->id,
					'show'     => '1',
					'html'     => 0,
				)
			);
			return;
		}

		$value = FrmEntriesHelper::display_value(
			$value,
			$atts['field'],
			array(
				'type'     => $atts['field']->type,
				'truncate' => false,
				'entry_id' => $atts['entry']->id,
			)
		);
	}

	private static function prepare_subscriber( $vars ) {
		$subscriber = array(
			'EmailAddress'   => $vars['email'],
			'Name'           => $vars['fullname'],
			'CustomFields'   => self::entry_to_custom_fields( $vars ),
			'ConsentToTrack' => 'Unchanged',
		);

		return $subscriber;
	}

	/**
	 * Get the entry values into the custom field format required.
	 *
	 * @param array $vars The values submitted in each field.
	 * @return array
	 */
	private static function entry_to_custom_fields( $vars ) {
		$custom_fields = array();

		foreach ( $vars as $key => $value ) {
			if ( 'email' === $key || 'fullname' === $key ) {
				continue;
			}
			if ( is_array( $value ) ) {
				foreach ( $value as $val ) {
					$custom_fields[] = array(
						'Key'   => $key,
						'Value' => $val,
					);
				}
			} else {
				$custom_fields[] = array(
					'Key'   => $key,
					'Value' => $value,
				);
			}
		}

		return $custom_fields;
	}

	public static function get_entry_or_post_value( $entry, $field_id ) {
		$value = '';

		if ( ! empty( $entry ) && isset( $entry->metas[ $field_id ] ) ) {
			$value = $entry->metas[ $field_id ];
		} elseif ( isset( $_POST['item_meta'][ $field_id ] ) ) { // WPCS: CSRF ok.
			$value = sanitize_text_field( wp_unslash( $_POST['item_meta'][ $field_id ] ) ); // WPCS: CSRF ok.
		}

		return $value;
	}
}
