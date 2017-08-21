<?php
namespace Quark\ViewResources\jQuery\Plugins\OwlCarousel;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class OwlCarouselJS
 *
 * @package Quark\ViewResources\jQuery\Plugins\OwlCarousel
 */
class OwlCarouselJS implements IQuarkSpecifiedViewResource, IQuarkViewResourceWithDependencies, IQuarkForeignViewResource {
	/**
	 * @var string $_version = OwlCarousel::CURRENT_VERSION
	 */
	private $_version = OwlCarousel::CURRENT_VERSION;

	/**
	 * @param string $version = OwlCarousel::CURRENT_VERSION
	 */
	public function __construct ($version = OwlCarousel::CURRENT_VERSION) {
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
		//return 'https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/' . $this->_version . '/owl.carousel.min.js';
		return 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/' . $this->_version . '/owl.carousel.min.js';
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