<?php
namespace Quark\ViewResources\Quark\QuarkNetwork;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;

/**
 * Class QuarkNetwork
 *
 * @package Quark\ViewResources\Quark\QuarkNetwork
 */
class QuarkNetwork implements IQuarkViewResource, IQuarkLocalViewResource {
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
	public function CacheControl () {
		return true;
	}
}