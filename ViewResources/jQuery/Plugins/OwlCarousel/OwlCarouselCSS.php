<?php
namespace Quark\ViewResources\jQuery\Plugins\OwlCarousel;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class OwlCarouselCSS
 *
 * @package Quark\ViewResources\jQuery\Plugins\OwlCarousel
 */
class OwlCarouselCSS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
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
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/' . $this->_version . '/owl.carousel.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}