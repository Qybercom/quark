<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkXMLNode;

/**
 * Class UPnPProperty
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPProperty {
	const NAMESPACE_PREFIX = 'upnp';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_value = ''
	 */
	private $_value = '';

	/**
	 * @var array|object $_attributes = []
	 */
	private $_attributes = array();

	/**
	 * @param string $name = ''
	 * @param string $value = ''
	 * @param array|object $attributes = []
	 * @param bool $namespace = true
	 */
	public function __construct ($name = '', $value = '', $attributes = [], $namespace = true) {
		$this->Name($name, $namespace);
		$this->Value($value);
		$this->Attributes($attributes);
	}

	/**
	 * @param string $name = ''
	 * @param bool $namespace = true
	 *
	 * @return string
	 */
	public function Name ($name = '', $namespace = true) {
		if (func_num_args() != 0)
			$this->_name = ($namespace ? self::NAMESPACE_PREFIX . ':' : '') . $name;

		return $this->_name;
	}

	/**
	 * @param string $value = ''
	 *
	 * @return string
	 */
	public function Value ($value = '') {
		if (func_num_args() != 0)
			$this->_value = $value;

		return $this->_value;
	}

	/**
	 * @param array|object $attributes = []
	 *
	 * @return array|object
	 */
	public function Attributes ($attributes = []) {
		if (func_num_args() != 0)
			$this->_attributes = $attributes;

		return $this->_attributes;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function XMLNode () {
		return new QuarkXMLNode($this->_name, $this->_value, $this->_attributes);
	}
}