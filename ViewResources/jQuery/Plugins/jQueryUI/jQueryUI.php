<?php
namespace Quark\ViewResources\jQuery\Plugins\jQueryUI;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class jQueryUI
 *
 * @package Quark\ViewResources\jQuery\Plugins\jQueryUI
 */
class jQueryUI implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	const THEME_BASE = 'base';
	const THEME_BLACK_TIE = 'black-tie';
	const THEME_BLITZER = 'blitzer';
	const THEME_CUPERTINO = 'cupertino';
	const THEME_DARK_HIVE = 'dark-hive';
	const THEME_DOT_LUV = 'dot-luv';
	const THEME_EGGPLANT = 'eggplant';
	const THEME_EXCITE_BIKE = 'excite-bike';
	const THEME_FLICK = 'flick';
	const THEME_HOT_SNEAKS = 'hot-sneaks';
	const THEME_HUMANITY = 'humanity';
	const THEME_LE_FROG = 'le-frog';
	const THEME_MINT_CHOC = 'mint-choc';
	const THEME_OVERCAST = 'overcast';
	const THEME_PEPPER_GRINDER = 'pepper-grinder/';
	const THEME_REDMOND = 'redmond';
	const THEME_SMOOTHNESS = 'smoothness';
	const THEME_SOUTH_STREET = 'south-street';
	const THEME_START = 'start';
	const THEME_SUNNY = 'sunny';
	const THEME_SWANKY_PURSE = 'swanky-purse';
	const THEME_TRONTASTIC = 'trontastic';
	const THEME_UI_DARKNESS = 'ui-darkness';
	const THEME_UI_LIGHTNESS = 'ui-lightness';
	const THEME_VADER = 'vader';
	const THEME_NONE = '';

	const CURRENT_VERSION = '1.13.2';

	/**
	 * @var string $_theme = self::THEME_NONE
	 */
	private $_theme = self::THEME_NONE;

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @param string $theme = self::THEME_NONE
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($theme = self::THEME_NONE, $version = self::CURRENT_VERSION) {
		$this->_theme = $theme;
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		$out = array(
			new jQueryUIJS($this->_version)
		);

		if ($this->_theme != self::THEME_NONE) {
			$out[] = new jQueryUICSS($this->_theme, $this->_version);
			$out[] = new jQueryUITheme($this->_theme, $this->_version);
		}

		return $out;
	}
}