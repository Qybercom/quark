<?php
namespace Quark\ViewResources\Quark\QuarkNetwork;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;

/**
 * Class QuarkNetwork
 *
 * @package Quark\ViewResources\Quark\QuarkNetwork
 */
class QuarkNetwork implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource {
	/**
	 * @var bool $_legacy = false
	 */
	private $_legacy = false;

	/**
	 * @param bool $legacy = false
	 */
	public function __construct ($legacy = false) {
		$this->_legacy = $legacy;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/QuarkNetwork' . ($this->_legacy ? 'Legacy' : '') . '.js';
	}

	/**
	 * @return bool
	 */
	public function Minimize () {
		return true;
	}
}