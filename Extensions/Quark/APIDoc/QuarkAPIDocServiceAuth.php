<?php
namespace Quark\Extensions\Quark\APIDoc;

/**
 * Class QuarkAPIDocServiceAuth
 *
 * @package Quark\Extensions\Quark\APIDoc
 */
class QuarkAPIDocServiceAuth {
	/**
	 * @var string $_provider = ''
	 */
	private $_provider = '';

	/**
	 * @var string $_criteria = ''
	 */
	private $_criteria = '';

	/**
	 * @var string $_failure = ''
	 */
	private $_failure = '';

	/**
	 * @param string $provider = ''
	 * @param string $criteria = ''
	 * @param string $failure = ''
	 */
	public function __construct ($provider = '', $criteria = '', $failure = '') {
		$this->_provider = $provider;
		$this->_criteria = $criteria;
		$this->_failure = $failure;
	}

	/**
	 * @return string
	 */
	public function Provider () {
		return $this->_provider;
	}

	/**
	 * @return string
	 */
	public function Criteria () {
		return $this->_criteria;
	}

	/**
	 * @return string
	 */
	public function Failure () {
		return $this->_failure;
	}
}