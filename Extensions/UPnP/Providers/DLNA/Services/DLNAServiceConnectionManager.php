<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\Services;

use Quark\Extensions\UPnP\IQuarkUPnPProviderService;

use Quark\Extensions\UPnP\UPnPServiceControlProtocol;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolAction;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolVariable;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolVariableBehavior;
use Quark\Extensions\UPnP\UPnPServiceDescription;

/**
 * Class DLNAServiceConnectionManager
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\Services
 */
class DLNAServiceConnectionManager implements IQuarkUPnPProviderService {
	const NAME = 'ConnectionManager';
	const TYPE = 'urn:schemas-upnp-org:service:ConnectionManager:1';

	use UPnPServiceControlProtocolVariableBehavior;

	/**
	 * @return string
	 */
	public function UPnPServiceName () {
		return self::NAME;
	}

	/**
	 * @return UPnPServiceDescription
	 */
	public function UPnPServiceDescription () {
		return new UPnPServiceDescription(
			'urn:upnp-org:serviceId:' . self::NAME,
			self::TYPE,
			$this->_urlDescription . self::NAME,
			$this->_urlControl . self::NAME,
			$this->_urlEvent . self::NAME
		);
	}

	/**
	 * @return UPnPServiceControlProtocol
	 */
	public function UPnPServiceControlProtocol () {
		$protocol = new UPnPServiceControlProtocol();

		//$protocol->Action(new UPnPServiceControlProtocolAction());

		return $protocol;
	}
}