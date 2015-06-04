<?php
namespace Quark\ViewResources\MomentJS;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;

/**
 * Class MomentJSTimezoneData
 *
 * @package Quark\ViewResources\MomentJS
 */
class MomentJSTimezoneData implements IQuarkViewResource, IQuarkLocalViewResource {
	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/JS/moment-timezone-with-data.js';
	}

	/**
	 * @return IQuarkViewResourceType;
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return false;
	}
}