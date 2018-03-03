<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\Elements;

use Quark\QuarkXMLNode;

use Quark\Extensions\UPnP\UPnPProperty;
use Quark\Extensions\UPnP\Providers\DLNA\IQuarkDLNAElement;
use Quark\Extensions\UPnP\Providers\DLNA\IQuarkDLNAElementResource;

/**
 * Class DLNAElementItem
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\Elements
 */
class DLNAElementItem implements IQuarkDLNAElement {
	const ELEMENT = 'item';
	const RESOURCE = 'res';

	const PROPERTY_TITLE = 'dc:title';
	const PROPERTY_UPnP_CLASS = 'class';
	const PROPERTY_RES = 'res';

	const UPnP_CLASS_GENERIC = 'object.item';
	CONST UPnP_CLASS_IMAGE = 'object.item.imageItem';
	CONST UPnP_CLASS_IMAGE_PHOTO = 'object.item.imageItem.photo';
	CONST UPnP_CLASS_AUDIO = 'object.item.audioItem';
	CONST UPnP_CLASS_AUDIO_MUSIC_TRACK = 'object.item.audioItem.musicTrack';
	CONST UPnP_CLASS_VIDEO = 'object.item.videoItem';

	const PROTOCOL_TRANSPORT_ALL = '*';
	const PROTOCOL_TRANSPORT_HTTP_GET = 'http-get';
	const PROTOCOL_TRANSPORT_RTSP = 'rtsp';

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_title = ''
	 */
	private $_title = '';

	/**
	 * @var string $_parentID = ''
	 */
	private $_parentID = '';

	/**
	 * @var bool $_restricted = false
	 */
	private $_restricted = false;

	/**
	 * @var UPnPProperty[] $_properties
	 */
	private $_properties = array();

	/**
	 * @param string $id = ''
	 * @param string $title = ''
	 * @param string $parentID = ''
	 * @param bool $restricted = false
	 */
	public function __construct ($id = '', $title = '', $parentID = '', $restricted = false) {
		$this->ID($id);
		$this->Title($title);
		$this->ParentID($parentID);
		$this->Restricted($restricted);
	}

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param string $title = ''
	 *
	 * @return string
	 */
	public function Title ($title = '') {
		if (func_num_args() != 0)
			$this->_title = $title;

		return $this->_title;
	}

	/**
	 * @param string $parent = ''
	 *
	 * @return string
	 */
	public function ParentID ($parent = '') {
		if (func_num_args() != 0)
			$this->_parentID = $parent;

		return $this->_parentID;
	}

	/**
	 * @param bool $restricted = false
	 *
	 * @return bool
	 */
	public function Restricted ($restricted = false) {
		if (func_num_args() != 0)
			$this->_restricted = $restricted;

		return $this->_restricted;
	}

	/**
	 * @return UPnPProperty[]
	 */
	public function Properties () {
		return $this->_properties;
	}

	/**
	 * @param UPnPProperty $property = null
	 *
	 * @return DLNAElementContainer
	 */
	public function Property (UPnPProperty $property = null) {
		if ($property != null)
			$this->_properties[] = $property;

		return $this;
	}

	/**
	 * @param IQuarkDLNAElementResource $resource = null
	 *
	 * @return DLNAElementItem
	 */
	public function Resource (IQuarkDLNAElementResource $resource = null) {
		if ($resource != null) {
			$this->_properties[] = $resource->DLNAElementResource();

			$properties = $resource->DLNAElementResourceUPnPProperties();

			foreach ($properties as $property)
				$this->_properties[] = $property->XMLNode();
		}

		return $this;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function DLNAElement () {
		$properties = $this->_properties;
		$properties[] = new QuarkXMLNode(self::PROPERTY_TITLE, $this->_title);

		if (sizeof($this->_properties) == 0)
			$properties[] = (new UPnPProperty(
				self::PROPERTY_UPnP_CLASS,
				self::UPnP_CLASS_GENERIC
			))->XMLNode();

		return new QuarkXMLNode(self::ELEMENT, $properties, array(
			'id' => $this->_id,
			'parentID' => $this->_parentID,
			'restricted' => $this->_restricted ? 'true' : 'false'
		));
	}
}