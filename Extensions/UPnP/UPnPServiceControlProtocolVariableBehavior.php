<?php
namespace Quark\Extensions\UPnP;

/**
 * Class UPnPServiceControlProtocolVariableBehavior
 *
 * @package Quark\Extensions\UPnP
 */
trait UPnPServiceControlProtocolVariableBehavior {
	/**
	 * @var string $_urlDescription = ''
	 */
	private $_urlDescription = '';

	/**
	 * @var string $_urlControl = ''
	 */
	private $_urlControl = '';

	/**
	 * @var string $_urlEvent = ''
	 */
	private $_urlEvent = '';

	/**
	 * @var array $_variables = []
	 */
	private $_variables = array();

	/**
	 * @param string $description
	 * @param string $control
	 * @param string $event
	 *
	 * @return mixed
	 */
	public function UPnPServiceURLs ($description, $control, $event) {
		$this->_urlDescription = $description;
		$this->_urlControl = $control;
		$this->_urlEvent = $event;
	}

	/**
	 * @param string $name = ''
	 * @param string $dataType = UPnPServiceControlProtocolVariable::DATA_TYPE_STRING
	 * @param string $defaultValue = null
	 * @param string[] $allowedValues = []
	 * @param bool $events = false
	 *
	 * @return UPnPServiceControlProtocolVariable
	 */
	public function UPnPServiceVariable ($name = '', $dataType = UPnPServiceControlProtocolVariable::DATA_TYPE_STRING, $defaultValue = null, $allowedValues = [], $events = false) {
		if (func_num_args() > 1)
			$this->_variables[$name] = new UPnPServiceControlProtocolVariable($name, $dataType, $defaultValue, $allowedValues, $events);

		return isset($this->_variables[$name]) ? $this->_variables[$name] : null;
	}

	/**
	 * @return UPnPServiceControlProtocolVariable[]
	 */
	public function UPnPServiceVariableList () {
		return $this->_variables;
	}
}