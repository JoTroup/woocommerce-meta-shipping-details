<?php
/**
 * Base class for plugin helper functions.
 */
class wmsd_Base {
	/**
	 * Configuration array for the plugin.
	 */
	protected $config = [
		'prefix' => 'wmsd',
		'prefixSeparator' => '_',
	];

	/**
	 * Add a prefix to a given name.
	 */
	public function setPrefix($name) {
		return ((strpos($name, $this->config['prefix']) === 0) ? '' : $this->config['prefix']) . $this->config['prefixSeparator'] . $name;
	}

	/**
	 * Retrieve a prefixed option from the database.
	 */
	public function getOption($name, $default = null) {
		$ret = get_option($this->setPrefix($name));
		if (!$ret && $default) {
			$ret = $default;
		}
		return $ret;
	}
	
	/**
	 * Add or update a prefixed option in the database.
	 */
	public function setOption($name, $value) {
		return ($this->getOption($name, '') === '') ? 
			add_option($this->setPrefix($name), $value) : 
			update_option($this->setPrefix($name), $value);
	}
}
