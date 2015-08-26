<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw\Actions;

use Quark\QuarkFile;

use Quark\Extensions\MediaProcessing\GraphicsDraw\IQuarkGDImageAction;

/**
 * Class GDImageCropAction
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw\Filters
 */
class GDImageCropAction implements IQuarkGDImageAction {
	/**
	 * @var int $width
	 */
	public $width;

	/**
	 * @var int $height
	 */
	public $height;

	/**
	 * @var int $x = 0
	 */
	public $x = 0;

	/**
	 * @var int $y = 0
	 */
	public $y = 0;

	/**
	 * @param int $width
	 * @param int $height
	 * @param int $x = 0
	 * @param int $y = 0
	 */
	public function __construct ($width, $height, $x = 0, $y = 0) {
		$this->width = $width;
		$this->height = $height;
		$this->x = $x;
		$this->y = $y;
	}

	/**
	 * @param resource $image
	 * @param QuarkFile $file
	 *
	 * @return resource
	 */
	public function GDAction ($image, QuarkFile $file) {
		$dst = imagecreatetruecolor($this->width, $this->height);
		$ok = imagecopy($dst, $image, 0, 0, $this->x, $this->y, $this->width, $this->height);

		return $ok ? $dst : $image;
	}
}