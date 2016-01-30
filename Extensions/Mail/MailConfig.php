<?php
namespace Quark\Extensions\Mail;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class MailConfig
 *
 * @package Quark\Extensions\Mail
 */
class MailConfig implements IQuarkExtensionConfig {
	/**
	 * @var IQuarkMailProvider $_provider
	 */
	private $_provider;

	/**
	 * @var string $username
	 */
	private $_username;

	/**
	 * @var string $_password
	 */
	private $_password;

	/**
	 * @var string $_fullname
	 */
	private $_fullname = '';

	/**
	 * @param IQuarkMailProvider $provider
	 * @param $username
	 * @param $password
	 * @param string $fullname = ''
	 */
	public function __construct (IQuarkMailProvider $provider, $username, $password, $fullname = '') {
		$this->_provider = $provider;
		$this->_username = $username;
		$this->_password = $password;
		$this->_fullname = $fullname;
	}

	/**
	 * @return string
	 */
	public function From () {
		return Mail::Sender($this->_fullname, $this->_username);
	}

	/**
	 * @return IQuarkMailProvider
	 */
	public function &MailProvider () {
		return $this->_provider;
	}

	/**
	 * @return \Quark\QuarkURI
	 */
	public function MailSMTPEndpoint () {
		return $this->_provider->MailSMTP($this->_username, $this->_password);
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		// TODO: Implement Stacked() method.
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}
}