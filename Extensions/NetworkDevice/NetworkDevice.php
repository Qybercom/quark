<?php
namespace Quark\Extensions\NetworkDevice;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkCollection;
use Quark\QuarkKeyValuePair;

use Quark\Extensions\NetworkDevice\Primitives\NetworkInterface;
use Quark\Extensions\NetworkDevice\Primitives\DHCPServerLease;

/**
 * Class NetworkDevice
 *
 * @package Quark\Extensions\NetworkDevice
 */
class NetworkDevice implements IQuarkExtension {
	const IPv4 = 'v4';
	const IPv6 = 'v6';
	
	/**
	 * @var NetworkDeviceConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config
	 */
	public function __construct ($config) {
		$this->_config = Quark::Config()->Extension($config);
	}
	
	/**
	 * @return bool
	 */
	public function Connect () {
		return $this->_config->Provider()->NetworkDeviceConnect($this->_config->URI());
	}
	
	/**
	 * @return bool
	 */
	public function Close () {
		return $this->_config->Provider()->NetworkDeviceClose();
	}
	
	/**
	 * @return bool
	 */
	public function Connected () {
		return $this->_config->Provider()->NetworkDeviceConnected();
	}
	
	/**
	 * @return bool
	 */
	public function Authorized () {
		return $this->_config->Provider()->NetworkDeviceAuthorized();
	}
	
	/**
	 * @param string $method
	 *
	 * @return bool
	 */
	private function _checkConnect ($method) {
		if ($this->_config->Provider()->NetworkDeviceConnected()) return true;
		
		Quark::Log('[NetworkDevice::' . $method . '] Provider is not connected');
		
		return false;
	}
	
	/**
	 * @param QuarkKeyValuePair $user = null
	 *
	 * @return bool
	 */
	public function Authorize (QuarkKeyValuePair $user = null) {
		if (!$this->_checkConnect('Authorize')) return false;

		return $this->_config->Provider()->NetworkDeviceAuthorize($user == null ? $this->_config->URI()->Credentials() : $user);
	}

	/**
	 * @return QuarkCollection|NetworkInterface[]
	 */
	public function InterfaceList () {
		if (!$this->_checkConnect('InterfaceList')) return null;

		return $this->_config->Provider()->NetworkDeviceInterfaceList();
	}

	/**
	 * @param string $ipVersion = self::IPv4
	 *
	 * @return QuarkCollection|DHCPServerLease[]
	 */
	public function DHCPServerLeaseList ($ipVersion = self::IPv4) {
		if (!$this->_checkConnect('DHCPSeverLeaseList')) return null;

		return $this->_config->Provider()->NetworkDeviceDHCPServerLeaseList($ipVersion);
	}
}