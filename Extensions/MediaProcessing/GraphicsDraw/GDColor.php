<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw;

use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\Quark;
use Quark\QuarkModel;

/**
 * Class GDColor
 *
 * @property int $r = 0
 * @property int $g = 0
 * @property int $b = 0
 * @property int|float $a = 1.0
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw
 */
class GDColor implements IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel {
	const WHITE = 'FFFFFF';
	const RED = 'FF0000';
	const GREEN = '00FF00';
	const BLUE = '0000FF';
	const BLACK = '000000';

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

	/**
	 * @param float $alpha = 1.0
	 *
	 * @return GDColor
	 */
	public static function RandomColor ($alpha = 1.0) {
		return new self(
			mt_rand(0, 255),
			mt_rand(0, 255),
			mt_rand(0, 255),
			func_num_args() != 0 ? $alpha : Quark::RandomFloat(0.0, 1.0)
		);
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'r' => 0,
			'g' => 0,
			'b' => 0,
			'a' => 1.0
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return new QuarkModel(self::FromHEX($raw));
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->ToHEX();
	}
}