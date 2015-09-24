<?php
namespace Quark\Extensions\Mail\Providers;

use Quark\IQuarkExtension;
use Quark\QuarkURI;

use Quark\Extensions\Mail\IQuarkMailProvider;
use Quark\Extensions\Mail\Mail;

/**
 * Class Google
 *
 * @package Quark\Extensions\Mail\Providers
 */
class Google implements IQuarkMailProvider {
	private $_username;
	private $_password;
	private $_fullname;

	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $name
	 */
	public function __construct ($username, $password, $name = '') {
		$this->_username = $username;
		$this->_password = $password;
		$this->_fullname = $name;
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
		return Mail::Sender($this->_fullname, $this->_username);
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

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}
}