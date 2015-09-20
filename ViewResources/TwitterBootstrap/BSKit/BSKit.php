<?php
namespace Quark\ViewResources\TwitterBootstrap\BSKit;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkCSSViewResourceType;
use Quark\ViewResources\TwitterBootstrap\Plugins\TBSHoverDropdown;
use Quark\ViewResources\TwitterBootstrap\TwitterBootstrap;

/**
 * Class BSKit
 *
 * @package Quark\ViewResources\TwitterBootstrap\BSKit
 */
class BSKit implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @var string $_location
	 */
	private $_location;

	/**
	 * @param $location
	 */
	public function __construct ($location) {
		$this->_location = $location;
	}

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
		return $this->_location;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new TwitterBootstrap(),
			new TBSHoverDropdown()
		);
	}
}