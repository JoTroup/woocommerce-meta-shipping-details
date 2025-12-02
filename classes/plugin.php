<?php
/**
 * The core plugin class.
 *
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/setup.php';

class wact_Plugin extends wact_Setup {
	public function __construct($config) {
		$this->config = $config;
	}
}