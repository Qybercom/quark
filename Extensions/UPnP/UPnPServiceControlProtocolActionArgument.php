<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkXMLNode;

/**
 * Class UPnPServiceControlProtocolActionArgument
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPServiceControlProtocolActionArgument {
	const DIRECTION_IN = 'in';
	const DIRECTION_OUT = 'out';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_direction = ''
	 */
	private $_direction = '';

	/**
	 * @var UPnPServiceControlProtocolVariable $_variable
	 */
	private $_variable;

	/**
	 * @param string $name = ''
	 * @param string $direction = ''
	 * @param UPnPServiceControlProtocolVariable $variable = null
	 */
	public function __construct ($name = '', $direction = '', UPnPServiceControlProtocolVariable $variable = null) {
		$this->Name($name);
		$this->Direction($direction);
		$this->Variable($variable);
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
	 * @param string $direction = ''
	 *
	 * @return string
	 */
	public function Direction ($direction = '') {
		if (func_num_args() != 0)
			$this->_direction = $direction;

		return $this->_direction;
	}

	/**
	 * @param UPnPServiceControlProtocolVariable $variable = null
	 *
	 * @return UPnPServiceControlProtocolVariable
	 */
	public function Variable (UPnPServiceControlProtocolVariable $variable = null) {
		if (func_num_args() != 0)
			$this->_variable = $variable;

		return $this->_variable;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function ToXML () {
		return new QuarkXMLNode('argument', array(
			'name' => $this->_name,
			'direction' => $this->_direction,
			'relatedStateVariable' => $this->_variable->Name()
		));
	}

	/**
	 * @param string $name = ''
	 * @param UPnPServiceControlProtocolVariable $variable = null
	 *
	 * @return UPnPServiceControlProtocolActionArgument
	 */
	public static function In ($name = '', UPnPServiceControlProtocolVariable $variable = null) {
		return new self($name, self::DIRECTION_IN, $variable);
	}

	/**
	 * @param string $name = ''
	 * @param UPnPServiceControlProtocolVariable $variable = null
	 *
	 * @return UPnPServiceControlProtocolActionArgument
	 */
	public static function Out ($name = '', UPnPServiceControlProtocolVariable $variable = null) {
		return new self($name, self::DIRECTION_OUT, $variable);
	}
}