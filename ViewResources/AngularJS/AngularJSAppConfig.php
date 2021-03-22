<?php
namespace Quark\ViewResources\AngularJS;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;
use Quark\Quark;

/**
 * Class AngularJSAppConfig
 *
 * @package Quark\ViewResources\AngularJS
 */
class AngularJSAppConfig implements IQuarkExtensionConfig {
	const DEFAULT_NAME_INDEX_HTML = 'index.html';
	const DEFAULT_NAME_STYLES_CSS = 'styles.css';
	const DEFAULT_NAME_MAIN_JS = 'main.js';
	const DEFAULT_NAME_POLYFILLS_JS = 'polyfills.js';
	const DEFAULT_NAME_RUNTIME_JS = 'runtime.js';
	const DEFAULT_NAME_VENDOR_JS = 'vendor.js';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_location = ''
	 */
	private $_location = '';

	/**
	 * @var string $_nameIndexHTML = self::DEFAULT_NAME_INDEX_HTML
	 */
	private $_nameIndexHTML = self::DEFAULT_NAME_INDEX_HTML;

	/**
	 * @var string $_nameStylesCSS = self::DEFAULT_NAME_STYLES_CSS
	 */
	private $_nameStylesCSS = self::DEFAULT_NAME_STYLES_CSS;

	/**
	 * @var string $_nameMainJS = self::DEFAULT_NAME_MAIN_JS
	 */
	private $_nameMainJS = self::DEFAULT_NAME_MAIN_JS;

	/**
	 * @var string $_namePolyfillsJS = self::DEFAULT_NAME_POLYFILLS_JS
	 */
	private $_namePolyfillsJS = self::DEFAULT_NAME_POLYFILLS_JS;

	/**
	 * @var string $_nameRuntimeJS = self::DEFAULT_NAME_RUNTIME_JS
	 */
	private $_nameRuntimeJS = self::DEFAULT_NAME_RUNTIME_JS;

	/**
	 * @var string $_nameVendorJS = self::DEFAULT_NAME_VENDOR_JS
	 */
	private $_nameVendorJS = self::DEFAULT_NAME_VENDOR_JS;

	/**
	 * @return string[]
	 */
	public static function ArtifactResources () {
		return array(
			'Index' => 'HTML',
			'Favicon' => 'ICO',
			'Styles' => 'CSS',
			'Main' => 'JS',
			'Polyfills' => 'JS',
			'Runtime' => 'JS',
			'Vendor' => 'JS'
		);
	}

	/**
	 * @param string $location = ''
	 *
	 * @return string
	 */
	public function Location ($location = '') {
		if (func_num_args() != 0)
			$this->_location = $location;

		return $this->_location;
	}

	/**
	 * @param string $name = self::DEFAULT_NAME_INDEX_HTML
	 *
	 * @return string
	 */
	public function NameIndexHTML ($name = self::DEFAULT_NAME_INDEX_HTML) {
		if (func_num_args() != 0)
			$this->_nameIndexHTML = $name;

		return $this->_nameIndexHTML;
	}

	/**
	 * @param string $name = self::DEFAULT_NAME_MAIN_JS
	 *
	 * @return string
	 */
	public function NameMainJS ($name = self::DEFAULT_NAME_MAIN_JS) {
		if (func_num_args() != 0)
			$this->_nameMainJS = $name;

		return $this->_nameMainJS;
	}

	/**
	 * @param string $name = self::DEFAULT_NAME_STYLES_CSS
	 *
	 * @return string
	 */
	public function NameStylesCSS ($name = self::DEFAULT_NAME_STYLES_CSS) {
		if (func_num_args() != 0)
			$this->_nameStylesCSS = $name;

		return $this->_nameStylesCSS;
	}

	/**
	 * @param string $name = self::DEFAULT_NAME_POLYFILLS_JS
	 *
	 * @return string
	 */
	public function NamePolyfillsJS ($name = self::DEFAULT_NAME_POLYFILLS_JS) {
		if (func_num_args() != 0)
			$this->_namePolyfillsJS = $name;

		return $this->_namePolyfillsJS;
	}

	/**
	 * @param string $name = self::DEFAULT_NAME_RUNTIME_JS
	 *
	 * @return string
	 */
	public function NameRuntimeJS ($name = self::DEFAULT_NAME_RUNTIME_JS) {
		if (func_num_args() != 0)
			$this->_nameRuntimeJS = $name;

		return $this->_nameRuntimeJS;
	}

	/**
	 * @param string $name = self::DEFAULT_NAME_VENDOR_JS
	 *
	 * @return string
	 */
	public function NameVendorJS ($name = self::DEFAULT_NAME_VENDOR_JS) {
		if (func_num_args() != 0)
			$this->_nameVendorJS = $name;

		return $this->_nameVendorJS;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->Location))
			$this->Location($ini->Location);

		if (isset($ini->NameIndexHTML))
			$this->NameIndexHTML($ini->NameIndexHTML);

		if (isset($ini->NameStylesCSS))
			$this->NameStylesCSS($ini->NameStylesCSS);

		if (isset($ini->NameMainJS))
			$this->NameMainJS($ini->NameMainJS);

		if (isset($ini->NamePolyfillsJS))
			$this->NamePolyfillsJS($ini->NamePolyfillsJS);

		if (isset($ini->NameRuntimeJS))
			$this->NameRuntimeJS($ini->NameRuntimeJS);

		if (isset($ini->NameVendorJS))
			$this->NameVendorJS($ini->NameVendorJS);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Artifact ($name = '') {
		$artifact = '_name' . $name;

		return $this->$artifact;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function ArtifactLocation ($name = '') {
		return $this->Location() . '/' . $this->Artifact($name);
	}
}