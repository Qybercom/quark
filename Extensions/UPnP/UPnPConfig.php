<?php
namespace Quark\Extensions\UPnP;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

use Quark\QuarkURI;

/**
 * Class UPnPConfig
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_announcerHost = QuarkURI::HOST_LOCALHOST
	 */
	private $_announcerHost = QuarkURI::HOST_LOCALHOST;

	/**
	 * @var UPnPRootDescription $_rootDescription
	 */
	private $_rootDescription;

	/**
	 * @var IQuarkUPnPProvider [] $_providers = []
	 */
	private $_providers = array();

	/**
	 * @var string $_urlServiceDescription = ''
	 */
	private $_urlServiceDescription = '';

	/**
	 * @var string $_urlServiceControl = ''
	 */
	private $_urlServiceControl = '';

	/**
	 * @var string $_urlServiceEvent = ''
	 */
	private $_urlServiceEvent = '';

	/**
	 * UPnPConfig constructor.
	 */
	public function __construct () {
		$this->_rootDescription = new UPnPRootDescription();
	}

	/**
	 * @param IQuarkUPnPProvider $provider = null
	 *
	 * @return UPnPConfig
	 */
	public function Provider (IQuarkUPnPProvider $provider = null) {
		if ($provider != null)
			$this->_providers[] = $provider;

		return $this;
	}

	/**
	 * @return IQuarkUPnPProvider[]
	 */
	public function Providers () {
		return $this->_providers;
	}

	/**
	 * @param string $host = QuarkURI::HOST_LOCALHOST
	 *
	 * @return string
	 */
	public function AnnouncerHost ($host = QuarkURI::HOST_LOCALHOST) {
		if (func_num_args() != 0)
			$this->_announcerHost = $host;

		return $this->_announcerHost;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function URLServiceDescription ($url = '') {
		if (func_num_args() != 0)
			$this->_urlServiceDescription = $url;

		return $this->_urlServiceDescription;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function URLServiceControl ($url = '') {
		if (func_num_args() != 0)
			$this->_urlServiceControl = $url;

		return $this->_urlServiceControl;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function URLServiceEvent ($url = '') {
		if (func_num_args() != 0)
			$this->_urlServiceEvent = $url;

		return $this->_urlServiceEvent;
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
		if (isset($ini->AnnouncerHost))
			$this->AnnouncerHost($ini->AnnouncerHost);

		if (isset($ini->Location))
			$this->_rootDescription->Location($ini->Location);

		if (isset($ini->URLServiceDescription))
			$this->URLServiceDescription($ini->URLServiceDescription);

		if (isset($ini->URLServiceControl))
			$this->URLServiceControl($ini->URLServiceControl);

		if (isset($ini->URLServiceEvent))
			$this->URLServiceEvent($ini->URLServiceEvent);

		if (isset($ini->VersionMajor))
			$this->_rootDescription->VersionMajor($ini->VersionMajor);

		if (isset($ini->VersionMinor))
			$this->_rootDescription->VersionMinor($ini->VersionMinor);

		if (isset($ini->UuID))
			$this->_rootDescription->UuID($ini->UuID);

		if (isset($ini->FriendlyName))
			$this->_rootDescription->FriendlyName($ini->FriendlyName);

		if (isset($ini->Manufacturer))
			$this->_rootDescription->Manufacturer($ini->Manufacturer);

		if (isset($ini->ManufacturerURL))
			$this->_rootDescription->ManufacturerURL($ini->ManufacturerURL);

		if (isset($ini->ModelName))
			$this->_rootDescription->ModelName($ini->ModelName);

		if (isset($ini->ModelDescription))
			$this->_rootDescription->ModelDescription($ini->ModelDescription);

		if (isset($ini->ModelNumber))
			$this->_rootDescription->ModelNumber($ini->ModelNumber);

		if (isset($ini->ModelURL))
			$this->_rootDescription->ModelURL($ini->ModelURL);

		if (isset($ini->SerialNumber))
			$this->_rootDescription->SerialNumber($ini->SerialNumber);

		foreach ($this->_providers as $i => &$provider)
			$provider->UPnPProviderOptions($ini);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new UPnP($this->_name);
	}

	/**
	 * @return UPnPRootDescription
	 */
	public function &RootDescription () {
		$services = array();

		foreach ($this->_providers as $i => &$provider) {
			$deviceType = $provider->UPnPProviderDeviceType();

			if ($this->_rootDescription->DeviceType() === null && $deviceType !== null)
				$this->_rootDescription->DeviceType($deviceType);

			$providerAttributes = $provider->UPnPProviderAttributes();
			if (is_array($providerAttributes))
				foreach ($providerAttributes as $j => &$attribute)
					$this->_rootDescription->Attribute($attribute);

			$providerAttributesXMLNS = $provider->UPnPProviderAttributesXMLNS();
			if (is_array($providerAttributesXMLNS))
				foreach ($providerAttributesXMLNS as $j => &$attributeXMLNS)
					$this->_rootDescription->AttributeXMLNS($attributeXMLNS);

			$providerServices = $provider->UPnPProviderServices();
			if (is_array($providerServices))
				foreach ($providerServices as $j => &$service) {
					$service->UPnPServiceURLs(
						$this->_urlServiceDescription,
						$this->_urlServiceControl,
						$this->_urlServiceEvent
					);

					$services[] = $service->UPnPServiceDescription();
				}
		}

		$this->_rootDescription->Services($services);

		return $this->_rootDescription;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return UPnPServiceControlProtocol
	 */
	public function ServiceControlProtocol ($name = '') {
		foreach ($this->_providers as $i => &$provider) {
			$services = $provider->UPnPProviderServices();

			foreach ($services as $j => &$service)
				if ($service->UPnPServiceName() == $name)
					return $service->UPnPServiceControlProtocol();
		}

		return null;
	}
}