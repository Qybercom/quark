<?php
namespace Quark\Extensions\SendGrid;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class SendGridConfig
 *
 * @package Quark\Extensions\SendGrid
 */
class SendGridConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_apiKey = ''
	 */
	private $_apiKey = '';

	/**
	 * @var string $_fromAddress = ''
	 */
	private $_fromAddress = '';

	/**
	 * @var string $_fromName = ''
	 */
	private $_fromName = '';

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
	 * @param string $address = ''
	 *
	 * @return string
	 */
	public function FromAddress ($address = '') {
		if (func_num_args() != 0)
			$this->_fromAddress = $address;

		return $this->_fromAddress;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function FromName ($name = '') {
		if (func_num_args() != 0)
			$this->_fromName = $name;

		return $this->_fromName;
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

		if (isset($ini->FromAddress))
			$this->FromAddress($ini->FromAddress);

		if (isset($ini->FromName))
			$this->FromName($ini->FromName);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new SendGrid($this->_name);
	}
}