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
	const TYPE_JPEG = 'image/jpeg';
	const TYPE_PNG = 'image/png';
	const TYPE_GIF = 'image/gif';
	const TYPE_WBMP = 'image/wbmp';
	const TYPE_WEBP = 'image/webp';
	const TYPE_XBM = 'image/xbm';
	const TYPE_XPM = 'image/xpm';
	
	/**
	 * @var array $_processors
	 */
	private static $_processors = array(
		self::TYPE_JPEG => 'imagejpeg',
		self::TYPE_PNG => 'imagepng',
		self::TYPE_GIF => 'imagegif',
		self::TYPE_WBMP => 'imagewbmp',
		self::TYPE_WEBP => 'imagewebp',
		self::TYPE_XBM => 'imagexbm',
		self::TYPE_XPM => 'imagexpm',
	);
	
	/**
	 * @var array $_extensions
	 */
	private static $_extensions = array(
		self::TYPE_JPEG => 'jpg',
		self::TYPE_PNG => 'png',
		self::TYPE_GIF => 'gif',
		self::TYPE_WBMP => 'wbmp',
		self::TYPE_WEBP => 'webp',
		self::TYPE_XBM => 'xbm',
		self::TYPE_XPM => 'xpm'
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
	 * @param int $width = 1
	 * @param int $height = 1
	 * @param string $type = self::TYPE_PNG
	 */
	public function __construct ($width = 1, $height = 1, $type = self::TYPE_PNG) {
		$this->_file = new QuarkFile();
		$this->_file->type = $type;
		$this->_file->extension = isset(self::$_extensions[$type]) ? self::$_extensions[$type] : 'img';
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
		$img->Content($image->Load()->Content());

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
	 * @param IQuarkGDFilter $filter = null
	 *
	 * @return GDImage
	 */
	public function Filter (IQuarkGDFilter $filter = null) {
		if ($this->_image && $filter) {
			$this->_image = $filter->GDFilter($this->_image);
			$this->_apply();
		}

		return $this;
	}

	/**
	 * @param IQuarkGDAction $action = null
	 *
	 * @return GDImage
	 */
	public function Action (IQuarkGDAction $action = null) {
		if ($this->_image && $action) {
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
	 * @return GDPosition
	 */
	public function Center () {
		return new GDPosition($this->Width() / 2, $this->Height() / 2);
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
	 * @param int $width
	 * @param int $height
	 * @param bool $cover = true
	 *
	 * @return GDImage
	 */
	public function Fit ($width, $height, $cover = true) {
		if ($cover) {
			if ($this->Width() > $width) {
				$this->Resize($height / $this->Height());
				$this->Crop($width, $height, ($this->Width() - $width) / 2, 0);
			}

			if ($this->Height() > $height) {
				$this->Resize($width / $this->Width());
				$this->Crop($width, $height, ($this->Height() - $height) / 2, 0);
			}
		}
		else {
			if ($this->Width() > $width)
				$this->Resize($width / $this->Width());

			if ($this->Height() > $height)
				$this->Resize($height / $this->Height());
		}

		return $this;
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
		$canvas = self::Canvas($this->Width(), $this->Height());

		imagecopy($canvas, $this->_image, 0, 0, 0, 0, $this->Width(), $this->Height());
		imagecopy($canvas, $image->Image(), $x, $y, 0, 0, $image->Width(), $image->Height());
		imagecopymerge($this->_image, $canvas, 0, 0, 0, 0, $this->Width(), $this->Height(), $alpha * 100);

		return $this->_apply();
	}
	
	/**
	 * @param string $type = self::TYPE_PNG
	 *
	 * @return string
	 */
	private function _process ($type = self::TYPE_PNG) {
		if (!isset(self::$_processors[$type])) return '';

		$processor = self::$_processors[$type];

		ob_start();
		$processor($this->_image);
		return ob_get_clean();
	}
	
	/**
	 * @param string $type = self::TYPE_PNG
	 *
	 * @return GDImage
	 */
	public function Convert ($type = self::TYPE_PNG) {
		$image = new self($this->Width(), $this->Height(), $type);
		$image->Content($this->_process($type));
		
		return $image;
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
		imagesavealpha($canvas, true);

		$bg->Allocate($canvas);
		imagefill($canvas, 0, 0, $bg->Resource());

		return $canvas;
	}
}