<?php
namespace Quark\ViewResources\MediumEditor;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkViewResourceWithBackwardDependencies;

/**
 * Class MediumEditor
 *
 * @package Quark\ViewResources\MediumEditor
 */
class MediumEditor implements IQuarkViewResource, IQuarkViewResourceWithDependencies, IQuarkViewResourceWithBackwardDependencies {
	const CURRENT_VERSION = '5.22.1';

	/**
	 * @var string $_theme = MediumEditorTheme::NAME_DEFAULT
	 */
	private $_theme = MediumEditorTheme::NAME_DEFAULT;

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @param string $theme = MediumEditorTheme::NAME_DEFAULT
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($theme = MediumEditorTheme::NAME_DEFAULT, $version = self::CURRENT_VERSION) {
		$this->_theme = $theme;
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new MediumEditorCSS($this->_version),
			new MediumEditorJS($this->_version)
		);
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function BackwardDependencies () {
		return array(
			new MediumEditorTheme($this->_theme, $this->_version)
		);
	}
}