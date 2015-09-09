<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw;

use Quark\IQuarkExtension;

use Quark\QuarkFile;

/**
 * Class GDImage
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw
 */
class GDImage implements IQuarkExtension {
	/**
	 * @var array
	 */
	private static $_processors = array(
		'image/jpeg' => 'imagejpeg',
		'image/png' => 'imagepng',
		'image/gif' => 'imagegif',
		'image/wbmp' => 'imagewbmp',
		'image/webp' => 'imagewebp',
		'image/xbm' => 'imagexbm',
		'image/xpm' => 'imagexpm',
	);

	/**
	 * @var QuarkFile $_file
	 */
	private $_file;

	/**
	 * @var resource $_image
	 */
	private $_image;

	/**
	 * @param int $width
	 * @param int $height
	 */
	public function __construct ($width = 1, $height = 1) {
		$this->_file = new QuarkFile();
		$this->_file->type = 'image/png';
		$this->_image = self::Canvas($width, $height);
	}

	/**
	 * @param QuarkFile|string $image
	 *
	 * @return GDImage
	 */
	public static function FromFile ($image = '') {
		if (is_string($image))
			$image = new QuarkFile($image, true);

		if (!($image instanceof QuarkFile)) return null;

		$img = new GDImage();
		$img->File($image);
		$img->Content($image->Content());

		return $img;
	}

	/**
	 * @param QuarkFile $file
	 *
	 * @return QuarkFile
	 */
	public function File (QuarkFile $file = null) {
		if (func_num_args() != 0)
			$this->_file = $file;

		return $this->_file;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function Content ($content = '') {
		if (func_num_args() != 0) {
			$this->_image = imagecreatefromstring($content);
			$this->_file->Content($content);
		}

		return $this->_file->Content();
	}

	/**
	 * @return resource
	 */
	public function Image () {
		return $this->_image;
	}

	/**
	 * @param IQuarkGDImageFilter $filter
	 *
	 * @return GDImage
	 */
	public function Filter (IQuarkGDImageFilter $filter) {
		if ($this->_image) {
			$this->_image = $filter->GDFilter($this->_image);
			$this->_apply();
		}

		return $this;
	}

	/**
	 * @param IQuarkGDImageAction $action
	 *
	 * @return GDImage
	 */
	public function Action (IQuarkGDImageAction $action) {
		if ($this->_image) {
			$this->_image = $action->GDAction($this->_image, $this->_file);
			$this->_apply();
		}

		return $this;
	}

	/**
	 * @param string $location
	 *
	 * @return GDImage
	 */
	public function Duplicate ($location) {
		$duplicate = clone $this;
		$duplicate->File()->Location($location);
		return $duplicate;
	}

	/**
	 * http://php.net/manual/ru/function.imagecolorat.php
	 * http://php.net/manual/ru/function.imagecolorallocatealpha.php#61081
	 *
	 * @param int $x
	 * @param int $y
	 *
	 * @return GDColor
	 */
	public function ColorPicker ($x, $y) {
		$rgb = imagecolorat($this->_image, $x, $y);

		$color = GDColor::FromRGB($rgb);
		$color->a = $rgb >> 24;

		return $color;
	}

	/**
	 * @param int $width
	 *
	 * @return int
	 */
	public function Width ($width = 0) {
		if (func_num_args() != 0)
			$this->_resize(-1, $width);

		return imagesx($this->_image);
	}

	/**
	 * @param int $height
	 *
	 * @return int
	 */
	public function Height ($height = 0) {
		if (func_num_args() != 0)
			$this->_resize($height, -1);

		return imagesy($this->_image);
	}

	/**
	 * @param int $factor = 1
	 *
	 * @return bool
	 */
	public function Resize ($factor = 1) {
		return $this->_resize($this->Height() * $factor, $this->Width() * $factor);
	}

	/**
	 * http://stackoverflow.com/a/18110532
	 *
	 * @param int $height
	 * @param int $width
	 *
	 * @return bool
	 */
	private function _resize ($height = -1, $width = -1) {
		$x = imagesx($this->_image);
		$y = imagesy($this->_image);

		$height = $height > -1 ? $height : $y;
		$width = $width > -1 ? $width : $x;

		$tmp = self::Canvas($width, $height);

		$ok = imagecopyresized($tmp, $this->_image, 0, 0, 0, 0, $width, $height, $x, $y);

		$this->_image = $tmp;

		return $ok && $this->_apply();
	}

	/**
	 * @param $width
	 * @param $height
	 * @param int $x = 0
	 * @param int $y = 0
	 *
	 * @return bool
	 */
	public function Crop ($width, $height, $x = 0, $y = 0) {
		$dst = imagecreatetruecolor($width, $height);
		$ok = imagecopy($dst, $this->_image, 0, 0, $x, $y, $width, $height);

		$this->_image = $dst;

		return $ok && $this->_apply();
	}

	/**
	 * http://php.net/manual/ru/function.imagecopymerge.php#92787
	 *
	 * @param GDImage $image
	 * @param int $x = 0
	 * @param int $y = 0
	 * @param float $alpha = 1.0
	 *
	 * @return bool
	 */
	public function Merge (GDImage $image, $x = 0, $y = 0, $alpha = 1.0) {
		$canvas = imagecreatetruecolor($this->Width(), $this->Height());

		imagecopy($canvas, $this->_image, 0, 0, 0, 0, $this->Width(), $this->Height());
		imagecopy($canvas, $image->Image(), $x, $y, 0, 0, $image->Width(), $image->Height());
		imagecopymerge($this->_image, $canvas, 0, 0, 0, 0, $this->Width(), $this->Height(), $alpha * 100);

		return $this->_apply();
	}

	/**
	 * @return bool
	 */
	private function _apply () {
		if (!isset(self::$_processors[$this->_file->type])) return false;

		$processor = self::$_processors[$this->_file->type];

		ob_start();
		$processor($this->_image);
		$this->_file->Content(ob_get_clean());

		return true;
	}

	/**
	 * @param int $width = 0
	 * @param int $height = 0
	 * @param GDColor $bg = new GDColor(255,255,255,0)
	 *
	 * @return resource
	 */
	public static function Canvas ($width = 0, $height = 0, GDColor $bg = null) {
		if (!$bg)
			$bg = new GDColor(255,255,255,0);

		$canvas = imagecreatetruecolor((int)$width, (int)$height);
		imagealphablending($canvas, false);
		imagesavealpha($canvas, true);

		$bg->Allocate($canvas);

		imagefill($canvas, 0, 0, $bg->Resource());

		return $canvas;
	}
}