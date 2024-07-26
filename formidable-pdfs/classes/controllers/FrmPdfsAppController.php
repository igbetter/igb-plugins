<?php
/**
 * App controller
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsAppController
 */
class FrmPdfsAppController {

	const FILE_MODE = 'file';

	const DOWNLOAD_MODE = 'download';

	const VIEW_MODE = 'view';

	/**
	 * Flag to check if PDF file is being processed.
	 *
	 * @since 2.0
	 *
	 * @var bool
	 */
	public static $is_processing = false;

	/**
	 * Shows the incompatible notice.
	 */
	public static function show_incompatible_notice() {
		$error_message = self::get_incompatible_error_message();

		if ( ! $error_message ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<?php echo FrmAppHelper::kses( $error_message, array( 'a', 'br', 'span', 'p' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
	}

	/**
	 * Gets incompatible error message.
	 *
	 * @return string
	 */
	public static function get_incompatible_error_message() {
		$messages = FrmPdfsAppHelper::get_incompatible_error_messages_arr();
		if ( ! $messages ) {
			return '';
		}

		return '<p>' . implode( '</p><p>', $messages ) . '</p>';
	}

	/**
	 * Adds incompatible notice messages to the Frm message list.
	 *
	 * @since 2.0
	 *
	 * @param array $messages Message list.
	 * @return array
	 */
	public static function add_incompatible_notice_to_message_list( $messages ) {
		return FrmPdfsAppHelper::get_incompatible_error_messages_arr() + $messages;
	}

	/**
	 * Initializes plugin translation.
	 */
	public static function init_translation() {
		load_plugin_textdomain( 'formidable-pdfs', false, FrmPdfsAppHelper::plugin_folder() . '/languages/' );
	}

	/**
	 * Includes addon updater.
	 */
	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			FrmPdfsUpdate::load_hooks();
		}
	}

	/**
	 * Generates PDF file.
	 *
	 * @param object|int|false $entry Entry object, or ID, or `false` (in case the PDF isn't the entry table).
	 * @param array            $args  Shortcode attributes.
	 * @return string|false|void Return the file path if `$mode` is set to `file`. Return `false` if entry doesn't exist.
	 */
	public static function generate_entry_pdf( $entry, $args = array() ) {
		$args = wp_parse_args( $args, self::get_default_entry_pdf_args() );

		FrmPdfsAppHelper::increase_export_server_limit();

		$html = self::generate_pdf_html( $entry, $args );

		if ( false === $html ) {
			return false;
		}

		$html_to_pdf = new FrmPdfsHtmlToPdf();
		$html_to_pdf->load_html( $html );
		$html_to_pdf->render( $args );

		$file_name = self::get_entry_pdf_file_name( $entry, $args );

		if ( self::DOWNLOAD_MODE === $args['mode'] || self::VIEW_MODE === $args['mode'] ) {
			$html_to_pdf->stream( $file_name, array( 'Attachment' => self::DOWNLOAD_MODE === $args['mode'] ) );
			die();
		}

		$file_path = get_temp_dir() . $file_name;
		$output    = $html_to_pdf->output();

		$written_bytes = file_put_contents( $file_path, $output );
		if ( false === $written_bytes ) {
			return false;
		}
		return $file_path;
	}

	/**
	 * Generates PDF html.
	 *
	 * @param object|int|false $entry See {@see FrmPdfsAppController::generate_entry_pdf()}.
	 * @param array            $args  See {@see FrmPdfsAppController::generate_entry_pdf()}.
	 * @return string|false Return `false` if entry doesn't exist.
	 */
	private static function generate_pdf_html( $entry, $args ) {
		self::$is_processing = true;

		if ( FrmPdfsAppHelper::is_entry_table( $args ) ) {
			$html = self::get_entry_table( $entry, $args );
		} elseif ( ! empty( $args['view'] ) ) {
			$html = self::get_view_shortcode( $args );
		} else {
			$html = self::get_any_shortcode( $args );
		}

		self::$is_processing = false;

		if ( ! $html ) {
			return false;
		}

		// This replaces private image URLs with actual image URLs.
		self::replace_protected_images_and_add_to_chmod_queue( $html );

		// This replaces internal image URLS with paths, and remove external images if can't render them.
		self::replace_internal_and_external_images( $html );

		// This replaces <svg> and <img src=".svg"> with base64 images.
		self::replace_svgs( $html );

		self::maybe_add_pagination( $html, $args );

		/**
		 * Filters the HTML content of PDF entry export file.
		 *
		 * @param string $content The HTML content of PDF entry export file.
		 * @param array  $args    {
		 *     The args. See {@see FrmPdfsAppController::get_default_entry_pdf_args()}. The following items are also included:
		 *
		 *     @type string   $id          Entry id.
		 *     @type stdClass $entry       Entry object.
		 *     @type string   $orientation Orientation of the downloaded PDF 'portrait' or 'landscape'.
		 * }
		 */
		$html = apply_filters( 'frm_pdfs_export_content', $html, $args + compact( 'entry' ) );

		// Wrap the content with UTF-8 charset <meta> before processing the grid containers to fix the arabic characters issue.
		$css_placeholder = '<!--FRMPDF CSS PLACEHOLDER-->';
		$html = FrmPdfsAppHelper::wrap_html( $html, $css_placeholder );
		self::maybe_remove_grids_contain_full_width_columns( $html );

		// Add the CSS.
		$html = str_replace( $css_placeholder, self::get_pdf_css( $entry, $args ), $html );

		return $html;
	}

	/**
	 * Get the entry in a table.
	 *
	 * @param object|int $entry The entry used for this PDF.
	 * @param array      $args  The parameters for [frm-show-entry].
	 * @return string
	 */
	private static function get_entry_table( $entry, $args ) {
		FrmEntry::maybe_get_entry( $entry );
		if ( ! $entry ) {
			return false;
		}

		$fields = self::get_fields_for_export( $entry, $args );

		$args      = $args + compact( 'entry', 'fields' );
		$show_args = self::set_entry_args( $args );

		ob_start();
		include FrmPdfsAppHelper::plugin_path() . '/classes/views/pdf-entry.php';
		return ob_get_clean();
	}

	/**
	 * Prepare the shortcode parameters to use in
	 * FrmProEntriesController::show_entry_shortcode()
	 *
	 * @param array $show_args The parameters for [frm-show-entry].
	 * @return array
	 */
	private static function set_entry_args( $show_args ) {
		$show_args['format']       = 'pdf';
		$show_args['inline_style'] = false;
		$show_args['show_image']   = true;
		$show_args['size']         = 'thumbnail';
		$show_args['add_link']     = true;

		if ( empty( $show_args['include_extras'] ) ) {
			$show_args['include_extras'] = 'page, section';
		}

		/**
		 * Filters the args of FrmProEntriesController::show_entry_shortcode() in the content of PDF entry export file.
		 *
		 * @param array $show_args {
		 *     The args. See {@see FrmPdfsAppController::get_default_entry_pdf_args()}. The following items are also included:
		 *
		 *     @type object   $entry  Entry object.
		 *     @type object[] $fields Array of field objects.
		 * }
		 */
		return apply_filters( 'frm_pdfs_show_args', $show_args );
	}

	/**
	 * Prep View shortcode parameters and generate the output.
	 *
	 * @param array $args The parameters for the shortcode.
	 * @return string
	 */
	private static function get_view_shortcode( $args ) {
		$args['source'] = 'display-frm-data';
		if ( ! empty( $args['id'] ) && is_callable( array( 'FrmViewsDisplay', 'getOne' ) ) ) {
			$view = FrmViewsDisplay::getOne( $args['view'] );
			if ( $view ) {
				$args[ $view->frm_param ] = $args['id'];
			}
		}
		$args['id'] = $args['view'];
		return self::get_any_shortcode( $args );
	}

	/**
	 * Use the "shortcode" parameter to show anything.
	 *
	 * @param array $args The parameters for the shortcode.
	 * @return string
	 */
	private static function get_any_shortcode( $args ) {
		$shortcode_atts = '';
		unset( $args['action'] );
		foreach ( $args as $name => $val ) {
			$shortcode_atts .= ' ' . esc_attr( $name ) . '="' . esc_attr( $val ) . '"';
		}
		return do_shortcode( '[' . esc_attr( $args['source'] ) . $shortcode_atts . ']' );
	}

	/**
	 * Replaces internal image URLs with folder paths, and removes external images if can't render them.
	 *
	 * @since 2.0.4
	 *
	 * @param string $html The generated HTML for the PDF.
	 */
	private static function replace_internal_and_external_images( &$html ) {
		$can_render_images = FrmPdfsAppHelper::can_render_images_in_pdf();
		$upload_dir        = wp_upload_dir();
		$valid_upload_dir  = ! empty( $upload_dir['basedir'] ) && ! empty( $upload_dir['baseurl'] );
		if ( $can_render_images && ! $valid_upload_dir ) {
			// If can render external images, and upload dir data is invalid, do nothing.
			return;
		}

		// Get all images.
		$pattern = '/<img\s+[^>]*src=("|\')(?<url>[^"\']+)\1[^>]*>/i';
		preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER );

		$searches = array();
		$replaces = array();

		foreach ( $matches as $match ) {
			$img         = $match[0];
			$img_url     = $match['url'];
			$is_internal = $valid_upload_dir && 0 === strpos( $img_url, $upload_dir['baseurl'] );

			if ( $is_internal ) {
				// Replace internal image URL with the folder path.
				$searches[]  = $img;
				$replaces[] = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $img );
			} elseif ( ! $can_render_images && ! $is_internal ) {
				// Remove external image if can't render it.
				$searches[]  = $img;
				$replaces[] = '';
			}
		}

