<?php
namespace Quark\ViewResources\Quark\QuarkMedia;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;

/**
 * Class QuarkMedia
 *
 * @package Quark\ViewResources\Quark\QuarkMedia
 */
class QuarkMedia implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource {
	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}
	
	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/QuarkMedia.js';
	}
	
	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}
}