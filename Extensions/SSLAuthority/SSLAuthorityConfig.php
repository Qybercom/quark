<?php
namespace Quark\Extensions\SSLAuthority;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfigWithForcedOptions;

/**
 * Class SSLAuthorityConfig
 *
 * @package Quark\Extensions\SSLAuthority
 */
class SSLAuthorityConfig implements IQuarkExtensionConfigWithForcedOptions {
	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @var IQuarkSSLAuthorityProvider $_provider
	 */
	private $_provider;

	/**
	 * @param IQuarkSSLAuthorityProvider $provider
	 */
	public function __construct (IQuarkSSLAuthorityProvider $provider) {
		$this->_provider = $provider;
	}

	/**
	 * @return IQuarkSSLAuthorityProvider
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
		return new SSLAuthority($this->_name);
	}
}