		if ( $searches && $replaces ) {
			$html = str_replace( $searches, $replaces, $html );
		}
	}

	/**
	 * Maybe adds pagination HTML.
	 *
	 * @since 2.0.2
	 *
	 * @param string $html The generated HTML for the PDF.
	 * @param array  $args See {@see FrmPdfsAppController::generate_entry_pdf()} for more details.
	 */
	private static function maybe_add_pagination( &$html, $args ) {
		if ( ! empty( $args['show_pagination'] ) ) {
			$html = FrmPdfsAppHelper::get_pagination_html() . $html;
		}
	}

	/**
	 * Maybe remove grids contain full width columns only to fix the empty page with long content.
	 *
	 * @since 2.0.2
	 *
	 * @param string $html HTML content.
	 */
	private static function maybe_remove_grids_contain_full_width_columns( &$html ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return;
		}

		// Don't output warning if there is invalid HTML.
		$set_err = libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadHTML( $html, LIBXML_NOWARNING );
		$xpath = new DOMXPath( $dom );

		$grid_containers = $xpath->query( '//div[contains(@class, "frm_grid_container")]' );
		foreach ( $grid_containers as $grid_container ) {
			if ( self::contains_full_width_columns_only( $grid_container ) ) {
				$class = $grid_container->getAttribute( 'class' );
				$grid_container->setAttribute( 'class', $class . ' frm_grid_container_no_table' );
			}
		}

		libxml_use_internal_errors( $set_err );
		$html = $dom->saveHTML();
	}

	/**
	 * Checks if the given HTML element contains full width columns only.
	 *
	 * @since 2.0.2
	 *
	 * @param DOMElement $element DOM element.
	 * @return bool
	 */
	private static function contains_full_width_columns_only( $element ) {
		if ( self::get_child_element_count( $element ) < 2 ) {
			return true;
		}

		foreach ( $element->childNodes as $child_node ) {
			if ( ! $child_node instanceof DOMElement ) {
				continue;
			}

			$class = $child_node->getAttribute( 'class' );
			// Return false if there is at least 1 column isn't full width.
			if ( preg_match( '/frm\d{1,2}/', $class ) && false === strpos( $class, 'frm12' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Gets child element count.
	 *
	 * @since 2.0.3
	 *
	 * @param DOMElement $element DOM element.
	 * @return int
	 */
	private static function get_child_element_count( $element ) {
		// This property is added since PHP 8.0.
		if ( property_exists( $element, 'childElementCount' ) ) {
			return $element->childElementCount;
		}

		$count = 0;
		foreach ( $element->childNodes as $child_node ) {
			if ( $child_node instanceof DOMElement ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Replaces protected image URLs with the actual URLs.
	 *
	 * @since 2.0.2
	 *
	 * @param string $html The generated HTML for the PDF.
	 *
	 * @return void
	 */
	private static function replace_protected_images_and_add_to_chmod_queue( &$html ) {
		if ( ! is_callable( array( 'FrmProFileField', 'get_file_payload' ) ) ) {
			return;
		}

		// Get all image src.
		preg_match_all( '/<img.+?src="([^"]*)".*?\/?>/', $html, $matches, PREG_SET_ORDER );

		$searches = array();
		$replaces = array();

		foreach ( $matches as $match ) {
			$payload = FrmProFileField::get_file_payload( $match[1] );

			// Only process private file with base64 URL.
			if ( ! $payload ) {
				continue;
			}

			$file_data = FrmProFileField::get_download_filepath( $payload );
			if ( empty( $file_data['path'] ) ) {
				continue;
			}

			if ( ! FrmProFileField::folder_is_protected( $file_data['form_id'] ) ) {
				continue;
			}

			$searches[] = $match[1];
			$replaces[] = self::upload_path_to_url( $file_data['path'] );

			// Add to queue to update chmod later.
			FrmPdfsFileProtectionHelper::add_to_queue( $file_data['path'] );
		}

		if ( $searches ) {
			$html = str_replace( $searches, $replaces, $html );
		}
	}

	/**
	 * Gets uploaded file URL from file path.
	 *
	 * @since 2.0.2
	 *
	 * @param string $file_path File path.
	 * @return string
	 */
	private static function upload_path_to_url( $file_path ) {
		$upload_dir = wp_upload_dir();
		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path );
	}

	/**
	 * Replaces SVGs with base64 images.
	 *
	 * @since 2.0.2
	 *
	 * @param string $html PDF HTML.
	 */
	private static function replace_svgs( &$html ) {
		$searches = array();
		$replaces = array();

		FrmPdfsAppHelper::get_svg_searches_and_replaces( $searches, $replaces, $html );
		FrmPdfsAppHelper::get_img_svg_searches_and_replaces( $searches, $replaces, $html );

		$html = str_replace( $searches, $replaces, $html );
	}

	/**
	 * Allow the signature user_html=0 parameter to prevent signature images.
	 *
	 * @param array $defaults The default params to generate PDF.
	 * @return array
	 */
	public static function show_entry_defaults( $defaults ) {
		$defaults['use_html'] = 0;
		return $defaults;
	}

	/**
	 * Gets default entry PDF export args.
	 *
	 * @return array
	 */
	private static function get_default_entry_pdf_args() {
		/*
		 * Default is `file`, the PDF content will be written into a file.
		 * Set to `download` if you want to prompt the download dialog.
		 * Set to `view` to view the file on the current page.
		 */
		$mode = self::FILE_MODE;
		return array(
			'mode'           => $mode,
			'exclude_fields' => '', // Comma separated IDs of exclude fields.
			'include_extras' => '',
		);
	}

	/**
	 * Gets fields for exporting.
	 *
	 * @param object $entry Entry object.
	 * @param array  $args  Args.
	 * @return array
	 */
	private static function get_fields_for_export( $entry, $args ) {
		$fields = FrmField::get_all_for_form( $entry->form_id );
		self::remove_invisible_fields( $args, $fields );

		/**
		 * Filters the fields for PDF entry exporting.
		 *
		 * @param object[] $fields Array of field object.
		 * @param array    $args   {
		 *     The args.
		 *
		 *     @type object $entry Entry object.
		 * }
		 */
		$fields = apply_filters( 'frm_pdfs_fields_for_export', $fields, compact( 'entry' ) );

		return $fields;
	}

	/**
	 * Removes any fields hidden with visibility setting.
	 *
	 * @param array $args Args.
	 * @param array $fields All the fields to display.
	 * @return void
	 */
	private static function remove_invisible_fields( $args, &$fields ) {
		$include_invisible = ! empty( $args['include_extras'] ) && strpos( $args['include_extras'], 'admin_only' ) !== false;
		if ( $include_invisible ) {
			return;
		}

		foreach ( $fields as $index => $field ) {
			if ( ! FrmProFieldsHelper::is_field_visible_to_user( $field ) ) {
				unset( $fields[ $index ] );
			}
			unset( $field );
		}
	}
	/**
	 * Gets entry pdf export file name.
	 *
	 * @param object|int|false $entry See {@see FrmPdfsAppController::generate_entry_pdf()}.
	 * @param array            $args  The shortcode attributes.
	 * @return string
	 */
	private static function get_entry_pdf_file_name( $entry, $args ) {
		$file_name = empty( $args['filename'] ) ? '[form_name]-[date format="Y-m-d"]-[key]' : $args['filename'];
		$file_name = str_replace( array( '{', '}' ), array( '[', ']' ), $file_name );
		FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $file_name );

		if ( $entry && strpos( $file_name, '[' ) !== false ) {
			$form      = FrmForm::getOne( $entry->form_id );
			$file_name = self::maybe_replace_form_name_shortcodes( $file_name, $form );
			$file_name = apply_filters( 'frm_content', $file_name, $form, $entry );

			$args['form']      = $form;
			$args['form_name'] = $form->name; // For backward compatibility.
		} else {
			// Clear as fallback.
			$file_name = str_replace( '[form_name]', '', $file_name );
			$file_name = str_replace( '[key]', '', $file_name );
		}

		if ( empty( $file_name ) ) {
			$file_name = gmdate( 'Y-m-d' );
		}
		$file_name = sanitize_title( $file_name ) . '.pdf';

		if ( empty( $args['filename'] ) ) {
			$file_name = 'frm-' . $file_name;
		}

		$args['entry'] = $entry;

		/**
		 * Filters the PDF entry export file name.
		 *
		 * @param string $file_name The file name.
		 * @param array  $args      {
		 *     The shortcode attributes and following values:
		 *
		 *     @type object $entry Entry object.
		 *     @type object $form  Form object. You should check it exists before using.
		 * }
		 */
		return apply_filters( 'frm_pdfs_export_file_name', $file_name, $args );
	}

	/**
	 * This only needs to be here temporarily.
	 * Remove it around 10-2022.
	 *
	 * @param string              $string The file name.
	 * @param stdClass|string|int $form   The current form used for the PDF.
	 * @return string
	 */
	private static function maybe_replace_form_name_shortcodes( $string, $form ) {
		if ( ! is_callable( 'FrmFormsController::replace_form_name_shortcodes' ) ) {
			return $string;
		}
		return FrmFormsController::replace_form_name_shortcodes( $string, $form );
	}

	/**
	 * Deletes a file.
	 *
	 * @param string $file_path File path.
	 */
	public static function delete_file( $file_path ) {
		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}
	}

	/**
	 * Gets CSS for the pdf file.
	 *
	 * @param object $entry Entry object.
	 * @param array  $args  The shortcode parameters.
	 * @return string CSS content, includes `<style>` tag.
	 */
	private static function get_pdf_css( $entry, $args ) {
		ob_start();

		$is_entry_table = FrmPdfsAppHelper::is_entry_table( $args );

		$frm_style = new FrmStyle( 'default' );
		$style     = $frm_style->get_one();
		$defaults  = FrmStylesHelper::get_settings_for_output( $style );

		if ( ! $is_entry_table ) {
			include FrmAppHelper::plugin_path() . '/css/custom_theme.css.php';
		}

		include FrmPdfsAppHelper::plugin_path() . '/css/pdf.css.php';

		if ( $is_entry_table ) {
			include FrmPdfsAppHelper::plugin_path() . '/css/pdf-entry-table.css.php';
		}

		$css = ob_get_clean();

		$args['entry'] = $entry;

		/**
		 * Filters the CSS of PDF entry export.
		 *
		 * @since 2.0.4 The `$args` include all shortcode attributes.
		 *
		 * @param string $css  CSS code. This doesn't include style tag.
		 * @param array  $args The shortcode attributes, with `entry` object is added.
		 */
		$css = apply_filters( 'frm_pdfs_css', $css, $args );

		// Remove a bit of css bulk.
		$css = preg_replace( '/@[a-z-]*keyframes\b[^{]*({(?>[^{}]++|(?1))*})/', '', $css );

		// Replace relative paths with absolute paths for fonts.
		$css = str_replace( 'url(\'../fonts/s11-fp.', 'url(\'' . FrmAppHelper::plugin_url() . '/fonts/s11-fp.', $css );

		// The background-color for <tr> doesn't work, so we add background-color to <td>.
		$css = str_replace( '.frm-alt-table tr:nth-child(even) {', '.frm-alt-table tr:nth-child(even), .frm-alt-table tr:nth-child(even) td {', $css );

		return '<style type="text/css">' . $css . '</style>';
	}

	/**
	 * Changes entry formatter class.
	 *
	 * @param string $formatter_class Entry formatter class name.
	 * @param array  $atts            The attributes. See {@see FrmEntriesController::show_entry_shortcode()}.
	 * @return string
	 */
	public static function entry_formatter_class( $formatter_class, $atts ) {
		if ( isset( $atts['format'] ) && 'pdf' === $atts['format'] ) {
			return 'FrmPdfsEntryFormatter';
		}
		return $formatter_class;
	}
}
