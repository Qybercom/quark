<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw;

use Quark\QuarkFile;

/**
 * Interface IQuarkGDImageAction
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw
 */
interface IQuarkGDImageAction {
	/**
	 * @param resource $image
	 * @param QuarkFile $file
	 *
	 * @return resource
	 */
	public function GDAction($image, QuarkFile $file);
}