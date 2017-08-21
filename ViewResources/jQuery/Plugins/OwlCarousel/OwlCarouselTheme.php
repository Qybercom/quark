<?php
namespace Quark\ViewResources\jQuery\Plugins\OwlCarousel;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class OwlCarouselTheme
 *
 * @package Quark\ViewResources\jQuery\Plugins\OwlCarousel
 */
class OwlCarouselTheme implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const THEME_DEFAULT = 'default';
	const THEME_GREEN = 'green';

	/**
	 * @var string $_version = OwlCarousel::CURRENT_VERSION
	 */
	private $_version = OwlCarousel::CURRENT_VERSION;

	/**
	 * @var string $_theme = self::THEME_GREEN
	 */
	private $_theme = self::THEME_DEFAULT;

	/**
	 * @param string $version = OwlCarousel::CURRENT_VERSION
	 * @param string $theme = self::THEME_GREEN
	 */
	public function __construct ($version = OwlCarousel::CURRENT_VERSION, $theme = self::THEME_GREEN) {
		$this->_version = $version;
		$this->_theme = $theme;
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
		//return 'https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/' . $this->_version . '/owl.theme.min.css';
		return 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/' . $this->_version . '/assets/owl.theme.' . $this->_theme . '.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}