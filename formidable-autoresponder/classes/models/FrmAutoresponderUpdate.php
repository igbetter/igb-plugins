<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Handles the plugin autoupdates.
 */
class FrmAutoresponderUpdate extends FrmAddon {
	public $plugin_file;
	public $plugin_name = 'Form Action Automation';
	public $download_id = 326042;
	public $version;

	public function __construct() {
		$this->version     = FrmAutoresponderHelper::plugin_version();
		$this->plugin_file = dirname( dirname( dirname( __FILE__ ) ) ) . '/formidable-autoresponder.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmAutoresponderUpdate();
	}
}
