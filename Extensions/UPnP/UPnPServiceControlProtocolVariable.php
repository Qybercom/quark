<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkObject;
use Quark\QuarkXMLNode;

/**
 * Class UPnPServiceControlProtocolVariable
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPServiceControlProtocolVariable {
	const DATA_TYPE_STRING = 'string';
	const DATA_TYPE_UNSIGNED_INT4 = 'ui4';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_dataType = self::DATA_TYPE_STRING
	 */
	private $_dataType = self::DATA_TYPE_STRING;

	/**
	 * @var string $_defaultValue = null
	 */
	private $_defaultValue = null;

	/**
	 * @var string[] $_allowedValues = []
	 */
	private $_allowedValues = array();

	/**
	 * @var bool $_events = false
	 */
	private $_events = false;

	/**
	 * @param string $name = ''
	 * @param string $dataType = self::DATA_TYPE_STRING
	 * @param string $defaultValue = null
	 * @param string[] $allowedValues = []
	 * @param bool $events = false
	 */
	public function __construct ($name = '', $dataType = self::DATA_TYPE_STRING, $defaultValue = null, $allowedValues = [], $events = false) {
		$this->Name($name);
		$this->DataType($dataType);
		$this->DefaultValue($defaultValue);
		$this->AllowedValues($allowedValues);
		$this->Events($events);
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}

	/**
	 * @param string $dataType = self::DATA_TYPE_STRING
	 *
	 * @return string
	 */
	public function DataType ($dataType = self::DATA_TYPE_STRING) {
		if (func_num_args() != 0)
			$this->_dataType = $dataType;

		return $this->_dataType;
	}

	/**
	 * @param string $defaultValue = null
	 *
	 * @return string
	 */
	public function DefaultValue ($defaultValue = null) {
		if (func_num_args() != 0)
			$this->_defaultValue = $defaultValue;

		return $this->_defaultValue;
	}

	/**
	 * @param string[] $values = []
	 *
	 * @return string[]
	 */
	public function AllowedValues ($values = []) {
		if (func_num_args() != 0 && QuarkObject::isIterative($values))
			$this->_allowedValues = $values;

		return $this->_allowedValues;
	}

	/**
	 * @param bool $events = false
	 *
	 * @return bool
	 */
	public function Events ($events = false) {
		if (func_num_args() != 0)
			$this->_events = $events;

		return $this->_events;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function ToXML () {
		$data = array(
			'name' => $this->_name,
			'dataType' => $this->_dataType
		);

		if ($this->_defaultValue !== null)
			$data['defaultValue'] = new QuarkXMLNode('defaultValue', $this->_defaultValue);

		if (sizeof($this->_allowedValues) != 0) {
			$data['allowedValueList'] = array();

			foreach ($this->_allowedValues as $i => &$value)
				$data['allowedValueList'][] = new QuarkXMLNode('allowedValue', $value);
		}

		return new QuarkXMLNode('stateVariable', $data, array(
			'sendEvents' => $this->_events ? 'yes' : 'no'
		));
	}

	/**
	 * @param string $name = ''
	 * @param string $dataType = self::DATA_TYPE_STRING
	 * @param string $defaultValue = null
	 * @param string[] $allowedValues = []
	 *
	 * @return UPnPServiceControlProtocolVariable
	 */
	public static function Eventable ($name = '', $dataType = self::DATA_TYPE_STRING, $defaultValue = null, $allowedValues = []) {
		return new self($name, $dataType, $defaultValue, $allowedValues, true);
	}
}