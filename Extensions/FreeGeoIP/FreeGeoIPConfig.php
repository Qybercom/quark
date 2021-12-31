<?php
namespace Quark\Extensions\FreeGeoIP;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class FreeGeoIPConfig
 *
 * @package Quark\Extensions\FreeGeoIP
 */
class FreeGeoIPConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_apiKey = ''
	 */
	private $_apiKey = '';

	/**
	 * @param string $key = ''
	 *
	 * @return string
	 */
	public function APIKey ($key = '') {
		if (func_num_args() != 0)
			$this->_apiKey = $key;

		return $this->_apiKey;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->APIKey))
			$this->APIKey($ini->APIKey);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new FreeGeoIP($this->_name);
	}
}