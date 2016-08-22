<?php
namespace Quark\ViewResources\Quark\QuarkPresence;

use Quark\IQuarkLocalViewResource;
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
class QuarkPresenceControlsCSS implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
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
	public function CacheControl () {
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