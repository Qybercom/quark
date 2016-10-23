<?php
namespace Quark\ViewResources\Quark\QuarkControls;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\ViewResources\Quark\QuarkUX\QuarkUX;

/**
 * Class QuarkControls
 *
 * @package Quark\ViewResources\Quark\QuarkControls
 */
class QuarkControls implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
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