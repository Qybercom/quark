<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw\Filters;

use Quark\Extensions\MediaProcessing\GraphicsDraw\IQuarkGDFilter;

/**
 * Class GDBlurFilter
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw\Filters
 */
class GDBlurFilter implements IQuarkGDFilter {
	/**
	 * @var int $_cycles = 15
	 */
	private $_cycles = 15;

	/**
	 * @param int $cycles = 15
	 */
	public function __construct ($cycles = 15) {
		$this->_cycles = $cycles;
	}

	/**
	 * @param resource $image
	 *
	 * @return resource
	 */
	public function GDFilter ($image) {
		$i = 0;

		while ($i < $this->_cycles) {
			imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
			$i++;
		}

		return $image;
	}
}