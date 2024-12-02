<?php
namespace Quark\Extensions\NetworkDevice;

use Quark\Extensions\NetworkDevice\Primitives\DHCPServerLease;
use Quark\QuarkCollection;
use Quark\QuarkKeyValuePair;
use Quark\QuarkURI;

use Quark\Extensions\NetworkDevice\Primitives\NetworkInterface;

/**
 * Interface INetworkDeviceProvider
 *
 * @package Quark\Extensions\NetworkDevice
 */
interface INetworkDeviceProvider {
	/**
	 * @return bool
	 */
	public function NetworkDeviceConnected();
	
	/**
	 * @return bool
	 */
	public function NetworkDeviceAuthorized();
	
	/**
	 * @param QuarkURI $uri
	 *
	 * @return bool
	 */
	public function NetworkDeviceConnect(QuarkURI $uri);

	/**
	 * @return bool
	 */
	public function NetworkDeviceClose();
	
	/**
	 * @param QuarkKeyValuePair $user
	 *
	 * @return bool
	 */
	public function NetworkDeviceAuthorize(QuarkKeyValuePair $user);

	/**
	 * @return QuarkCollection|NetworkInterface[]
	 */
	public function NetworkDeviceInterfaceList();

	/**
	 * @return QuarkCollection|DHCPServerLease[]
	 */
	public function NetworkDeviceDHCPServerLeaseList($ipVersion);
}