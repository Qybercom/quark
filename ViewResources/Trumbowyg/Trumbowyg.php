<?php
namespace Quark\ViewResources\Trumbowyg;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class Trumbowyg
 *
 * @package Quark\ViewResources\Trumbowyg
 */
class Trumbowyg implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	const CURRENT_VERSION = '2.4.1';

	/**
	 * @var string $_lang = TumbowygLanguage::LANG_DEFAULT
	 */
	private $_lang = TrumbowygLanguage::LANG_DEFAULT;

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @param string $language = TumbowygLanguage::LANG_DEFAULT
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($language = TrumbowygLanguage::LANG_DEFAULT, $version = self::CURRENT_VERSION) {
		$this->_lang = $language;
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new jQueryCore(),
			new TrumbowygCSS($this->_version),
			new TrumbowygJS($this->_version),
			$this->_lang != TrumbowygLanguage::LANG_DEFAULT ? new TrumbowygLanguage($this->_lang) : null
		);
	}
}