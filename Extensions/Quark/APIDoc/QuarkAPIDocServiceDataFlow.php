<?php
namespace Quark\Extensions\Quark\APIDoc;

/**
 * Class QuarkAPIDocServiceDataFlow
 *
 * @package Quark\Extensions\Quark\APIDoc
 */
class QuarkAPIDocServiceDataFlow {
	/**
	 * @var string $_payload = ''
	 */
	private $_payload = '';

	/**
	 * @var string $_uri = ''
	 */
	private $_uri = '';

	/**
	 * @var string $_info = ''
	 */
	private $_info = '';

	/**
	 * QuarkAPIDocServiceDataFlow constructor.
	 *
	 * @param string $payload = ''
	 * @param string $uri = ''
	 * @param string $info = ''
	 */
	public function __construct ($payload = '', $uri = '', $info = '') {
		$this->_payload = $payload;
		$this->_uri = $uri;
		$this->_info = $info;
	}

	/**
	 * @return string
	 */
	public function Payload () {
		return $this->_payload;
	}

	/**
	 * @return string
	 */
	public function URI () {
		return $this->_uri;
	}

	/**
	 * @return string
	 */
	public function Info () {
		return $this->_info;
	}
}