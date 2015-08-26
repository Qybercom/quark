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
	public function __construct ($width = 0, $height = 0) {
		$this->_file = new QuarkFile();

		if (func_num_args() != 0)
			$this->_image = imagecreatetruecolor($width, $height);
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
}