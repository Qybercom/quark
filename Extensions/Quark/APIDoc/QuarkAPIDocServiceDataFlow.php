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
	 * @var string $_sample = ''
	 */
	private $_sample = '';

	/**
	 * @param string $payload = ''
	 * @param string $uri = ''
	 * @param string $info = ''
	 * @param string $sample = ''
	 */
	public function __construct ($payload = '', $uri = '', $info = '', $sample = '') {
		$this->_payload = $payload;
		$this->_uri = $uri;
		$this->_info = $info;
		$this->_sample = $sample;
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
	
	/**
	 * @return string
	 */
	public function Sample () {
		return $this->_sample;
	}
}