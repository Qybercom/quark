<?php
namespace Quark\ViewResources\Quark\JS;

use Quark\IQuarkViewResource;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;
use Quark\QuarkLocalCoreJSViewResource;

/**
 * Class QuarkGX
 *
 * @package Quark\ViewResources\Quark\JS
 */
class QuarkGX implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies{
	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/QuarkGX.js';
	}
	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}

	/**
	 * @return array
	 */
	public function Dependencies () {
		return array(
			new jQueryCore(),
			new QuarkLocalCoreJSViewResource(),
			new QuarkIO(),
			new QuarkUX()
		);
	}
}