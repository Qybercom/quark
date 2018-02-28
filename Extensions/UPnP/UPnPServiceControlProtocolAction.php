<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkObject;
use Quark\QuarkXMLNode;

/**
 * Class UPnPServiceControlProtocolAction
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPServiceControlProtocolAction {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var UPnPServiceControlProtocolActionArgument[] $_arguments = []
	 */
	private $_arguments = array();

	/**
	 * @param string $name = ''
	 */
	public function __construct ($name = '') {
		$this->Name($name);
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
	 * @param UPnPServiceControlProtocolActionArgument[] $arguments = []
	 *
	 * @return UPnPServiceControlProtocolActionArgument[]
	 */
	public function Arguments ($arguments = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($arguments, new UPnPServiceControlProtocolActionArgument()))
			$this->_arguments = $arguments;

		return $this->_arguments;
	}

	/**
	 * @param string $name = ''
	 * @param UPnPServiceControlProtocolVariable $variable = null
	 *
	 * @return UPnPServiceControlProtocolAction
	 */
	public function ArgumentIn ($name = '', UPnPServiceControlProtocolVariable $variable = null) {
		if (func_num_args() != 0)
			$this->_arguments[] = UPnPServiceControlProtocolActionArgument::In($name, $variable);

		return $this;
	}

	/**
	 * @param string $name = ''
	 * @param UPnPServiceControlProtocolVariable $variable = null
	 *
	 * @return UPnPServiceControlProtocolAction
	 */
	public function ArgumentOut ($name = '', UPnPServiceControlProtocolVariable $variable = null) {
		if (func_num_args() != 0)
			$this->_arguments[] = UPnPServiceControlProtocolActionArgument::Out($name, $variable);

		return $this;
	}

	/**
	 * @return UPnPServiceControlProtocolVariable[]
	 */
	public function Variables () {
		$variables = array();

		foreach ($this->_arguments as $i => &$argument) {
			$variable = $argument->Variable();

			if (!isset($variables[$variable->Name()]))
				$variables[$variable->Name()] = $variable;
		}

		return $variables;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function ToXML () {
		$arguments = array();

		foreach ($this->_arguments as $i => &$argument)
			$arguments[] = $argument->ToXML();

		return new QuarkXMLNode('action', array(
			'name' => $this->_name,
			'argumentList' => $arguments
		));
	}

	/**
	 * @param string $name = ''
	 *
	 * @return UPnPServiceControlProtocolAction
	 */
	public static function WithArguments ($name = '') {
		return new self($name);
	}
}