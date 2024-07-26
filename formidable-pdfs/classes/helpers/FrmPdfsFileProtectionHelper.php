<?php
/**
 * File protection helper
 *
 * @since 2.0.2
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsFileProtectionHelper
 */
class FrmPdfsFileProtectionHelper {

	/**
	 * Files waiting for updating chmod.
	 *
	 * @var string[]
	 */
	private static $queue = array();

	/**
	 * Files was updated chmod.
	 *
	 * @var string[]
	 */
	private static $updated_files = array();

	/**
	 * Adds a file to queue.
	 *
	 * @param string $file File path.
	 */
	public static function add_to_queue( $file ) {
		self::$queue[] = $file;
	}

	/**
	 * Updates chmod tp public for files in queue, then clear queue.
	 */
	public static function process_queue() {
		foreach ( self::$queue as $file ) {
			if ( FrmProFileField::WRITE_ONLY === FrmProFileField::get_chmod( compact( 'file' ) ) ) {
				self::$updated_files[] = $file;
				FrmProFileField::chmod( $file, 0644 ); // Chmod to 0400 doesn't work in some cases.

				self::maybe_update_htaccess( $file );
			}
		}

		self::$queue = array();
	}

	/**
	 * Maybe update .htaccess file content on some servers like Apache.
	 * We don't need to restore the .htaccess content back.
	 *
	 * @since 2.0.3
	 *
	 * @param string $file Uploaded file path.
	 */
	private static function maybe_update_htaccess( $file ) {
		if ( ! FrmProFileField::server_supports_htaccess() ) {
			return;
		}

		$folder        = dirname( $file );
		$htaccess_file = $folder . '/.htaccess';

		if ( ! file_exists( $htaccess_file ) ) {
			return;
		}

		$upload_dir = trailingslashit( wp_upload_dir()['basedir'] );
		$folder     = str_replace( $upload_dir, '', $folder );

		$content = "Deny from all\r\nRequire local\r\nAllow from all\r\n";
		$create_file = new FrmCreateFile(
			array(
				'folder_name'   => $folder,
				'file_name'     => '.htaccess',
				// translators: .htaccess file path.
				'error_message' => sprintf( __( 'Unable to write to %s to access files for PDFs.', 'formidable-pdfs' ), $htaccess_file ),
			)
		);
		$create_file->create_file( $content );
	}

	/**
	 * Updates chmod back to private for files was updated before.
	 */
	public static function restore_chmod() {
		foreach ( self::$updated_files as $file ) {
			FrmProFileField::set_to_write_only( $file );
		}

		self::$updated_files = array();
	}
}
