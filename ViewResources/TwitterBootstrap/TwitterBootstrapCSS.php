<?php
namespace Quark\ViewResources\TwitterBootstrap;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class TwitterBootstrapCSS
 *
 * @package Quark\ViewResources\TwitterBootstrap
 */
class TwitterBootstrapCSS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
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
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://maxcdn.bootstrapcdn.com/bootstrap/' . $this->_version . '/css/bootstrap.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}