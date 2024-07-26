<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmChatUpdate extends FrmAddon {

	/**
	 * @var string $plugin_file
	 */
	public $plugin_file;

	/**
	 * @var string $plugin_name
	 */
	public $plugin_name = 'Formidable Conversational Forms';

	/**
	 * @var int $download_id
	 */
	public $download_id = 28100793;

	/**
	 * @var string $version
	 */
	public $version;

	public function __construct() {
		$this->version     = FrmChatAppHelper::plugin_version();
		$this->plugin_file = FrmChatAppHelper::path() . '/formidable-chat.php';
		parent::__construct();
	}

	/**
	 * @return void
	 */
	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmChatUpdate();
	}
}
