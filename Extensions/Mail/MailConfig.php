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
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * @param IQuarkMailProvider $provider
	 * @param $username = ''
	 * @param $password = ''
	 * @param string $fullname = ''
	 */
	public function __construct (IQuarkMailProvider $provider, $username = '', $password = '', $fullname = '') {
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
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->Username))
			$this->_username = $ini->Username;

		if (isset($ini->Password))
			$this->_password = $ini->Password;

		if (isset($ini->FullName))
			$this->_fullname = $ini->FullName;
		
		if (isset($ini->CertificateLocation))
			$this->_certificate = new QuarkCertificate($ini->CertificateLocation);
		
		if (isset($ini->CertificatePassphrase))
			$this->_certificate->Passphrase($ini->CertificatePassphrase);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new Mail($this->_name);
	}
}