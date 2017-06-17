<?php
namespace Quark\Extensions\Mail\Providers;

use Quark\QuarkURI;

use Quark\Extensions\Mail\IQuarkMailProvider;

/**
 * Class QuarkGenericMailer
 *
 * @package Quark\Extensions\Mail\Providers
 */
class QuarkGenericMailer implements IQuarkMailProvider {
	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @var bool $_startTLS = false
	 */
	private $_startTLS = false;

	/**
	 * @param QuarkURI $uri = null
	 */
	public function __construct (QuarkURI $uri = null) {
		$this->URI($uri);
	}

	/**
	 * @param QuarkURI $uri = null
	 *
	 * @return QuarkURI
	 */
	public function URI (QuarkURI $uri = null) {
		if (func_num_args() != 0)
			$this->_uri = $uri;

		return $this->_uri;
	}

	/**
	 * @param object $ini
	 *
	 * @return void
	 */
	public function MailINI ($ini) {
		if ($this->_uri == null)
			$this->_uri = new QuarkURI(QuarkURI::WRAPPER_TCP);

		if (isset($ini->Protocol))
			$this->_uri->scheme = $ini->Protocol;

		if (isset($ini->Host))
			$this->_uri->host = $ini->Host;

		if (isset($ini->Port))
			$this->_uri->port = $ini->Port;

		if (isset($ini->StartTLS))
			$this->_startTLS = $ini->StartTLS;
	}

	/**
	 * @param string $username
	 * @param string $password
	 *
	 * @return QuarkURI
	 */
	public function MailSMTP ($username, $password) {
		if ($username)
			$this->_uri->user = $username;

		if ($password)
			$this->_uri->pass = $password;

		return $this->_uri;
	}

	/**
	 * @return bool
	 */
	public function MailStartTLS () {
		return $this->_startTLS;
	}
}