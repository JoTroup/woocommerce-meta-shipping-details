<?php
/**
 * The core plugin class.
 * Handles initialization and configuration.
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/setup.php';

class {plugin_prefix}_Plugin extends {plugin_prefix}_Setup {
	public function __construct($config) {
		$this->config = $config;
	}
}