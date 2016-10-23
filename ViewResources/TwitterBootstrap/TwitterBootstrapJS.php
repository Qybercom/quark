<?php
namespace Quark\ViewResources\TwitterBootstrap;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class TwitterBootstrapJS
 *
 * @package Quark\ViewResources\TwitterBootstrap
 */
class TwitterBootstrapJS implements IQuarkSpecifiedViewResource, IQuarkViewResourceWithDependencies, IQuarkForeignViewResource {
	/**
	 * @var string $_version = TwitterBootstrap::CURRENT_VERSION
	 */
	private $_version = TwitterBootstrap::CURRENT_VERSION;

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

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}