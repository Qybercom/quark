<?php
namespace Quark\Extensions\UPnP\Providers\DLNA;

use Quark\QuarkKeyValuePair;
use Quark\Extensions\UPnP\IQuarkUPnPProvider;
use Quark\Extensions\UPnP\IQuarkUPnPProviderService;

use Quark\Extensions\UPnP\Providers\DLNA\Services\DLNAServiceConnectionManager;
use Quark\Extensions\UPnP\Providers\DLNA\Services\DLNAServiceContentDirectory;

/**
 * Class DLNA
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA
 */
class DLNA implements IQuarkUPnPProvider {
	const UPnP_DEVICE_TYPE = 'urn:schemas-upnp-org:device:MediaServer:1';

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function UPnPProviderOptions ($ini) {
		// TODO: Implement UPnPProviderOptions() method.
	}

	/**
	 * @return string
	 */
	public function UPnPProviderDeviceType () {
		return self::UPnP_DEVICE_TYPE;
	}

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function UPnPProviderAttributes () {
		return array(
			new QuarkKeyValuePair('dlna:X_DLNACAP', null),
			new QuarkKeyValuePair('dlna:X_DLNADOC', 'DMS-1.50')
		);
	}

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function UPnPProviderAttributesXMLNS () {
		return array(
			new QuarkKeyValuePair('xmlns:dlna', 'urn:schemas-dlna-org:device-1-0'),
			new QuarkKeyValuePair('xmlns:sec', 'http://www.sec.co.kr/dlna')
		);
	}

	/**
	 * @return IQuarkUPnPProviderService[]
	 */
	public function UPnPProviderServices () {
		return array(
			new DLNAServiceConnectionManager(),
			new DLNAServiceContentDirectory()
		);
	}
}