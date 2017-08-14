<?php
namespace Quark\ViewResources\Quark\QuarkFont;

/**
 * Class QuarkFontSource
 *
 * @package Quark\ViewResources\Quark\QuarkFont
 */
class QuarkFontSource {
	const CHARSET_UTF_8 = 'utf-8';

	const FORMAT_TRUETYPE = 'truetype';
	const FORMAT_OPENTYPE = 'opentype';
	const FORMAT_EMBEDDED_OPENTYPE = 'embedded-opentype';
	const FORMAT_WOFF = 'woff';
	const FORMAT_WOFF2 = 'woff2';
	const FORMAT_SVG = 'svg';

	/**
	 * @var string $_url = ''
	 */
	private $_url = '';

	/**
	 * @var string $_format = ''
	 */
	private $_format = '';

	/**
	 * @var bool $_external = true
	 */
	private $_external = true;

	/**
	 * @param string $url = ''
	 * @param string $format = ''
	 */
	public function __construct ($url = '', $format = '') {
		$this->Url($url);
		$this->Format($format);
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function Url ($url = '') {
		if (func_num_args() != 0)
			$this->_url = $url;

		return $this->_url;
	}

	/**
	 * @param string $format = ''
	 *
	 * @return string
	 */
	public function Format ($format = '') {
		if (func_num_args() != 0)
			$this->_format = $format;

		return $this->_format;
	}

	/**
	 * @return string
	 */
	public function Src () {
		$escape = $this->_external ? '\'' : '';

		return 'url(' . $escape . $this->_url . $escape . ')' . ($this->_format ? ' format(\'' . $this->_format . '\')' : '');
	}

	/**
	 * @param string $format
	 * @param string $content = ''
	 * @param string $charset = self::CHARSET_UTF_8
	 *
	 * @return QuarkFontSource
	 */
	public static function InlineFont ($format, $content = '', $charset = self::CHARSET_UTF_8) {
		$font = new self('data:font/' . $format . ';charset=' . $charset . ';base64,' . base64_encode($content));
		$font->_external = false;

		return $font;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return QuarkFontSource
	 */
	public static function IECompatible ($url = '') {
		return new self($url . '?', self::FORMAT_EMBEDDED_OPENTYPE);
	}
}