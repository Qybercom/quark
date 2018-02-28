<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\Elements;

use Quark\QuarkXMLNode;

use Quark\Extensions\UPnP\UPnPProperty;
use Quark\Extensions\UPnP\Providers\DLNA\IQuarkDLNAElement;

/**
 * Class DLNAElementContainer
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\Elements
 */
class DLNAElementContainer implements IQuarkDLNAElement {
	const ELEMENT = 'container';

	const PROPERTY_TITLE = 'dc:title';
	const PROPERTY_UPnP_CLASS = 'class';

	const UPnP_CLASS_GENERIC = 'object.container';
	const UPnP_CLASS_STORAGE = 'object.container.storageFolder';

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
	 * @var int $_childCount = 0
	 */
	private $_childCount = 0;

	/**
	 * @var bool $_restricted = false
	 */
	private $_restricted = false;

	/**
	 * @var bool $_searchable = true
	 */
	private $_searchable = true;

	/**
	 * @var UPnPProperty[] $_properties
	 */
	private $_properties = array();

	/**
	 * @param string $id = ''
	 * @param string $title = ''
	 * @param string $parentID = ''
	 * @param int $childCount = 0
	 * @param bool $restricted = false
	 * @param bool $searchable = true
	 */
	public function __construct ($id = '', $title = '', $parentID = '', $childCount = 0, $restricted = false, $searchable = true) {
		$this->ID($id);
		$this->Title($title);
		$this->ParentID($parentID);
		$this->ChildCount($childCount);
		$this->Restricted($restricted);
		$this->Searchable($searchable);
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
	 * @param int $count = 0
	 *
	 * @return int
	 */
	public function ChildCount ($count = 0) {
		if (func_num_args() != 0)
			$this->_childCount = $count;

		return $this->_childCount;
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
	 * @param bool $searchable = false
	 *
	 * @return bool
	 */
	public function Searchable ($searchable = false) {
		if (func_num_args() != 0)
			$this->_searchable = $searchable;

		return $this->_searchable;
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
	public function UPnP (UPnPProperty $property = null) {
		if ($property != null)
			$this->_properties[] = $property;

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
				self::UPnP_CLASS_STORAGE
			))->XMLNode();

		return new QuarkXMLNode(self::ELEMENT, $properties, array(
			'id' => $this->_id,
			'parentID' => $this->_parentID,
			'childCount' => $this->_childCount,
			'restricted' => $this->_restricted ? 'true' : 'false',
			'searchable' => $this->_searchable ? 'true' : 'false'
		));
	}
}