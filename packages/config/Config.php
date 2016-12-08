<?php

namespace Config;

class Config
{
	/**
	 * @var Config
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	private $config = [];

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function get($key) {
		if (empty($this->config)) {
			$this->config = array_merge(
				require(dirname(__FILE__) . '/configs/global.php'),
				require(dirname(__FILE__) . '/configs/local.php')
			);
		}

		if (isset($this->config[$key])) {
			return $this->config[$key];
		}

		return null;
	}

	private function __construct() {}

	private function __clone() {}

	static function me() {
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}
}