<?php
namespace Quark\Extensions\UPnP;

/**
 * Class UPnPRootDeviceService
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPRootDeviceService implements IQuarkUPnPProviderService {
	const TYPE = 'upnp:rootdevice';

	/**
	 * @return string
	 */
	public function UPnPServiceName () {
		return 'upnp:rootdevice';
	}

	/**
	 * @param string $description
	 * @param string $control
	 * @param string $event
	 *
	 * @return mixed
	 */
	public function UPnPServiceURLs ($description, $control, $event) {
		// TODO: Implement UPnPServiceURLs() method.
	}

	/**
	 * @param string $name
	 *
	 * @return UPnPServiceControlProtocolVariable
	 */
	public function UPnPServiceVariable ($name) {
		// TODO: Implement UPnPServiceVariable() method.
	}

	/**
	 * @return UPnPServiceControlProtocolVariable[]
	 */
	public function UPnPServiceVariableList () {
		// TODO: Implement UPnPServiceVariableList() method.
	}

	/**
	 * @return UPnPServiceDescription
	 */
	public function UPnPServiceDescription () {
		return new UPnPServiceDescription(self::TYPE, self::TYPE);
	}

	/**
	 * @return UPnPServiceControlProtocol
	 */
	public function UPnPServiceControlProtocol () {
		// TODO: Implement UPnPServiceControlProtocol() method.
	}
}