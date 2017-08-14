<?php
namespace Quark\ViewResources\Zocial;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkMinimizableViewResourceBehavior;

/**
 * Class ZocialIcons
 *
 * @package Quark\ViewResources\Zocial
 */
class ZocialIcons implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource {
	use QuarkMinimizableViewResourceBehavior;

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/ZocialIcons.css';
	}
}