<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsUpdate extends FrmAddon {

	public $plugin_file;
	public $plugin_name = 'Visual Views';
	public $download_id = 28058856;

	public function __construct() {
		$this->version     = FrmViewsAppHelper::plugin_version();
		$this->plugin_file = dirname( __DIR__ ) . '/formidable-views.php';
		parent::__construct();
	}

	public static function load_hooks() {
		new FrmViewsUpdate();
	}
}
