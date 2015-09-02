<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw;

/**
 * Interface IQuarkGDImageFilter
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw
 */
interface IQuarkGDImageFilter {
	/**
	 * @param resource $image
	 *
	 * @return resource
	 */
	public function GDFilter($image);
}