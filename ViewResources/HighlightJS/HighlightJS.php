<?php
namespace Quark\ViewResources\HighlightJS;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithBackwardDependencies;

use Quark\QuarkDTO;
use Quark\QuarkInlineJSViewResource;
use Quark\QuarkJSViewResourceType;

/**
 * Class HighlightJS
 *
 * @package Quark\ViewResources\HighlightJS
 */
class HighlightJS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource, IQuarkViewResourceWithBackwardDependencies {
	const CURRENT_VERSION = '9.3.0';

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @var string $_style = self::STYLE_GITHUB
	 */
	private $_style = HighlightJSStyle::STYLE_GITHUB;

	/**
	 * @var bool $_autoInit = false
	 */
	private $_autoInit = false;

	/**
	 * @var string[] $_languages = []
	 */
	private $_languages = array();

	/**
	 * @param string $style = HighlightJSStyle::STYLE_GITHUB
	 * @param string|string[] $languages = []
	 * @param bool $autoInit = false
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($style = HighlightJSStyle::STYLE_GITHUB, $languages = [], $autoInit = false, $version = self::CURRENT_VERSION) {
		$this->_style = $style;
		$this->_languages = is_array($languages) ? $languages : array($languages);
		$this->_autoInit = $autoInit;
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/' . $this->_version . '/highlight.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function BackwardDependencies () {
		$depends = array(new HighlightJSStyle($this->_style, $this->_version));

		foreach ($this->_languages as $language)
			$depends[] = new HighlightJSLanguage($language, $this->_version);

		if ($this->_autoInit)
			$depends[] = new QuarkInlineJSViewResource('hljs.initHighlightingOnLoad();');

		return $depends;
	}
}