<?php
namespace Quark\ViewResources\jQuery\Plugins\OwlCarousel;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class OwlCarousel
 *
 * @package Quark\ViewResources\jQuery\Plugins\OwlCarousel
 */
class OwlCarousel implements IQuarkViewResource, IQuarkViewResourceWithDependencies{
	//const CURRENT_VERSION = '1.32';
	//const CURRENT_VERSION = '2.2.1';
	const CURRENT_VERSION = '2.3.4';

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
			new OwlCarouselCSS($this->_version),
			new OwlCarouselTheme($this->_version),
			//new OwlCarouselTransitions($this->_version),
			new OwlCarouselJS($this->_version)
		);
	}
}