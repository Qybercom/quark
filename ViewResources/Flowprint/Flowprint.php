<?php
namespace Quark\ViewResources\Flowprint;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class Flowprint
 *
 * @package Quark\ViewResources\Flowprint
 */
class Flowprint implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	const CURRENT_VERSION = '1.0.2';

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
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new FlowprintCSS($this->_version),
			new FlowprintJS($this->_version)
		);
	}
}