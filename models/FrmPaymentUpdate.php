<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmPaymentUpdate extends FrmAddon {
	public $plugin_file;
	public $plugin_name = 'PayPal Standard';
	public $download_id = 163257;
	public $version = '3.07';

	public function __construct() {
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/formidable-paypal.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmPaymentUpdate();
	}
}
