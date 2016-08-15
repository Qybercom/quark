<?php
namespace Quark\Extensions\SMS;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class SMSCenterConfig
 *
 * @package Quark\Extensions\SMS
 */
class SMSCenterConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $username = ''
	 */
	public $username = '';

	/**
	 * @var string $password = ''
	 */
	public $password = '';

	/**
	 * @var string $sender = ''
	 */
	public $sender = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @param string $username = ''
	 * @param string $password = ''
	 * @param string $sender = ''
	 */
	public function __construct ($username = '', $password = '', $sender = '') {
		$this->username = $username;
		$this->password = $password;
		$this->sender = $sender;
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
		if (isset($ini->Username))
			$this->username = $ini->Username;

		if (isset($ini->Password))
			$this->password = $ini->Password;

		if (isset($ini->Sender))
			$this->sender = $ini->Sender;
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new SMS($this->_name);
	}
}