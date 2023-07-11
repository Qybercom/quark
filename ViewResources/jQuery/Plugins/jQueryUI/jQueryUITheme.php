<?php
namespace Quark\ViewResources\jQuery\Plugins\jQueryUI;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkCSSViewResourceType;

/**
 * Class jQueryUITheme
 *
 * @package Quark\ViewResources\jQuery\Plugins\jQueryUI
 */
class jQueryUITheme implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	/**
	 * @var string $_theme = jQueryUI::THEME_NONE
	 */
	private $_theme = jQueryUI::THEME_NONE;

	/**
	 * @var string $_version = jQueryUI::CURRENT_VERSION
	 */
	private $_version = jQueryUI::CURRENT_VERSION;

	/**
	 * @param string $theme = jQueryUI::THEME_NONE
	 * @param string $version = jQueryUI::CURRENT_VERSION
	 */
	public function __construct ($theme = jQueryUI::THEME_NONE, $version = jQueryUI::CURRENT_VERSION) {
		$this->_theme = $theme;
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/' . $this->_version . '/themes/' . $this->_theme . '/theme.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}