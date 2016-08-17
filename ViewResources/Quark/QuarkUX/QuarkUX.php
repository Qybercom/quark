<?php
namespace Quark\ViewResources\Quark\QuarkUX;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;
use Quark\QuarkLocalCoreJSViewResource;

use Quark\ViewResources\jQuery\jQueryCore;
use Quark\ViewResources\Quark\QuarkIO\QuarkIO;

/**
 * Class QuarkUX
 *
 * @package Quark\ViewResources\Quark\QuarkUX
 */
class QuarkUX implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
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
		return __DIR__ . '/QuarkUX.js';
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new jQueryCore(),
			new QuarkLocalCoreJSViewResource(),
			new QuarkIO()
		);
	}
}