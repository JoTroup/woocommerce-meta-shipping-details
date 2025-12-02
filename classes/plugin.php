<?php
/**
 * The core plugin class.
 * Handles initialization and configuration.
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/setup.php';

class wmsd_Plugin extends wmsd_Setup {
	public function __construct($config) {
		$this->config = $config;
	}
}