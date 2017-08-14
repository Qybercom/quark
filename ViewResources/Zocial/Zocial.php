<?php
namespace Quark\ViewResources\Zocial;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class Zocial
 *
 * @package Quark\ViewResources\Zocial
 */
class Zocial implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	const VERSION = '1.3.0';

	/**
	 * @var string $_version = self::VERSION
	 */
	private $_version = self::VERSION;

	/**
	 * @param string $version = self::VERSION
	 */
	public function __construct ($version = self::VERSION) {
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new ZocialLib($this->_version),
			new ZocialIcons()
		);
	}
}