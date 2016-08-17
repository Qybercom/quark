<?php
namespace Quark\ViewResources\Quark\QuarkControls;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkViewResourceType;

use Quark\ViewResources\Quark\QuarkUI;
use Quark\ViewResources\Quark\QuarkUX\QuarkUX;

/**
 * Class QuarkControls
 *
 * @package Quark\ViewResources\Quark\QuarkControls
 */
class QuarkControls implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
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
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new QuarkUX(),
			new QuarkControlsCSS(),
			new QuarkControlsJS()
		);
	}
}