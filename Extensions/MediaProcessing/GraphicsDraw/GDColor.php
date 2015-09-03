<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw;

/**
 * Class GDColor
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw
 */
class GDColor {
	const WHITE = 'FFFFFF';
	const RED = 'FF0000';
	const GREEN = '00FF00';
	const BLUE = '0000FF';
	const BLACK = '000000';

	/**
	 * @var int $r = 0
	 */
	public $r = 0;

	/**
	 * @var int $g = 0
	 */
	public $g = 0;

	/**
	 * @var int $b = 0
	 */
	public $b = 0;

	/**
	 * @var float $a = 1.0
	 */
	public $a = 1;

	/**
	 * @var int $_resource
	 */
	private $_resource;

	/**
	 * @param $r
	 * @param $g
	 * @param $b
	 * @param float $a = 1.0
	 */
	public function __construct ($r, $g, $b, $a = 1.0) {
		$this->r = (int)$r;
		$this->g = (int)$g;
		$this->b = (int)$b;
		$this->a = (float)$a;
	}

	/**
	 * @param int $rgb
	 * @param float $alpha = 1.0
	 *
	 * @return GDColor
	 */
	public static function FromRGB ($rgb, $alpha = 1.0) {
		$rgb = (float)$rgb;

		return new GDColor(
			($rgb >> 16) & 0xFF,
			($rgb >> 8) & 0xFF,
			$rgb & 0xFF,
			$alpha
		);
	}

	/**
	 * http://php.net/manual/ru/function.hexdec.php#99478
	 *
	 * @param string $hex
	 * @param float $alpha = 1.0
	 *
	 * @return GDColor
	 */
	public static function FromHEX ($hex = '', $alpha = 1.0) {
		$hex = preg_replace('/[^0-9A-Fa-f]/', '', $hex);

		if (strlen($hex) == 3)
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];

		if (strlen($hex) != 6) return null;

		return self::FromRGB(hexdec($hex), $alpha);
	}

	/**
	 * http://php.net/manual/ru/function.imagecolorallocatealpha.php#106642
	 *
	 * @param resource $image
	 *
	 * @return int
	 */
	public function Allocate ($image) {
		$a = ((~((int)((float)$this->a * 255))) & 0xff) >> 1;

		return $this->_resource = imagecolorallocatealpha($image, $this->r, $this->g, $this->b, $a);
	}

	/**
	 * @param resource $image
	 *
	 * @return bool
	 */
	public function Deallocate ($image) {
		return imagecolordeallocate($image, $this->_resource);
	}

	/**
	 * @return int
	 */
	public function Resource () {
		return $this->_resource;
	}

	/**
	 * http://www.anyexample.com/programming/php/php_convert_rgb_from_to_html_hex_color.xml
	 *
	 * @param bool $short = false
	 *
	 * @return string
	 */
	public function ToHEX ($short = false) {
		$r = dechex((int)$this->r);
		$g = dechex((int)$this->g);
		$b = dechex((int)$this->b);

		if (strlen($r) == 1) $r = $r . $r;
		if (strlen($g) == 1) $g = $g . $g;
		if (strlen($b) == 1) $b = $b . $b;

		return $short
			? ($r[0] . $g[0] . $b[0])
			: ($r . $g . $b);
	}

	/**
	 * @return string
	 */
	public function RGBToCSS () {
		return 'rgb(' . (int)$this->r . ',' . (int)$this->g . ',' . (float)$this->b . ')';
	}

	/**
	 * @return string
	 */
	public function RGBAToCSS () {
		return 'rgba(' . (int)$this->r . ',' . (int)$this->g . ',' . (int)$this->b . ',' . (float)$this->a . ')';
	}

	/**
	 * @param bool $short = false
	 *
	 * @return string
	 */
	public function HEXToCSS ($short = false) {
		return '#' . $this->ToHEX($short);
	}
}