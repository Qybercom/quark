<?php
namespace Quark\ViewResources\CodeMirror;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class CodeMirror
 *
 * @package Quark\ViewResources\CodeMirror
 */
class CodeMirror implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	const VERSION_CURRENT = '5.54.0';

	/**
	 * @var string $_mode
	 */
	private $_mode;

	/**
	 * @var string $_theme
	 */
	private $_theme;

	/**
	 * @var string[] $_addons = []
	 */
	private $_addons = array();

	/**
	 * @var string $_version
	 */
	private $_version = self::VERSION_CURRENT;

	/**
	 * @param string $mode = null
	 * @param string $theme = null
	 * @param string $version = self::VERSION_CURRENT
	 */
	public function __construct ($mode = null, $theme = null, $version = self::VERSION_CURRENT) {
		$this->_mode = $mode;
		$this->_theme = $theme;
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		$out = array(
			new CodeMirrorCSS($this->_version),
			new CodeMirrorJS($this->_version)
		);

		if ($this->_mode != null)
			$out[] = new CodeMirrorMode($this->_mode, $this->_version);

		if ($this->_theme != null)
			$out[] = new CodeMirrorTheme($this->_theme, $this->_version);

		foreach ($this->_addons as $i => &$addOn)
			$out[] = new CodeMirrorAddOn($addOn);

		return $out;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function AddOn ($name) {
		$this->_addons[] = $name;

		return $this;
	}
}