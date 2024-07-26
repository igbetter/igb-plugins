<?php
/**
 * App helper
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsAppHelper
 */
class FrmPdfsAppHelper {

	const ERROR_BAD_REQUEST = 400;

	const ERROR_FORBIDDEN = 403;

	const ERROR_NOT_FOUND = 404;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public static $plug_version = '2.0.4';

	/**
	 * Gets plugin folder name.
	 *
	 * @return string
	 */
	public static function plugin_folder() {
		return basename( self::plugin_path() );
	}

	/**
	 * Gets plugin path.
	 *
	 * @return string
	 */
	public static function plugin_path() {
		return dirname( dirname( dirname( __FILE__ ) ) );
	}

	/**
	 * Gets plugin file path.
	 *
	 * @return string
	 */
	public static function plugin_file() {
		return self::plugin_path() . '/formidable-pdfs.php';
	}

	/**
	 * Gets plugin URL.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url( '', self::plugin_path() . '/formidable-pdfs.php' );
	}

	/**
	 * Gets plugin relative URL.
	 *
	 * @return string
	 */
	public static function relative_plugin_url() {
		return str_replace( array( 'https:', 'http:' ), '', self::plugin_url() );
	}

	/**
	 * Checks if current user can download pdf.
	 *
	 * @return bool
	 */
	public static function current_user_can_download_pdf() {
		return current_user_can( 'frm_view_entries' );
	}

	/**
	 * Increases server limitation for exporting file.
	 *
	 * @see FrmXMLController::csv()
	 */
	public static function increase_export_server_limit() {
		if ( function_exists( 'set_time_limit' ) ) {
			// Remove time limit to execute this function.
			set_time_limit( 0 );
		}

		$mem_limit = str_replace( 'M', '', ini_get( 'memory_limit' ) );
		if ( (int) $mem_limit < 256 ) {
			wp_raise_memory_limit();
		}
	}

	/**
	 * Checks if we can render images in PDF file.
	 *
	 * @return bool
	 */
	public static function can_render_images_in_pdf() {
		return ini_get( 'allow_url_fopen' );
	}

	/**
	 * Gets the array of incompatible error messages.
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public static function get_incompatible_error_messages_arr() {
		$error_messages = array();

		$php_version = '7.1';
		if ( version_compare( phpversion(), $php_version, '<' ) ) {
			$error_messages[] = sprintf(
				// translators: PHP version.
				__( 'Formidable PDFs requires at least PHP %s. Please update your PHP version.', 'formidable-pdfs' ),
				$php_version
			);
		}

		$frm_version = '5.4.2';
		if ( ! class_exists( 'FrmAppHelper', false ) || version_compare( FrmAppHelper::$plug_version, $frm_version, '<' ) ) {
			$error_messages[] = sprintf(
				// translators: Formidable Forms version.
				__( 'Formidable PDFs requires at least Formidable Forms %s.', 'formidable-pdfs' ),
				$frm_version
			);
		}

		if ( ! class_exists( 'FrmProDb', false ) || version_compare( FrmProDb::$plug_version, $frm_version, '<' ) ) {
			$error_messages[] = sprintf(
				// translators: Formidable Forms Pro version.
				__( 'Formidable PDFs requires at least Formidable Forms Pro %s.', 'formidable-pdfs' ),
				$frm_version
			);
		}

		$extensions = get_loaded_extensions();
		$ext_names  = array();
		if ( ! in_array( 'dom', $extensions, true ) ) {
			$ext_names[] = 'PHP DOM';
		}

		if ( ! in_array( 'mbstring', $extensions, true ) ) {
			$ext_names[] = 'PHP MBString';
		}

		if ( ! in_array( 'gd', $extensions, true ) ) {
			$ext_names[] = 'PHP GD';
		}

		if ( $ext_names ) {
			$error_messages[] = sprintf(
				// translators: PHP extensions.
				__( 'Formidable PDFs requires following extensions to be installed: %s', 'formidable-pdfs' ),
				implode( ', ', $ext_names )
			);
		}

		return $error_messages;
	}

	/**
	 * Check if the PDF is the default entry or not.
	 *
	 * @since 2.0
	 * @param array $params The values passed from a shortcode.
	 * @return bool
	 */
	public static function is_entry_table( $params ) {
		return empty( $params['view'] ) && empty( $params['source'] );
	}

	/**
	 * Checks if the PDF is the view.
	 *
	 * @since 2.0
	 *
	 * @param array $params The values passed from a shortcode.
	 * @return bool
	 */
	public static function is_view( $params ) {
		return ! empty( $params['view'] );
	}

	/**
	 * Checks if PDF file is being processing.
	 *
	 * @since 2.0
	 *
	 * @return bool
	 */
	public static function is_pdf() {
		return FrmPdfsAppController::$is_processing;
	}

	/**
	 * Wraps the HTML content and CSS into a full HTML page.
	 *
	 * @since 2.0
	 *
	 * @param string $html HTML content.
	 * @param string $css  CSS content, includes `<style>` tag.
	 * @return string
	 */
	public static function wrap_html( $html, $css ) {
		$full_html = <<<HTML
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		$css
	</head>

	<body>
		$html
	</body>
</html>
HTML;
		return $full_html;
	}

