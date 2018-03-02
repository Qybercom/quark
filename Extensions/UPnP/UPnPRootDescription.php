<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkKeyValuePair;
use Quark\QuarkObject;
use Quark\QuarkXMLNode;

/**
 * Class UPnPRootDescription
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPRootDescription {
	const VERSION_MAJOR = 1;
	const VERSION_MINOR = 0;

	const ROOT_DEVICE = 'upnp:rootdevice';
	const ROOT_DEVICE_XMLNS = 'urn:schemas-upnp-org:device-1-0';

	const DEFAULT_UUID = 'a6b50411-1554-39b3-4519-afa46c56b974';
	const DEFAULT_FRIENDLY_NAME = 'Quark UPnP';
	const DEFAULT_MANUFACTURER = 'Alex Furnica';
	const DEFAULT_MANUFACTURER_URL = 'https://github.com/Qybercom/quark';
	const DEFAULT_MODEL_NAME = 'QuarkUPnP/1.0';
	const DEFAULT_MODEL_NUMBER = '1.0.0';
	const DEFAULT_MODEL_URL = 'https://github.com/Qybercom/quark';
	const DEFAULT_SERIAL_NUMBER = '';

	/**
	 * @var string $_location = ''
	 */
	private $_location = '';

	/**
	 * @var int $_versionMajor = self::VERSION_MAJOR
	 */
	private $_versionMajor = self::VERSION_MAJOR;

	/**
	 * @var int $_versionMinor = self::VERSION_MINOR
	 */
	private $_versionMinor = self::VERSION_MINOR;

	/**
	 * @var QuarkKeyValuePair[] $_attributes = []
	 */
	private $_attributes = array();

	/**
	 * @var QuarkKeyValuePair[] $_attributesXMLNS = []
	 */
	private $_attributesXMLNS = array();

	/**
	 * @var string $_uuID = self::DEFAULT_UUID
	 */
	private $_uuID = self::DEFAULT_UUID;

	/**
	 * @var string $_friendlyName = self::DEFAULT_FRIENDLY_NAME
	 */
	private $_friendlyName = self::DEFAULT_FRIENDLY_NAME;

	/**
	 * @var string $_deviceType = null
	 */
	private $_deviceType = null;

	/**
	 * @var string $_manufacturer = self::DEFAULT_MANUFACTURER
	 */
	private $_manufacturer = self::DEFAULT_MANUFACTURER;

	/**
	 * @var string $_manufacturerUrl = self::DEFAULT_MANUFACTURER_URL
	 */
	private $_manufacturerUrl = self::DEFAULT_MANUFACTURER_URL;

	/**
	 * @var string $_modelName = self::DEFAULT_MODEL_NAME
	 */
	private $_modelName = self::DEFAULT_MODEL_NAME;

	/**
	 * @var string $_modelDescription = ''
	 */
	private $_modelDescription = '';

	/**
	 * @var string $_modelNumber = self::DEFAULT_MODEL_NUMBER
	 */
	private $_modelNumber = self::DEFAULT_MODEL_NUMBER;

	/**
	 * @var string $_modelURL = self::DEFAULT_MODEL_URL
	 */
	private $_modelURL = self::DEFAULT_MODEL_URL;

	/**
	 * @var string $_serialNumber = self::DEFAULT_SERIAL_NUMBER
	 */
	private $_serialNumber = self::DEFAULT_SERIAL_NUMBER;

	/**
	 * @var UPnPRootDescriptionIcon[] $_icons = []
	 */
	private $_icons = array();

	/**
	 * @var UPnPServiceDescription[] $_services = []
	 */
	private $_services = array();

	/**
	 * @var string $_urlBase = null
	 */
	private $_urlBase = null;

	/**
	 * UPnPRootDescription constructor.
	 */
	public function __construct () {
		$this->_services[] = new UPnPRootDeviceService();
	}

	/**
	 * @param string $location = ''
	 *
	 * @return string
	 */
	public function Location ($location = '') {
		if (func_num_args() != 0)
			$this->_location = $location;

		return $this->_location;
	}

	/**
	 * @param int $version = self::VERSION_MAJOR
	 *
	 * @return int
	 */
	public function VersionMajor ($version = self::VERSION_MAJOR) {
		if (func_num_args() != 0)
			$this->_versionMajor = $version;

		return $this->_versionMajor;
	}

	/**
	 * @param int $version = self::VERSION_MINOR
	 *
	 * @return int
	 */
	public function VersionMinor ($version = self::VERSION_MINOR) {
		if (func_num_args() != 0)
			$this->_versionMinor = $version;

		return $this->_versionMinor;
	}

	/**
	 * @param QuarkKeyValuePair[] $attributes = []
	 *
	 * @return QuarkKeyValuePair[]
	 */
	public function Attributes ($attributes = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($attributes, new QuarkKeyValuePair()))
			$this->_attributes = $attributes;

		return $this->_attributes;
	}

	/**
	 * @param QuarkKeyValuePair $attribute = null
	 *
	 * @return UPnPRootDescription
	 */
	public function Attribute (QuarkKeyValuePair $attribute = null) {
		if ($attribute != null)
			$this->_attributes[] = $attribute;

		return $this;
	}

	/**
	 * @param QuarkKeyValuePair[] $ns = []
	 *
	 * @return QuarkKeyValuePair[]
	 */
	public function AttributesXMLNS ($ns = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($ns, new QuarkKeyValuePair()))
			$this->_attributesXMLNS = $ns;

		return $this->_attributesXMLNS;
	}

	/**
	 * @param QuarkKeyValuePair $ns = null
	 *
	 * @return UPnPRootDescription
	 */
	public function AttributeXMLNS (QuarkKeyValuePair $ns = null) {
		if ($ns != null)
			$this->_attributesXMLNS[] = $ns;

		return $this;
	}

	/**
	 * @param string $uuid = ''
	 *
	 * @return string
	 */
	public function UuID ($uuid = '') {
		if (func_num_args() != 0)
			$this->_uuID = $uuid;

		return $this->_uuID;
	}

	/**
	 * @param string $name = self::DEFAULT_FRIENDLY_NAME
	 *
	 * @return string
	 */
	public function FriendlyName ($name = self::DEFAULT_FRIENDLY_NAME) {
		if (func_num_args() != 0)
			$this->_friendlyName = $name;

		return $this->_friendlyName;
	}

	/**
	 * @param string $type = null
	 *
	 * @return string
	 */
	public function DeviceType ($type = null) {
		if (func_num_args() != 0)
			$this->_deviceType = $type;

		return $this->_deviceType;
	}

	/**
	 * @param string $manufacturer = self::DEFAULT_MANUFACTURER
	 *
	 * @return string
	 */
	public function Manufacturer ($manufacturer = self::DEFAULT_MANUFACTURER) {
		if (func_num_args() != 0)
			$this->_manufacturer = $manufacturer;

		return $this->_manufacturer;
	}

	/**
	 * @param string $manufacturerUrl = self::DEFAULT_MANUFACTURER_URL
	 *
	 * @return string
	 */
	public function ManufacturerURL ($manufacturerUrl = self::DEFAULT_MANUFACTURER_URL) {
		if (func_num_args() != 0)
			$this->_manufacturerUrl = $manufacturerUrl;

		return $this->_manufacturerUrl;
	}

	/**
	 * @param string $name = self::DEFAULT_MODEL_NAME
	 *
	 * @return string
	 */
	public function ModelName ($name = self::DEFAULT_MODEL_NAME) {
		if (func_num_args() != 0)
			$this->_modelName = $name;

		return $this->_modelName;
	}

	/**
	 * @param string $description = ''
	 *
	 * @return string
	 */
	public function ModelDescription ($description = '') {
		if (func_num_args() != 0)
			$this->_modelDescription = $description;

		return $this->_modelDescription;
	}

	/**
	 * @param string $number = self::DEFAULT_MODEL_NUMBER
	 *
	 * @return string
	 */
	public function ModelNumber ($number = self::DEFAULT_MODEL_NUMBER) {
		if (func_num_args() != 0)
			$this->_modelNumber = $number;

		return $this->_modelNumber;
	}

	/**
	 * @param string $url = self::DEFAULT_MODEL_URL
	 *
	 * @return string
	 */
	public function ModelURL ($url = self::DEFAULT_MODEL_URL) {
		if (func_num_args() != 0)
			$this->_modelURL = $url;

		return $this->_modelURL;
	}

	/**
	 * @param string $number = self::DEFAULT_SERIAL_NUMBER
	 *
	 * @return string
	 */
	public function SerialNumber ($number = self::DEFAULT_SERIAL_NUMBER) {
		if (func_num_args() != 0)
			$this->_serialNumber = $number;

		return $this->_serialNumber;
	}

	/**
	 * @param UPnPRootDescriptionIcon[] $icons = []
	 *
	 * @return UPnPRootDescriptionIcon[]
	 */
	public function Icons ($icons = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($icons, new UPnPRootDescriptionIcon()))
			$this->_icons = $icons;

		return $this->_icons;
	}

	/**
	 * @param UPnPRootDescriptionIcon $icon = null
	 *
	 * @return UPnPRootDescription
	 */
	public function Icon (UPnPRootDescriptionIcon $icon = null) {
		if ($icon != null)
			$this->_icons[] = $icon;

		return $this;
	}

	/**
	 * @param UPnPServiceDescription[] $services = []
	 *
	 * @return UPnPServiceDescription[]
	 */
	public function Services ($services = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($services, new UPnPServiceDescription()))
			$this->_services = $services;

		return $this->_services;
	}

	/**
	 * @param UPnPServiceDescription $service = null
	 *
	 * @return UPnPRootDescription
	 */
	public function Service (UPnPServiceDescription $service = null) {
		if ($service != null)
			$this->_services[] = $service;

		return $this;
	}

	/**
	 * @param string $url = null
	 *
	 * @return string
	 */
	public function URLBase ($url = null) {
		if (func_num_args() != 0)
			$this->_urlBase = $url;

		return $this->_urlBase;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function ToXML () {
		$icons = array();
		foreach ($this->_icons as $icon)
			$icons[] = $icon->ToXML();

		$services = array();
		foreach ($this->_services as $service)
			$services[] = $service->ToXML();

		$attributes = array(
			'xmlns' => self::ROOT_DEVICE_XMLNS
		);

		foreach ($this->_attributesXMLNS as $i => &$ns)
			$attributes[$ns->Key()] = $ns->Value();

		$device = array();

		foreach ($this->_attributes as $i => &$attribute)
			$device[] = new QuarkXMLNode($attribute->Key(), $attribute->Value());

		$device = array_merge($device, array(
			new QuarkXMLNode('UDN', 'uuid:' . $this->_uuID),
			new QuarkXMLNode('friendlyName', $this->_friendlyName),
			new QuarkXMLNode('deviceType', $this->_deviceType),
			new QuarkXMLNode('manufacturer', $this->_manufacturer),
			new QuarkXMLNode('manufacturerUrl', $this->_manufacturerUrl),
			new QuarkXMLNode('modelName', $this->_modelName),
			new QuarkXMLNode('modelDescription', $this->_modelDescription),
			new QuarkXMLNode('modelNumber', $this->_modelNumber),
			new QuarkXMLNode('modelUUL', $this->_modelURL),
			new QuarkXMLNode('serialNumber', $this->_serialNumber)
		));

		if (sizeof($this->_icons) != 0)
			$device[] = new QuarkXMLNode('iconList', $icons);

		$device[] = new QuarkXMLNode('serviceList', $services);

		$xml = array(
			'specVersion' => array(
				'major' => $this->_versionMajor,
				'minor' => $this->_versionMinor
			),
			'device' => $device
		);

		if ($this->_urlBase !== null)
			$xml['URLBase'] = $this->_urlBase;

		return QuarkXMLNode::Root('root', $attributes, $xml);
	}
}