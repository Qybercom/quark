<?php
namespace Quark\Extensions\Quark\APIDoc;

/**
 * Class QuarkAPIDocServiceMethod
 *
 * @package Quark\Extensions\Quark\APIDoc
 */
class QuarkAPIDocServiceMethod {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_description = ''
	 */
	private $_description = '';

	/**
	 * @var QuarkAPIDocServiceAuth $_auth
	 */
	private $_auth;

	/**
	 * @var QuarkAPIDocServiceDataFlow $_request
	 */
	private $_request;

	/**
	 * @var QuarkAPIDocServiceDataFlow $_response
	 */
	private $_response;

	/**
	 * @var QuarkAPIDocServiceDataFlow $_event
	 */
	private $_event;

	/**
	 * @param string $name = ''
	 * @param string $description = ''
	 * @param QuarkAPIDocServiceAuth $auth = null
	 * @param QuarkAPIDocServiceDataFlow $request
	 * @param QuarkAPIDocServiceDataFlow $response
	 * @param QuarkAPIDocServiceDataFlow $event
	 */
	public function __construct ($name = '', $description = '', QuarkAPIDocServiceAuth $auth = null, QuarkAPIDocServiceDataFlow $request = null, QuarkAPIDocServiceDataFlow $response = null, QuarkAPIDocServiceDataFlow $event = null) {
		$this->_name = $name;
		$this->_description = $description;
		$this->_auth = $auth;
		$this->_request = $request;
		$this->_response = $response;
		$this->_event = $event;
	}

	/**
	 * @return string
	 */
	public function Name () {
		return $this->_name;
	}

	/**
	 * @return string
	 */
	public function Description () {
		return $this->_description;
	}

	/**
	 * @return QuarkAPIDocServiceAuth
	 */
	public function Auth () {
		return $this->_auth;
	}

	/**
	 * @return QuarkAPIDocServiceDataFlow
	 */
	public function Request () {
		return $this->_request;
	}

	/**
	 * @return QuarkAPIDocServiceDataFlow
	 */
	public function Response () {
		return $this->_response;
	}

	/**
	 * @return QuarkAPIDocServiceDataFlow
	 */
	public function Event () {
		return $this->_event;
	}
}