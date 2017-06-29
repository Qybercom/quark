<?php
namespace Quark\ViewResources\Quark\QuarkControls;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkLocalViewResource;

use Quark\QuarkCSSViewResourceType;

/**
 * Class QuarkControlsCSS
 *
 * @package Quark\ViewResources\Quark\QuarkControls
 */
class QuarkControlsCSS implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource {
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
		return __DIR__ . '/QuarkControls.css';
	}

	/**
	 * @return bool
	 */
	public function Minimize () {
		return true;
	}
}