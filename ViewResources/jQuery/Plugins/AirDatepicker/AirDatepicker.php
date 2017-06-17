<?php
namespace Quark\ViewResources\jQuery\Plugins\AirDatepicker;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class AirDatepicker
 *
 * @package Quark\ViewResources\jQuery\Plugins\AirDatepicker
 */
class AirDatepicker implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	const CURRENT_VERSION = '2.2.3';

	/**
	 * @var string $_lang = AirDatepickerLanguage::LANG_DEFAULT
	 */
	private $_language = AirDatepickerLanguage::LANG_DEFAULT;

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @param string $language = AirDatepickerLanguage::LANG_DEFAULT
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($language = AirDatepickerLanguage::LANG_DEFAULT, $version = self::CURRENT_VERSION) {
		$this->_language = $language;
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new AirDatepickerCSS($this->_version),
			new AirDatepickerJS($this->_version),
			$this->_language != AirDatepickerLanguage::LANG_DEFAULT ? new AirDatepickerLanguage($this->_language) : null
		);
	}
}