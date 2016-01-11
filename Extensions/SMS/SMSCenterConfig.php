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
	public $username = '';
	public $password = '';
	public $sender = '';

	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $sender
	 */
	public function __construct ($username, $password, $sender = '') {
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
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new SMS($this->_name);
	}
}