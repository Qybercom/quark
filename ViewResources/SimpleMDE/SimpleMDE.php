<?php
namespace Quark\ViewResources\SimpleMDE;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class SimpleMDE
 *
 * @package Quark\ViewResources\SimpleMDE
 */
class SimpleMDE implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	const CURRENT_VERSION = '1.11.2';

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
			new SimpleMDECSS($this->_version),
			new SimpleMDEJS($this->_version)
		);
	}
}