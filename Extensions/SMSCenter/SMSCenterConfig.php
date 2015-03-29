<?php
namespace Quark\Extensions\SMSCenter;

use Quark\IQuarkExtensionConfig;

/**
 * Class SMSCenterConfig
 *
 * @package Quark\Extensions\SMSCenter
 */
class SMSCenterConfig implements IQuarkExtensionConfig {
	public $username = '';
	public $password = '';
	public $sender = '';

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
}