<?php
/**
 * Setup functions on activate & deactivate events.
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/base.php';

class wact_Setup extends wact_Base {
	/**
	 * Specify all codes required for plugin activation here.
	 */
	public function activate() {
		$this->debug('[WooCommerce Auto Calculate Total] Activate');
	}

	/**
	 * Specify all codes required for plugin deactivation here.
	 */
	public function deactivate() {
		$this->debug('[WooCommerce Auto Calculate Total] Deactivate');
	}
}
