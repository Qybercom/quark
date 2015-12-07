<?php
namespace Quark\ViewResources\Quark\CSS;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkCSSViewResourceType;

/**
 * Class QuarkPresenceControl
 *
 * @package Quark\ViewResources\Quark\CSS
 */
class QuarkPresenceControl implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
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
		return __DIR__ . '/QuarkPresenceControl.css';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new QuarkPresence()
		);
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}
}