<?php
namespace Quark\ViewResources\Quark\QuarkFont;

use Quark\QuarkObject;

/**
 * Class QuarkFontItem
 *
 * @package Quark\ViewResources\Quark\QuarkFont
 */
class QuarkFontItem {
	const STYLE_DEFAULT = '';
	const STYLE_NORMAL = 'normal';
	const STYLE_ITALIC = 'italic';
	const STYLE_OBLIQUE = 'oblique';
	const STYLE_INHERIT = '';

	const WEIGHT_DEFAULT = 0;
	const WEIGHT_100 = 100;
	const WEIGHT_200 = 200;
	const WEIGHT_300 = 300;
	const WEIGHT_400 = 400;
	const WEIGHT_500 = 500;
	const WEIGHT_600 = 600;
	const WEIGHT_700 = 700;
	const WEIGHT_800 = 800;
	const WEIGHT_900 = 900;

	/**
	 * @var string $_family = ''
	 */
	private $_family = '';

	/**
	 * @var string $_style = self::STYLE_DEFAULT
	 */
	private $_style = self::STYLE_DEFAULT;

	/**
	 * @var int $_weight = self::WEIGHT_DEFAULT
	 */
	private $_weight = self::WEIGHT_DEFAULT;

	/**
	 * @var QuarkFontSource[] $_source = []
	 */
	private $_source = array();

	/**
	 * @var string[] $_local = []
	 */
	private $_local = array();

	/**
	 * @var string[] $_unicodeRange = []
	 */
	private $_unicodeRange = array();

	/**
	 * @param string $family = ''
	 * @param QuarkFontSource $source = null
	 * @param string[] $local = ''
	 * @param string $style = self::STYLE_DEFAULT
	 * @param int $weight = self::WEIGHT_DEFAULT
	 * @param string[] $unicodeRange = []
	 */
	public function __construct ($family = '', QuarkFontSource $source = null, $local = [], $style = self::STYLE_DEFAULT, $weight = self::WEIGHT_DEFAULT, $unicodeRange = []) {
		$this->Family($family);
		$this->Source($source);
		$this->Local($local);
		$this->Style($style);
		$this->Weight($weight);
		$this->_unicodeRange = $unicodeRange;
	}

	/**
	 * @param string $family = ''
	 *
	 * @return string
	 */
	public function Family ($family = '') {
		if (func_num_args() != 0)
			$this->_family = $family;

		return $this->_family;
	}

	/**
	 * @param string $style = self::STYLE_DEFAULT
	 *
	 * @return string
	 */
	public function Style ($style = self::STYLE_DEFAULT) {
		if (func_num_args() != 0)
			$this->_style = $style;

		return $this->_style;
	}

	/**
	 * @param int $weight =  = self::WEIGHT_DEFAULT
	 *
	 * @return int
	 */
	public function Weight ($weight = self::WEIGHT_DEFAULT) {
		if (func_num_args() != 0)
			$this->_weight = $weight;

		return $this->_weight;
	}

	/**
	 * @param QuarkFontSource $source = null
	 *
	 * @return QuarkFontItem
	 */
	public function Source (QuarkFontSource $source = null) {
		if ($source != null)
			$this->_source[] = $source;

		return $this;
	}

	/**
	 * @param QuarkFontSource[] $source = []
	 *
	 * @return QuarkFontSource[]
	 */
	public function Sources ($source = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($source, new QuarkFontSource()))
			$this->_source = $source;

		return $this->_source;
	}

	/**
	 * @param string[] $local = []
	 *
	 * @return string[]
	 */
	public function Local ($local = []) {
		if (func_num_args() != 0)
			$this->_local = $local;

		return $this->_local;
	}

	/**
	 * @param string $first = ''
	 * @param string $last = ''
	 *
	 * @return QuarkFontItem
	 */
	public function UnicodeRange ($first = '', $last = '') {
		$this->_unicodeRange[] = $first . '-' . $last;

		return $this;
	}

	/**
	 * @param string $char = ''
	 *
	 * @return QuarkFontItem
	 */
	public function UnicodeChar ($char = '') {
		$this->_unicodeRange[] = $char;

		return $this;
	}

	/**
	 * @return string
	 */
	public function FontFace () {
		$local = '';

		foreach ($this->_local as $i => &$name)
			$local .= 'local(' . $name . '),';

		$source = array();

		foreach ($this->_source as $i => &$src)
			$source[] = $src->Src();

		return '@font-face{'
			 . 'font-family: \'' . $this->_family . '\';'
			 . ($this->_style ? 'font-style:' . $this->_style . ';' : '')
			 . ($this->_weight ? 'font-weight:' . $this->_weight . ';' : '')
			 . 'src: ' . $local . implode(',', $source) . ';}'
			 . (sizeof($this->_unicodeRange) != 0 ? 'unicode-range:' . implode(',', $this->_unicodeRange) . ';' : '');
	}
}