<?php


class Config {


	protected static $config = null;

	static function init() {
		$file = dirname(dirname(__FILE__)) . '/etc/config.json';
		self::$config = json_decode(file_get_contents($file), true);
	}

	static function get($key) {
		if (!isset(self::$config[$key])) return null;
		return self::$config[$key];
	}


}