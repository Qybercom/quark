<?php
namespace Quark\Extensions\Mail\Providers;

use Quark\IQuarkExtensionConfig;
use Quark\Extensions\Mail\IQuarkMailProvider;

use Quark\QuarkURI;

use Quark\Extensions\Mail\Mail;

/**
 * Class Google
 *
 * @package Quark\Extensions\Mail\Providers
 */
class Google implements IQuarkMailProvider, IQuarkExtensionConfig {
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
		return QuarkURI::FromURI('ssl://smtp.gmail.com:465')->User($this->_username, $this->_password);
	}

	/**
	 * @return string
	 */
	public function From () {
		return Mail::Sender($this->_name, $this->_username);
	}
}