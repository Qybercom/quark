<?php
namespace Quark\ViewResources\Materialize;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class Materialize
 *
 * @package Quark\ViewResources\Materialize
 */
class Materialize implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	const CURRENT_VERSION = '0.97.6';

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
			new MaterializeCSS($this->_version),
			new MaterializeJS($this->_version)
		);
	}
}