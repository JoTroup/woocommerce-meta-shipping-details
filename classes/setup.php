<?php
/**
 * Setup functions for plugin activation and deactivation.
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/base.php';

class {plugin_prefix}_Setup extends {plugin_prefix}_Base {
	/**
	 * Code to execute on plugin activation.
	 */
	public function activate() {
		$this->debug('[{Plugin Name}] Activate');
	}

	/**
	 * Code to execute on plugin deactivation.
	 */
	public function deactivate() {
		$this->debug('[{Plugin Name}] Deactivate');
	}
}
