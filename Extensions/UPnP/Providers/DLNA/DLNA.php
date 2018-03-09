<?php
namespace Quark\Extensions\UPnP\Providers\DLNA;

use Quark\QuarkObject;
use Quark\QuarkKeyValuePair;

use Quark\Extensions\UPnP\IQuarkUPnPProvider;
use Quark\Extensions\UPnP\IQuarkUPnPProviderService;

use Quark\Extensions\UPnP\Providers\DLNA\Services\DLNAServiceConnectionManager;
use Quark\Extensions\UPnP\Providers\DLNA\Services\DLNAServiceContentDirectory;
use Quark\Extensions\UPnP\Providers\DLNA\Services\DLNAServiceXMSMediaReceiverRegistrar;

/**
 * Class DLNA
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA
 */
class DLNA implements IQuarkUPnPProvider {
	const UPnP_DEVICE_TYPE = 'urn:schemas-upnp-org:device:MediaServer:1';

	/**
	 * @var IQuarkUPnPProviderService[] $_services = []
	 */
	private $_services = array();

	/**
	 * @param IQuarkUPnPProviderService $service = null
	 *
	 * @return DLNA
	 */
	public function Service (IQuarkUPnPProviderService $service = null) {
		if ($service != null)
			$this->_services[] = $service;

		return $this;
	}

	/**
	 * @param IQuarkUPnPProviderService[] $services = []
	 *
	 * @return IQuarkUPnPProviderService[]
	 */
	public function Services ($services = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($services, 'Quark\\Extensions\\UPnP\\IQuarkUPnPProviderService', true))
			$this->_services = $services;

		return $this->_services;
	}

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
			new QuarkKeyValuePair('dlna:X_DLNADOC', 'DMS-1.50'),
			new QuarkKeyValuePair('dlna:X_DLNADOC', 'M-DMS-1.50')
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
		return $this->_services;
	}

	/**
	 * @return DLNA
	 */
	public static function Predefined () {
		$out = new self();

		$out->Services(array(
			new DLNAServiceConnectionManager(),
			new DLNAServiceContentDirectory(),
			new DLNAServiceXMSMediaReceiverRegistrar()
		));

		return $out;
	}
}