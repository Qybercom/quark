<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkMinimizableViewResourceBehavior;

/**
 * Class QuarkFavicon
 *
 * @package Quark\ViewResources\Quark
 */
class QuarkFavicon implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkInlineViewResource {
	use QuarkMinimizableViewResourceBehavior;

	public function __construct ($location) {

	}

	/**
	 * @return string
	 */
	public function Location () {
		// TODO: Implement Location() method.
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		// TODO: Implement Type() method.
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