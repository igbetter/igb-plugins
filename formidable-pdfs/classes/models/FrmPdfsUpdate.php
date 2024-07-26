<?php
/**
 * Addon update class
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsUpdate
 */
class FrmPdfsUpdate extends FrmAddon {

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	public $plugin_file;

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public $plugin_name = 'PDFs';

	/**
	 * Download ID.
	 *
	 * @var int
	 */
	public $download_id = 28136428;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->plugin_file = FrmPdfsAppHelper::plugin_file();
		$this->version     = FrmPdfsAppHelper::$plug_version;
		parent::__construct();
	}
}
