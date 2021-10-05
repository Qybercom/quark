<?php
namespace Quark\Extensions\OpenWeather;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;
use Quark\QuarkLanguage;

/**
 * Class OpenWeatherConfig
 *
 * @package Quark\Extensions\OpenWeather
 */
class OpenWeatherConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_version = OpenWeather::VERSION_DEFAULT
	 */
	private $_version = OpenWeather::VERSION_DEFAULT;

	/**
	 * @var string $_units = OpenWeather::UNITS_STANDARD
	 */
	private $_units = OpenWeather::UNITS_STANDARD;

	/**
	 * @var string $_language = QuarkLanguage::ANY
	 */
	private $_language = QuarkLanguage::ANY;

	/**
	 * @param string $appID = ''
	 *
	 * @return string
	 */
	public function AppID ($appID = '') {
		if (func_num_args() != 0)
			$this->_appId = $appID;

		return $this->_appId;
	}

	/**
	 * @param string $version = OpenWeather::VERSION_DEFAULT
	 *
	 * @return string
	 */
	public function Version ($version = OpenWeather::VERSION_DEFAULT) {
		if (func_num_args() != 0)
			$this->_version = $version;

		return $this->_version;
	}

	/**
	 * @param string $units = OpenWeather::UNITS_STANDARD
	 *
	 * @return string
	 */
	public function Units ($units = OpenWeather::UNITS_STANDARD) {
		if (func_num_args() != 0)
			$this->_units = $units;

		return $this->_units;
	}

	/**
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return string
	 */
	public function Language ($language = QuarkLanguage::ANY) {
		if (func_num_args() != 0)
			$this->_language = $language;

		return $this->_language;
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
		if (isset($ini->AppID))
			$this->AppID($ini->AppID);

		if (isset($ini->Version))
			$this->Version($ini->Version);

		if (isset($ini->Units))
			$this->Units($ini->Units);

		if (isset($ini->Language))
			$this->Language($ini->Language);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new OpenWeather($this->_name);
	}
}