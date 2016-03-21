<?php
namespace Quark\ViewResources\TwitterBootstrap;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class TwitterBootstrap
 *
 * @package Quark\ViewResources\TwitterBootstrap
 */
class TwitterBootstrap implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	const CURRENT_VERSION = '3.3.5';

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($version = self::CURRENT_VERSION) {
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		// TODO: Implement Type() method.
	}

	/**
	 * @return string
	 */
	public function Location () {
		// TODO: Implement Location() method.
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new TwitterBootstrapCSS($this->_version),
			new TwitterBootstrapJS($this->_version)
		);
	}
}