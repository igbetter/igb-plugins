<?php
/**
 * Handle the plugin updating.
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\models;

use FrmAddon;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Addon Update class.
 *
 * @since 1.0
 */
class Update extends FrmAddon {

	/**
	 * The current plugin path.
	 *
	 * @var string
	 */
	public $plugin_file;

	/**
	 * This must match the host site.
	 *
	 * @var string
	 */
	public $plugin_name = 'Surveys and Polls';

	/**
	 * ID from host site.
	 *
	 * @var int
	 */
	public $download_id = 28067256;

	/**
	 * Current version number.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Init updater.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->version     = \FrmSurveys\helpers\AppHelper::plugin_version();
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/formidable-surveys.php';
		parent::__construct();
	}

	/**
	 * Load the updater.
	 *
	 * @since 1.0
	 */
	public static function load_hooks() {
		new Update();
	}
}
