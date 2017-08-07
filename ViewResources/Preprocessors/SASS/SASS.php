<?php
namespace Quark\ViewResources\Preprocessors\SASS;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkMinimizableViewResourceBehavior;

/**
 * Class SASS
 *
 * @package Quark\ViewResources\Preprocessors\SASS
 */
class SASS implements IQuarkViewResource, IQuarkInlineViewResource {
	use QuarkMinimizableViewResourceBehavior;

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		// TODO: Implement Type() method.
	}

	/**
	 * @return string
	 */
	public function Location () {
		// TODO: Implement Location() method.
	}

	/**
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML ($minimize) {
		// TODO: Implement HTML() method.
	}
}