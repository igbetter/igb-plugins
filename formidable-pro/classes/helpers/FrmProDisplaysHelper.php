<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProDisplaysHelper {

	/**
	 * @param string $content
	 * @param mixed  $form_id
	 * @return array
	 */
	public static function get_shortcodes( $content, $form_id ) {
		if ( ! $form_id || strpos( $content, '[' ) === false ) {
			// don't continue if there are no shortcodes to check
			return array( array() );
		}

		$form_id            = (int) $form_id;
		$field_ids_and_keys = self::get_field_ids_and_keys_for_form( $form_id );
		$tagregexp          = array( 'deletelink', 'detaillink', 'evenodd', 'get', 'entry_count', 'event_date', 'is[-|_]draft' );

		if ( count( $field_ids_and_keys ) > 200 ) {
			$field_ids_and_keys = FrmProFieldsHelper::filter_keys_for_regex( $content, $field_ids_and_keys );
		}

		$tagregexp  = array_merge( $tagregexp, $field_ids_and_keys );
		$tagregexp  = implode( '|', $tagregexp ) . '|';
		$tagregexp .= FrmFieldsHelper::allowed_shortcodes();

		self::maybe_increase_regex_limit();

		preg_match_all( "/\[(if |foreach )?($tagregexp)\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?/s", $content, $matches, PREG_PATTERN_ORDER );

		$matches[0] = self::organize_and_filter_shortcodes( $matches[0] );

		return $matches;
	}

	/**
	 * Get all field IDs and keys for a form and its children forms for regex.
	 *
	 * @since 5.5.4 This was moved from get_shortcodes and field IDs were added because checking for \d would catch false positives.
	 *
	 * @param int $form_id
	 * @return array
	 */
	private static function get_field_ids_and_keys_for_form( $form_id ) {
		$form_ids      = self::linked_form_ids( $form_id );
		$field_query   = array(
			'form_id' => $form_ids,
			'or'      => 1,
		);
		$field_results = FrmDb::get_results( 'frm_fields', $field_query, 'id, field_key' );

		$field_ids_and_keys = array();
		foreach ( $field_results as $result ) {
			$field_ids_and_keys[] = $result->id;
			$field_ids_and_keys[] = $result->field_key;
		}

		return $field_ids_and_keys;
	}

	/**
	 * Get the ids of any child forms (repeat or embedded).
	 *
	 * @since 3.0
	 *
	 * @param int|string $form_id
	 * @return array
	 */
	private static function linked_form_ids( $form_id ) {
		$linked_field_query = array(
			'form_id' => $form_id,
			'type'    => array( 'divider', 'form' ),
		);
		$fields             = FrmDb::get_col( 'frm_fields', $linked_field_query, 'field_options' );

		$form_ids = array( $form_id );
		foreach ( $fields as $field_options ) {
			FrmProAppHelper::unserialize_or_decode( $field_options );
			if ( ! empty( $field_options['form_select'] ) ) {
				$form_ids[] = $field_options['form_select'];
			}
			unset( $field_options );
		}

		return $form_ids;
	}

	/**
	 * Make sure the backtrack limit is as least at the default
	 *
	 * @since 3.0
	 *
	 * @return void
	 */
	private static function maybe_increase_regex_limit() {
		$backtrack_limit = ini_get( 'pcre.backtrack_limit' );
		if ( $backtrack_limit < 1000000 ) {
			ini_set( 'pcre.backtrack_limit', 1000000 );
		}
	}

	/**
	 * Put conditionals and foreach first.
	 * Remove duplicate conditional and foreach tags.
	 *
	 * @since 2.01.03
	 *
	 * @param array $shortcodes
	 * @return array $shortcodes
	 */
	private static function organize_and_filter_shortcodes( $shortcodes ) {
		$move_up = array();

		foreach ( $shortcodes as $short_key => $tag ) {
			$conditional = preg_match( '/^\[if/s', $shortcodes[ $short_key ] ) ? true : false;
			$foreach     = preg_match( '/^\[foreach/s', $shortcodes[ $short_key ] ) ? true : false;

			if ( $conditional || $foreach ) {
				if ( ! in_array( $tag, $move_up, true ) ) {
					$move_up[ $short_key ] = $tag;
				}
				unset( $shortcodes[ $short_key ] );
			}
		}

		if ( $move_up ) {
			$shortcodes = $move_up + $shortcodes;
		}

		return $shortcodes;
	}

	/**
	 * @deprecated x.x
	 */
	public static function prepare_duplicate_view( &$post ) {
		_deprecated_function( __METHOD__, '6.11.1', 'FrmViewsDisplaysHelper::prepare_duplicate_view' );
		if ( is_callable( 'FrmViewsDisplaysHelper::prepare_duplicate_view' ) ) {
			FrmViewsDisplaysHelper::prepare_duplicate_view( $post );
		}
	}

	/**
	 * @deprecated x.x
	 *
	 * @return array
	 */
	public static function get_show_counts() {
		_deprecated_function( __METHOD__, '6.11.1', 'FrmViewsDisplaysHelper::get_show_counts' );
		if ( is_callable( 'FrmViewsDisplaysHelper::get_show_counts' ) ) {
			return FrmViewsDisplaysHelper::get_show_counts();
		}
		return array();
	}

	/**
	 * @param false|string $include_key if false all keys are included.
	 * @return array
	 */
	public static function get_frm_options_for_views( $include_key = false ) {
		if ( is_callable( 'FrmViewsDisplaysHelper::get_frm_options_for_views' ) ) {
			return FrmViewsDisplaysHelper::get_frm_options_for_views( $include_key );
		}
		return array();
	}
}
