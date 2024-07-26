<?php
/**
 * Shortcode controller
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsShortcodeController
 */
class FrmPdfsShortcodeController {

	const ENTRY_PDF_SC = 'frm-pdf';

	/**
	 * Handler for [frm-pdf] shortcode.
	 *
	 * @param array $atts Shortcode atts.
	 */
	public static function frm_pdf_handler( $atts ) {
		/**
		 * Filters the default atts of [frm-pdf] shortcode.
		 *
		 * @param array $atts Shortcode atts.
		 */
		$default_atts = apply_filters(
			'frm_pdfs_default_shortcode_atts',
			array(
				'id'      => '',
				'public'  => '',
				'label'   => __( 'Download PDF', 'formidable-pdfs' ),
				'title'   => '',
				'class'   => '',
				'html_id' => '',
				'target'  => '',
				'include_extras' => '', // If this includes 'admin_only', user can view all fields regardless field Visibility setting.
				'mode'           => FrmPdfsAppController::VIEW_MODE,
				'include_fields' => '',
				'exclude_fields' => '',
				'view'           => '',
				'orientation'    => 'portrait',
			)
		);

		$atts = array_merge( $default_atts, $atts );

		if ( FrmPdfsAppController::FILE_MODE === $atts['mode'] ) {
			$atts['mode'] = FrmPdfsAppController::VIEW_MODE; // Do not support file mode in shortcode.
		}

		if ( '0' === $atts['public'] ) {
			$atts['public'] = '';
		}

		if ( ! $atts['id'] && empty( $atts['view'] ) ) {
			return '';
		}

		$download_url = self::build_download_url( $atts );
		if ( empty( $atts['label'] ) ) {
			$output = esc_url( $download_url );
		} else {
			$output = sprintf(
				'<a href="%1$s" %2$s>%3$s</a>',
				esc_url( $download_url ),
				self::build_link_attrs( $atts ),
				esc_html( $atts['label'] )
			);
		}

		/**
		 * Filters the [frm-pdf] shortcode output.
		 *
		 * @param string $output The shortcode output.
		 * @param array  $atts   The shortcode atts. The `download_url` string is also added.
		 */
		return apply_filters( 'frm_pdfs_shortcode_output', $output, $atts + compact( 'download_url' ) );
	}

	/**
	 * Builds the download URL.
	 *
	 * @param array $shortcode_atts Shortcode atts.
	 * @return string
	 */
	private static function build_download_url( $shortcode_atts ) {
		$url_params           = $shortcode_atts;
		$url_params['action'] = 'frm_entry_pdf';

		$skip_atts = array( 'label', 'title', 'class', 'html_id', 'target', 'public' );
		$url_params = array_diff_key( $url_params, array_flip( $skip_atts ) );

		if ( ! empty( $shortcode_atts['public'] ) ) {
			$url_params['public']      = 1;
			$url_params['access_code'] = FrmPdfsAccessCodeHelper::get_access_code();
		}

		$url = FrmPdfsUrlHelper::get_encoded_url( home_url(), $url_params );

		/**
		 * Filters the download URL of [frm-pdf] shortcode.
		 *
		 * @param string $url  The URL.
		 * @param array  $args {
		 *     The args.
		 *
		 *     @type array $shortcode_atts Shortcode atts.
		 *     @type array $url_params     URL params.
		 * }
		 */
		return apply_filters( 'frm_pdfs_download_url', $url, compact( 'shortcode_atts', 'url_params' ) );
	}

	/**
	 * Builds link attributes.
	 *
	 * @param array $atts Shortcode atts.
	 * @return string
	 */
	private static function build_link_attrs( $atts ) {
		$link_attrs = sprintf(
			'title="%s"',
			esc_attr( ! empty( $atts['title'] ) ? $atts['title'] : $atts['label'] )
		);

		$atts['class'] .= ' frm_no_print';
		if ( ! empty( $atts['class'] ) ) {
			$link_attrs .= sprintf( ' class="%s"', esc_attr( $atts['class'] ) );
		}

		if ( ! empty( $atts['html_id'] ) ) {
			$link_attrs .= sprintf( ' id="%s"', esc_attr( $atts['html_id'] ) );
		}

		if ( ! empty( $atts['target'] ) ) {
			$link_attrs .= sprintf( ' target="%s"', esc_attr( $atts['target'] ) );
		}

		return $link_attrs;
	}