	/**
	 * Gets searches and replaces for <svg> tags in the given HTML.
	 *
	 * @since 2.0.2
	 *
	 * @param array  $searches Searches array.
	 * @param array  $replaces Replaces array.
	 * @param string $html     HTML content.
	 */
	public static function get_svg_searches_and_replaces( &$searches, &$replaces, $html ) {
		// Get all <svg> tags.
		preg_match_all( '/<svg.*>((?!<\/svg>).|\s)*<\/svg>/', $html, $matches, PREG_SET_ORDER );

		if ( ! $matches ) {
			return;
		}

		foreach ( $matches as $match ) {
			// Do not process one SVG multiple times.
			if ( in_array( $match[0], $searches, true ) ) {
				continue;
			}

			$new_svg = $match[0];
			self::process_svg_content( $new_svg );

			$searches[] = $match[0];
			$replaces[] = sprintf(
				'<img src="%s" class="frmsvg" />',
				self::svg_to_base64_img_src( $new_svg )
			);
		}
	}

	/**
	 * Gets searches and replaces for <img> tags contain svg icon in the given HTML.
	 *
	 * @since 2.0.2
	 *
	 * @param array  $searches Searches array.
	 * @param array  $replaces Replaces array.
	 * @param string $html     HTML content.
	 */
	public static function get_img_svg_searches_and_replaces( &$searches, &$replaces, $html ) {
		// Get all <img> with .svg extension.
		preg_match_all( '/\<img.+src\=(?:\"|\')(.+?\.svg)(?:\"|\')(?:.+?)\>/', $html, $matches, PREG_SET_ORDER );

		if ( ! $matches ) {
			return;
		}

		foreach ( $matches as $match ) {
			// Do not process one SVG multiple times.
			if ( in_array( $match[0], $searches, true ) ) {
				continue;
			}

			// $match[0] is the <img> tag, $match[1] is the img src.
			$new_svg = file_get_contents( $match[1] );
			self::process_svg_content( $new_svg );

			$searches[] = $match[0];
			$replaces[] = str_replace( $match[1], self::svg_to_base64_img_src( $new_svg ), $match[0] );
		}
	}

	/**
	 * Processes the SVG content before converting to base64 image.
	 *
	 * @since 2.0.2
	 *
	 * @param string $svg SVG content.
	 */
	private static function process_svg_content( &$svg ) {
		// Add xml tags if missing.
		if ( ! strpos( $svg, 'xmlns=' ) ) {
			$svg = str_replace( '<svg', '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"', $svg );
		}

		// CSS fill:currentColor doesn't work, we must add a fixed color.
		$color = '#000';

		// For rating stars, we'll use a custom color.
		if ( preg_match( '/<title>(star|star_full|star_half<\/title>)/', $svg ) ) {
			$color = '#f0Ad4e';
		}

		// Add fill color to the <svg> tag if is not set.
		if ( ! preg_match( '/<svg[^>]+(fill=")[^>]+>/', $svg ) ) {
			$svg = str_replace( '<svg', '<svg fill="' . $color . '"', $svg );
		} else {
			// Replace fill="none" in the <svg> tag.
			$svg = preg_replace( '/(<svg[^>]+)(fill="none")([^>]+>)/', '${1}fill="' . $color . '"${3}', $svg, 1 );
		}
	}

	/**
	 * Converts SVG content to base64 image src.
	 *
	 * @since 2.0.2
	 *
	 * @param string $svg SVG content.
	 * @return string
	 */
	private static function svg_to_base64_img_src( $svg ) {
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Maybe print pagination CSS.
	 *
	 * @since 2.0.2
	 *
	 * @param array $args See {@see FrmPdfsAppController::generate_entry_pdf()} for more details.
	 */
	public static function print_pagination_css( $args ) {
		if ( empty( $args['show_pagination'] ) ) {
			return;
		}

		echo '
			.frm_pdfs_pagination {
				position: fixed;
				left: 0;
				right: 0;
				bottom: 0;
				width: 100%;
				text-align: center;
			}

			.frm_pdfs_page_number:before {
				content: counter(page);
			}
		';
	}

	/**
	 * Gets pagination HTML.
	 *
	 * @since 2.0.2
	 *
	 * @return string
	 */
	public static function get_pagination_html() {
		$pagination_text = sprintf(
			// Translators: %1$s: page number, %2$s: total pages.
			__( 'Page %1$s of %2$s', 'formidable-pdfs' ),
			'<span class="frm_pdfs_page_number"></span>',
			'<span class="frm_pdfs_total_pages">' . self::get_pagination_total_pages_placeholder() . '</span>'
		);

		return '<div class="frm_pdfs_pagination">' . $pagination_text . '</div>';
	}

	/**
	 * Gets pagination total pages placeholder string. This will be replaced with the actual total pages when rendering.
	 *
	 * @since 2.0.2
	 *
	 * @return string
	 */
	public static function get_pagination_total_pages_placeholder() {
		return '^^'; // Don't use a long string, it will take space even after replaced with the actual number.
	}

	/**
	 * Checks if the given URI should be encoded by Dompdf\Helpers::encodeURI().
	 *
	 * @since 2.0.4
	 *
	 * @param string $uri Resource URI.
	 * @return bool
	 */
	public static function dompdf_should_encode_uri( $uri ) {
		$should_encode = true;

		// Fix wrong QRcode after rendering PDF.
		if ( 0 === strpos( $uri, 'https://api.qrserver.com/v1/create-qr-code/?' ) ) {
			$should_encode = false;
		}

		/**
		 * Skips encoding URL by Dompdf\Helpers::encodeURI(), which results a wrong URI.
		 *
		 * @since 2.0.4
		 *
		 * @param bool   $should_encode Set to `false` if you want to skip encoding.
		 * @param string $uri           The URI.
		 */
		return apply_filters( 'frm_pdfs_dompdf_should_encode_uri', $should_encode, $uri );
	}
}
