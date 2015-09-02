<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

/**
 * Class QuarkFavicon
 *
 * @package Quark\ViewResources\Quark
 */
class QuarkFavicon implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkInlineViewResource {
	public function __construct ($location) {

	}

	/**
	 * @return string
	 */
	public function Location () {
		// TODO: Implement Location() method.
	}

	/**
	 * @return IQuarkViewResourceType;
	 */
	public function Type () {
		// TODO: Implement Type() method.
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}

	/**
	 * @return string
	 */
	public function HTML () {
		// TODO: Implement HTML() method.
	}
}