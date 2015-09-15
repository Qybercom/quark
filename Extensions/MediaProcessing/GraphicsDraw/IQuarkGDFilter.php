<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw;

/**
 * Interface IQuarkGDFilter
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw
 */
interface IQuarkGDFilter {
	/**
	 * @param resource $image
	 *
	 * @return resource
	 */
	public function GDFilter($image);
}