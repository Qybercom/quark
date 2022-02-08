<?php
namespace Quark\Extensions\Mail;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

use Quark\QuarkCertificate;
use Quark\QuarkURI;
use Quark\QuarkDate;

/**
 * Class MailConfig
 *
 * @package Quark\Extensions\Mail
 */
class MailConfig implements IQuarkExtensionConfig {
	const TIMEOUT_CONNECT = 5;
	const TIMEOUT_COMMAND = 100000;

	const LOCAL_STORAGE = './runtime/mails/';

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
	 * @var bool $_localSend = false
	 */
	private $_localSend = false;

	/**
	 * @var string $_localStorage = self::LOCAL_STORAGE
	 */
	private $_localStorage = self::LOCAL_STORAGE;

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
	 * @param string $sender = ''
	 *
	 * @return string
	 */
	public function Sender ($sender = '') {
		if (func_num_args() != 0)
			$this->_sender = $sender;

		return $this->_sender ? $this->_sender : Mail::Sender($this->_fullName, $this->_username);
	}

	/**
	 * @param string $from = ''
	 *
	 * @return string
	 */
	public function From ($from = '') {
		if (func_num_args() != 0)
			$this->_from = $from;

		return $this->_from ? $this->_from : $this->EndpointSMTP()->user;
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
	 * @param QuarkCertificate $certificate = null
	 *
	 * @return QuarkCertificate
	 */
	public function &Certificate (QuarkCertificate $certificate = null) {
		if (func_num_args() != 0)
			$this->_certificate = $certificate;

		return $this->_certificate;
	}

	/**
	 * @param bool $log = false
	 *
	 * @return bool
	 */
	public function Log ($log = false) {
		if (func_num_args() != 0)
			$this->_log = $log;

		return $this->_log;
	}

	/**
	 * @param bool $send = false
	 *
	 * @return bool
	 */
	public function LocalSend ($send = false) {
		if (func_num_args() != 0)
			$this->_localSend = $send;

		return $this->_localSend;
	}

	/**
	 * @param string $storage = self::LOCAL_STORAGE
	 *
	 * @return string
	 */
	public function LocalStorage ($storage = self::LOCAL_STORAGE) {
		if (func_num_args() != 0)
			$this->_localStorage = $storage;

		return $this->_localStorage;
	}

	/**
	 * @return string
	 */
	public function LocalStoragePrefix () {
		return $this->_localStorage . '/' . QuarkDate::GMTNow()->Format('Ymd-His-u');
	}

	/**
	 * @return IQuarkMailProvider
	 */
	public function &Provider () {
		return $this->_provider;
	}

	/**
	 * @return QuarkURI
	 */
	public function EndpointSMTP () {
		return $this->_provider->MailSMTP($this->_username, $this->_password);
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
			$this->Username($ini->Username);

		if (isset($ini->Password))
			$this->Password($ini->Password);

		if (isset($ini->FullName))
			$this->FullName($ini->FullName);

		if (isset($ini->Sender))
			$this->Sender($ini->Sender);

		if (isset($ini->From))
			$this->From($ini->From);
		
		if (isset($ini->CertificateLocation))
			$this->Certificate(new QuarkCertificate($ini->CertificateLocation));
		
		if (isset($ini->CertificatePassphrase))
			$this->_certificate->Passphrase($ini->CertificatePassphrase);

		if (isset($ini->TimeoutConnect))
			$this->TimeoutConnect($ini->TimeoutConnect);

		if (isset($ini->TimeoutCommand))
			$this->TimeoutCommand($ini->TimeoutCommand);

		if (isset($ini->Log))
			$this->Log($ini->Log);

		if (isset($ini->LocalSend))
			$this->LocalSend($ini->LocalSend);

		if (isset($ini->LocalStorage))
			$this->LocalStorage($ini->LocalStorage);

		$this->_provider->MailINI($ini);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new Mail($this->_name);
	}
}