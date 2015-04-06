<?php
namespace Quark\ViewResources\Quark\CSS;

use Quark\IQuarkViewResource;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkCSSViewResourceType;

use Quark\ViewResources\Google\GoogleFont;
use Quark\ViewResources\Quark\QuarkUI;

/**
 * Class QuarkThinkscape
 *
 * @package Quark\ViewResources\Quark\CSS
 */
class QuarkThinkscape implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @return string
	 */
	public function Type () {
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/QuarkThinkscape.css';
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
			new QuarkUI(),
			new GoogleFont('Anonymous Pro')
		);
	}
}