<?php
namespace Quark\ViewResources\Quark\QuarkGX;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;

use Quark\QuarkLocalCoreJSViewResource;
use Quark\ViewResources\jQuery\jQueryCore;
use Quark\ViewResources\Quark\QuarkIO\QuarkIO;
use Quark\ViewResources\Quark\QuarkUX\QuarkUX;

/**
 * Class QuarkGX
 *
 * @package Quark\ViewResources\Quark\QuarkGX
 */
class QuarkGX implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies{
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
	public function Minimize () {
		return true;
	}

	/**
	 * @return IQuarkViewResource[]
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