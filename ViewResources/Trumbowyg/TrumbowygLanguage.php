<?php
namespace Quark\ViewResources\Trumbowyg;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class TrumbowygLanguage
 *
 * @package Quark\ViewResources\Trumbowyg
 */
class TrumbowygLanguage implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const LANG_DEFAULT = '';
	const LANG_HU = 'hu';
	const LANG_ID = 'id';
	const LANG_IT = 'it';
	const LANG_JA = 'ja';
	const LANG_KO = 'ko';
	const LANG_MY = 'my';
	const LANG_NL = 'nl';
	const LANG_NO_NB = 'no_nb';
	const LANG_PH = 'ph';
	const LANG_PL = 'pl';
	const LANG_PT = 'pt';
	const LANG_RO = 'ro';
	const LANG_RS = 'rs';
	const LANG_RS_LATIN = 'rs_latin';
	const LANG_RU = 'ru';
	const LANG_SK = 'sk';
	const LANG_SV = 'sv';
	const LANG_TR = 'tr';
	const LANG_UA = 'ua';
	const LANG_VI = 'vi';
	const LANG_ZH_CN = 'zh_cn';
	const LANG_ZH_TW = 'zh_tw';

	/**
	 * @var string $_name = self::LANG_DEFAULT
	 */
	private $_name = self::LANG_DEFAULT;

	/**
	 * @var string $_version = Trumbowyg::CURRENT_VERSION
	 */
	private $_version = Trumbowyg::CURRENT_VERSION;

	/**
	 * @param string $name = self::LANG_DEFAULT
	 * @param string $version = Trumbowyg::CURRENT_VERSION
	 */
	public function __construct ($name = self::LANG_DEFAULT, $version = Trumbowyg::CURRENT_VERSION) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/' . $this->_version . '/langs/' . $this->_name . '.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}