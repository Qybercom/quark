<?php
namespace Quark\ViewResources\Quark\ChatKit;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;
use Quark\ViewResources\MomentJS\MomentJS;
use Quark\ViewResources\Quark\QuarkMVC\QuarkMVC;
use Quark\ViewResources\Quark\QuarkNetwork\QuarkNetwork;
use Quark\ViewResources\Quark\QuarkUX\QuarkUX;

/**
 * Class ChatKitJS
 *
 * @package Quark\ViewResources\Quark\ChatKit
 */
class ChatKitJS implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
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
		return __DIR__ . '/ChatKit.js';
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
			new jQueryCore(),
			new MomentJS(),
			new QuarkNetwork(true),
			new QuarkUX(),
			new QuarkMVC()
		);
	}
}