<?php
namespace Quark\Extensions\Mail;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;
use Quark\QuarkCertificate;

/**
 * Class MailConfig
 *
 * @package Quark\Extensions\Mail
 */
class MailConfig implements IQuarkExtensionConfig {
	const TIMEOUT_CONNECT = 5;
	const TIMEOUT_COMMAND = 100000;

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
	private $_fullName = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_sender = ''
	 */
	private $_sender = '';

	/**
	 * @var string $_from = ''
	 */
	private $_from = '';

	/**
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * @var int $_timeoutConnect = self::TIMEOUT_CONNECT (seconds)
	 */
	private $_timeoutConnect = self::TIMEOUT_CONNECT;

	/**
	 * @var int $_timeoutCommand = self::TIMEOUT_COMMAND (microseconds)
	 */
	private $_timeoutCommand = self::TIMEOUT_COMMAND;

	/**
	 * @var bool $_log = false
	 */
	private $_log = false;

	/**
	 * @param IQuarkMailProvider $provider
	 * @param $username = ''
	 * @param $password = ''
	 * @param string $fullName = ''
	 */
	public function __construct (IQuarkMailProvider $provider, $username = '', $password = '', $fullName = '') {
		$this->_provider = $provider;
		$this->_username = $username;
		$this->_password = $password;
		$this->_fullName = $fullName;
	}

	/**
	 * @return string
	 */
	public function Sender () {
		return $this->_sender ? $this->_sender : Mail::Sender($this->_fullName, $this->_username);
	}

	/**
	 * @return string
	 */
	public function From () {
		return $this->_from ? $this->_from : $this->MailSMTPEndpoint()->user;
	}

	/**
	 * @param string $username = ''
	 *
	 * @return string
	 */
	public function Username ($username = '') {
		if (func_num_args() != 0)
			$this->_username = $username;

		return $this->_username;
	}

	/**
	 * @param string $password = ''
	 *
	 * @return string
	 */
	public function Password ($password = '') {
		if (func_num_args() != 0)
			$this->_password = $password;

		return $this->_password;
	}

	/**
	 * @param string $fullName = ''
	 *
	 * @return string
	 */
	public function FullName ($fullName = '') {
		if (func_num_args() != 0)
			$this->_fullName = $fullName;

		return $this->_fullName;
	}

	/**
	 * @param int $timeout = self::TIMEOUT_CONNECT (seconds)
	 *
	 * @return int
	 */
	public function TimeoutConnect ($timeout = self::TIMEOUT_CONNECT) {
		if (func_num_args() != 0)
			$this->_timeoutConnect = $timeout;

		return $this->_timeoutConnect;
	}

	/**
	 * @param int $timeout = self::TIMEOUT_COMMAND (microseconds)
	 *
	 * @return int
	 */
	public function TimeoutCommand ($timeout = self::TIMEOUT_COMMAND) {
		if (func_num_args() != 0)
			$this->_timeoutCommand = $timeout;

		return $this->_timeoutCommand;
	}

	/**
	 * @return bool
	 */
	public function Log () {
		return $this->_log;
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
	 * @return QuarkCertificate
	 */
	public function &MailCertificate () {
		return $this->_certificate;
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
	 * @return void
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->Username))
			$this->_username = $ini->Username;

		if (isset($ini->Password))
			$this->_password = $ini->Password;

		if (isset($ini->FullName))
			$this->_fullName = $ini->FullName;

		if (isset($ini->Sender))
			$this->_sender = $ini->Sender;

		if (isset($ini->From))
			$this->_from = $ini->From;
		
		if (isset($ini->CertificateLocation))
			$this->_certificate = new QuarkCertificate($ini->CertificateLocation);
		
		if (isset($ini->CertificatePassphrase))
			$this->_certificate->Passphrase($ini->CertificatePassphrase);

		if (isset($ini->TimeoutConnect))
			$this->_timeoutConnect = $ini->TimeoutConnect;

		if (isset($ini->TimeoutCommand))
			$this->_timeoutCommand = $ini->TimeoutCommand;

		$this->_log = isset($ini->Log) && $ini->Log;

		$this->_provider->MailINI($ini);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new Mail($this->_name);
	}
}