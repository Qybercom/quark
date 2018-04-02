<?php
namespace Quark\Extensions\CertificateAuthority;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfigWithForcedOptions;

/**
 * Class SSLAuthorityConfig
 *
 * @package Quark\Extensions\CertificateAuthority
 */
class CertificateAuthorityConfig implements IQuarkExtensionConfigWithForcedOptions {
	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @var IQuarkCertificateAuthorityProvider $_provider
	 */
	private $_provider;

	/**
	 * @param IQuarkCertificateAuthorityProvider $provider
	 */
	public function __construct (IQuarkCertificateAuthorityProvider $provider) {
		$this->_provider = $provider;
	}

	/**
	 * @return IQuarkCertificateAuthorityProvider
	 */
	public function &Provider () {
		return $this->_provider;
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
		$this->_provider->SSLAuthorityOptions($ini);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new CertificateAuthority($this->_name);
	}
}