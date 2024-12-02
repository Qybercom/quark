<?php
namespace Quark\Extensions\NetworkDevice;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;
use Quark\QuarkKeyValuePair;
use Quark\QuarkURI;

/**
 * Class NetworkDeviceConfig
 *
 * @package Quark\Extensions\NetworkDevice
 */
class NetworkDeviceConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var INetworkDeviceProvider $_provider
	 */
	private $_provider;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;
	
	/**
	 * @var bool $_autoConnect = true
	 */
	private $_autoConnect = true;
	
	/**
	 * @var bool $_autoLogin = true
	 */
	private $_autoLogin = true;

	/**
	 * @param INetworkDeviceProvider $provider = null
	 */
	public function __construct (INetworkDeviceProvider $provider = null) {
		$this->Provider($provider);
	}

	/**
	 * @param INetworkDeviceProvider $provider = null
	 *
	 * @return INetworkDeviceProvider
	 */
	public function &Provider (INetworkDeviceProvider $provider = null) {
		if (func_num_args() != 0)
			$this->_provider = $provider;

		return $this->_provider;
	}

	/**
	 * @param QuarkURI $uri = null
	 *
	 * @return QuarkURI
	 */
	public function &URI (QuarkURI $uri = null) {
		if (func_num_args() != 0)
			$this->_uri = $uri;

		return $this->_uri;
	}
	
	/**
	 * @param bool $auto = true
	 *
	 * @return bool
	 */
	public function AutoConnect ($auto = true) {
		if (func_num_args() != 0)
			$this->_autoConnect = $auto;
		
		return $this->_autoConnect;
	}
	
	/**
	 * @param bool $auto = true
	 *
	 * @return bool
	 */
	public function AutoLogin ($auto = true) {
		if (func_num_args() != 0)
			$this->_autoLogin = $auto;
		
		return $this->_autoLogin;
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
		if (isset($ini->URI))
			$this->URI(QuarkURI::FromURI($ini->URI));
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new NetworkDevice($this->_name);
	}
}