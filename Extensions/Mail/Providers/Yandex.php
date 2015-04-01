<?php
namespace Quark\Extensions\Mail\Providers;

use Quark\Extensions\Mail\Mail;
use Quark\IQuarkExtensionConfig;
use Quark\Extensions\Mail\IQuarkMailProvider;

use Quark\QuarkURI;

/**
 * Class Yandex
 *
 * @package Quark\Extensions\Mail\Providers
 */
class Yandex implements IQuarkMailProvider, IQuarkExtensionConfig {
	private $_username;
	private $_password;
	private $_name;

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $name
	 */
	public function __construct ($username, $password, $name = '') {
		$this->_username = $username;
		$this->_password = $password;
		$this->_name = $name;
	}

	/**
	 * @return QuarkURI
	 */
	public function SMTP () {
		return QuarkURI::FromURI('ssl://smtp.yandex.ru:465')->User($this->_username, $this->_password);
	}

	/**
	 * @return string
	 */
	public function From () {
		return Mail::Sender($this->_name, $this->_username);
	}
}