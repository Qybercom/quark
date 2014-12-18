<?php
namespace Quark\Extensions\UI;

use Quark\IQuarkExtension;

use Quark\QuarkSource;

/**
 * Class Resource
 *
 * @package Quark\Extensions\UI
 */
class Resource implements IQuarkExtension {
	private static $_js = array();
	private static $_css = array();

	/**
	 * @param bool $core
	 */
	public function __construct ($core = true) {
		if (!$core) return;

		$this->JS(__DIR__ . '/../../Quark.js');
		$this->CSS(__DIR__ . '/../../Quark.css');
	}

	/**
	 * @return mixed
	 */
	public function Init () { }

	/**
	 * @param $file
	 *
	 * @return Resource
	 */
	public function JS ($file) {
		self::$_js[] = QuarkSource::FromFile($file);

		return $this;
	}

	/**
	 * @param $file
	 *
	 * @return Resource
	 */
	public function CSS ($file) {
		self::$_css[] = QuarkSource::FromFile($file);

		return $this;
	}

	/**
	 * @return Resource
	 */
	public function JS_IO () {
		$this->JS(__DIR__ . '/../../ViewResources/Quark/JS/Quark.IO.js');

		return $this;
	}

	/**
	 * @return Resource
	 */
	public function JS_UX () {
		$this->JS_IO();
		$this->JS(__DIR__ . '/../../ViewResources/Quark/JS/Quark.UX.js');

		return $this;
	}

	/**
	 * @param bool $min
	 *
	 * @return string
	 */
	public function Content ($min = true) {
		$css = '';
		$js = '';

		/**
		 * @var QuarkSource $file
		 */

		foreach (self::$_css as $i => $file)
			$css .= $min
				? $file->Obfuscate(true)->Source()
				: $file->Source();

		foreach (self::$_js as $i => $file)
			$js .= $min
				? $file->Obfuscate()->Source()
				: $file->Source();

		return '<style type="text/css">' . $css . '</style>' . "\r\n\r\n"
			. '<script type="text/javascript">' . $js . '</script>';
	}
}