	/**
	 * Replaces PDF link shortcode to add the id attribute.
	 *
	 * @param string $content The content contains the shortcode.
	 * @param object $entry   Entry object.
	 * @return string
	 */
	public static function replace_pdf_link_shortcode( $content, $entry ) {
		preg_match_all( '/' . get_shortcode_regex( array( self::ENTRY_PDF_SC ) ) . '/', $content, $matches );
		if ( empty( $matches[0] ) ) {
			return $content;
		}

		$replace_from = array();
		$replace_to   = array();

		foreach ( $matches[0] as $index => $shortcode ) {
			$atts_str = $matches[3][ $index ];
			$atts     = shortcode_parse_atts( $atts_str );

			// Add id atts.
			if ( ! isset( $atts['id'] ) ) {
				$atts_str .= ' id="' . $entry->id . '"';
			}

			if ( $atts_str !== $matches[3][ $index ] ) {
				$replace_from[] = $shortcode;
				$replace_to[]   = "[{$matches[2][ $index ]}{$atts_str}]";
			}
		}

		if ( $replace_from ) {
			$content = str_replace( $replace_from, $replace_to, $content );
		}

		return $content;
	}

	/**
	 * Handles PDF link request.
	 */
	public static function handle_request() {
		$params = FrmAppHelper::get_param( 'frm_data' );
		if ( ! $params ) {
			return;
		}

		$params = FrmPdfsUrlHelper::decode_url_params( $params );
		if ( ! $params || ! is_array( $params ) || ! isset( $params['action'] ) || 'frm_entry_pdf' !== $params['action'] ) {
			return;
		}

		$entry = self::maybe_get_entry( $params );
		if ( ! FrmPdfsAppHelper::is_entry_table( $params ) && ! $entry ) {
			if ( ! self::guest_user_can_view( $params ) ) {
				wp_die( esc_html__( 'You are not allowed to view this file', 'formidable-pdfs' ), intval( FrmPdfsAppHelper::ERROR_FORBIDDEN ) );
			}

			FrmPdfsAppController::generate_entry_pdf( false, $params );
			return;
		}

		if ( ! $entry ) {
			wp_die( esc_html__( 'Entry does not exist', 'formidable-pdfs' ), intval( FrmPdfsAppHelper::ERROR_NOT_FOUND ) );
		}

		if ( ! self::user_can_view( $entry ) && ! self::guest_user_can_view( $params ) ) {
			wp_die( esc_html__( 'You are not allowed to view this file', 'formidable-pdfs' ), intval( FrmPdfsAppHelper::ERROR_FORBIDDEN ) );
		}

		unset( $params['action'] );

		FrmPdfsAppController::generate_entry_pdf( $entry, $params );
	}

	/**
	 * Check a few parameters to get an entry id. This is used
	 * the generate the file name.
	 *
	 * @since 2.0
	 *
	 * @param array $args The shortcode parameters.
	 * @return object|false
	 */
	private static function maybe_get_entry( $args ) {
		$entry_id = 0;
		if ( ! empty( $args['entry_id'] ) ) {
			$entry_id = $args['entry_id'];
		} elseif ( ! empty( $args['id'] ) ) {
			$entry_id = $args['id'];
		}

		return $entry_id ? FrmEntry::getOne( $entry_id ) : false;
	}

	/**
	 * Checks if current logged-in user can view the PDF.
	 *
	 * @param object $entry Entry object.
	 * @return bool
	 */
	private static function user_can_view( $entry ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( FrmPdfsAppHelper::current_user_can_download_pdf() ) {
			return true;
		}

		return intval( $entry->user_id ) && get_current_user_id() === intval( $entry->user_id );
	}

	/**
	 * Checks if the current logged-out user can view the PDF.
	 *
	 * @param array $params Decoded URL params.
	 * @return bool
	 */
	private static function guest_user_can_view( $params ) {
		if ( empty( $params['public'] ) || empty( $params['access_code'] ) ) {
			return false;
		}

		return FrmPdfsAccessCodeHelper::verify( $params['access_code'] );
	}

	/**
	 * Adds new shortcodes to the shortcodes list.
	 *
	 * @param array $shortcodes Array of shortcodes and their label.
	 * @return array
	 */
	public static function add_to_shortcodes_list( $shortcodes ) {
		$shortcodes[ self::ENTRY_PDF_SC ] = __( 'PDF link', 'formidable-pdfs' );
		return $shortcodes;
	}
}
