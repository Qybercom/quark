<?php
namespace Quark\ViewResources\TwitterBootstrap;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class TwitterBootstrapJS
 *
 * @package Quark\ViewResources\TwitterBootstrap
 */
class TwitterBootstrapJS implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	private $_version = '';

	/**
	 * @param string $version = TwitterBootstrap::CURRENT_VERSION
	 */
	public function __construct ($version = TwitterBootstrap::CURRENT_VERSION) {
		$this->_version = $version;
	}

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
		return 'https://maxcdn.bootstrapcdn.com/bootstrap/' . $this->_version . '/js/bootstrap.min.js';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new jQueryCore()
		);
	}
}