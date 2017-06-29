<?php
namespace Quark\ViewResources\Quark\QuarkPresence;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkCSSViewResourceType;

use Quark\ViewResources\Quark\QuarkControls\QuarkControlsCSS;

/**
 * Class QuarkPresenceControlsCSS
 *
 * @package Quark\ViewResources\Quark\QuarkPresence
 */
class QuarkPresenceControlsCSS implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
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
		return __DIR__ . '/QuarkPresenceControls.css';
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
			new QuarkControlsCSS()
		);
	}
}