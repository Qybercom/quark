<?php
namespace Quark\ViewResources\jQuery\Plugins\AirDatepicker;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class AirDatepickerLanguage
 *
 * @package Quark\ViewResources\jQuery\Plugins\AirDatepicker
 */
class AirDatepickerLanguage implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const LANG_DEFAULT = '';
	const LANG_CS = 'cs';
	const LANG_DA = 'da';
	const LANG_DE = 'de';
	const LANG_EN = 'en';
	const LANG_ES = 'es';
	const LANG_FI = 'fi';
	const LANG_FR = 'fr';
	const LANG_HU = 'hu';
	const LANG_NL = 'nl';
	const LANG_PL = 'pl';
	const LANG_PT = 'pt';
	const LANG_PT_BR = 'pt-BR';
	const LANG_RO = 'ro';
	const LANG_RU = 'ru';
	const LANG_SK = 'sk';
	const LANG_SH = 'zh';

	/**
	 * @var string $_name = self::LANG_DEFAULT
	 */
	private $_name = self::LANG_DEFAULT;

	/**
	 * @var string $_version = AirDatepicker::CURRENT_VERSION
	 */
	private $_version = AirDatepicker::CURRENT_VERSION;

	/**
	 * @param string $name = self::LANG_DEFAULT
	 * @param string $version = ImagePicker::CURRENT_VERSION
	 */
	public function __construct ($name = self::LANG_DEFAULT, $version = AirDatepicker::CURRENT_VERSION) {
		$this->_name = $name;
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/' . $this->_version . '/js/i18n/datepicker.' . $this->_name . '.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}