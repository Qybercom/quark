<?php
namespace Quark\Extensions\SMS;

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
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}
}