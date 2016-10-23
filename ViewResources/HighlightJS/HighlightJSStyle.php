<?php
namespace Quark\ViewResources\HighlightJS;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkCSSViewResourceType;

/**
 * Class HighlightJSStyle
 *
 * @package Quark\ViewResources\HighlightJS
 */
class HighlightJSStyle implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const STYLE_DEFAULT = 'default';
	const STYLE_DARKULA = 'darkula';
	const STYLE_GITHUB = 'github';
	const STYLE_GITHUB_GIST = 'github-gist';
	const STYLE_GOOGLECODE = 'googlecode';
	const STYLE_MONOKAI = 'monokai';
	const STYLE_MONOKAI_SUBLIME = 'monokai-sublime';
	const STYLE_QTCREATOR_DARK = 'qtcreator-dark';
	const STYLE_QTCREATOR_LIGHT = 'qtcreator-light';
	const STYLE_IDEA = 'idea';
	const STYLE_VS = 'vs';
	const STYLE_XCODE = 'xcode';

	/**
	 * @var string $_version = HighlightJS::CURRENT_VERSION
	 */
	private $_version = HighlightJS::CURRENT_VERSION;

	/**
	 * @var string $_name = self::STYLE_GITHUB
	 */
	private $_name = self::STYLE_GITHUB;

	/**
	 * @param string $name = self::STYLE_GITHUB
	 * @param string $version = HighlightJS::CURRENT_VERSION
	 */
	public function __construct ($name = self::STYLE_GITHUB, $version = HighlightJS::CURRENT_VERSION) {
		$this->_name = $name;
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/' . $this->_version . '/styles/' . $this->_name . '.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}