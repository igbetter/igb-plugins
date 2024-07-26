<?php
/**
 * Add and remove items from the log.
 */
class FrmAutoresponderLog {

	public $action;

	public function __construct( $atts = array() ) {
		foreach ( $atts as $k => $value ) {
			$this->{$k} = $value;
		}
	}

	/**
	 * Print a message out to a debug file.  This is useful for debugging to make sure that the emails are getting
	 * triggered.
	 *
	 * @param string $message - what to debug.
	 *
	 * @return void
	 */
	public function add( $message ) {
		$autoresponder = FrmAutoresponder::get_autoresponder( $this->action );

		if ( $autoresponder && ! empty( $autoresponder['debug'] ) ) {
			$file = $this->_get_log_file_name();
			$time = function_exists( 'current_time' ) ? current_time( '[H:i:s] ' ) : gmdate( '[H:i:s] ' );
			@file_put_contents( $file, $time . $message . "\n", FILE_APPEND );
		}
	}

	public function get_content( $url ) {
		$root = $this->_get_log_file_root();
		$file = $root . $url;
		if ( file_exists( $file ) ) {
			$log = file_get_contents( $file );

			$log = preg_replace( '/entry #([0-9]+)/', '<a href="' . esc_url( admin_url( 'admin.php?page=formidable-entries&frm_action=show&id=' ) . '$1' ) . '">$0</a>', $log );
		} else {
			$log = __( 'Nothing to see here', 'formidable-autoresponder' );
		}

		include( FrmAutoresponderHelper::plugin_path( 'classes/views/log.php' ) );
	}

	public function get_urls() {
		$dir = $this->_get_log_file_root();
		$files = glob( $dir . $this->action->ID . '-*' );

		$nonce = wp_create_nonce( 'frm_ajax' );
		$urls = array();
		foreach ( $files as $file ) {
			$url = 'admin-ajax.php?action=formidable_autoresponder_logview&nonce=' . $nonce;
			$url = admin_url( $url . '&log=' . basename( $file ) );
			$urls[ $url ] = filemtime( $file );
		}

		// Sort by date descending.
		arsort( $urls );
		$urls = array_keys( $urls );

		return ( $urls ) ? $urls : array();
	}

	/**
	 * Actually delete the log file
	 *
	 * @param string $url The url is of the X-slug-YYYY-MM-DD.log, where X is the action id,
	 *                    slug is the slug of the action and YYYY-MM-DD is the date of the log file to delete.
	 *
	 * @return void
	 */
	public function delete( $url ) {
		$root = $this->_get_log_file_root();
		$filename = $root . $url;
		if ( file_exists( $filename ) && trailingslashit( dirname( realpath( $filename ) ) ) == $root ) {
			@unlink( $filename );
		}
	}

	/**
	 * For the given action, return a log file name, which ( depending on $base ) is either a full path or a url to the
	 * log file.
	 *
	 * @param string $base Either basedir or baseurl.
	 *
	 * @return string either a full path or a url
	 */
	private function _get_log_file_name( $base = 'basedir' ) {
		$root = $this->_get_log_file_root( $base );
		$filename = $this->action->ID . '-' . sanitize_title( $this->action->post_title ) . '-' . gmdate( 'Y-m-d' ) . '.log';
		if ( 'basedir' === $base ) {
			wp_mkdir_p( $root );
		}
		return $root . $filename;
	}

	/**
	 * Get the root of the autoresponder log directory, either as a path or a url.  It's
	 * wp-content/uploads/formidable-autoresponder/logs.
	 *
	 * @param string $base Either basedir or baseurl.
	 *
	 * @return string either a full path or a url
	 */
	private function _get_log_file_root( $base = 'basedir' ) {
		$uploads = wp_upload_dir();
		$root = trailingslashit( $uploads[ $base ] );
		$root .= 'formidable-autoresponder/logs/';
		return apply_filters( 'formidable_autoresponder_logroot', $root );
	}
}
