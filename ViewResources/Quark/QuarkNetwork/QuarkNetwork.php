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
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/QuarkNetwork.js';
	}

	/**
	 * @return bool
	 */
	public function Minimize () {
		return true;
	}
}