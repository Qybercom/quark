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
	const CURRENT_VERSION = '1.0.5';

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;
	
	/**
	 * @var bool $_minified = true
	 */
	private $_minified = true;

	/**
	 * @param string $version = self::CURRENT_VERSION
	 * @param bool $minified = true
	 */
	public function __construct ($version = self::CURRENT_VERSION, $minified = true) {
		$this->_version = $version;
		$this->_minified = $minified;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new FlowprintCSS($this->_version, $this->_minified),
			new FlowprintJS($this->_version, $this->_minified)
		);
	}
}