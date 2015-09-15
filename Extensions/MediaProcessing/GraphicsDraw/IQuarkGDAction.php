<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw;

use Quark\QuarkFile;

/**
 * Interface IQuarkGDAction
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw
 */
interface IQuarkGDAction {
	/**
	 * @param resource $image
	 * @param QuarkFile $file
	 *
	 * @return resource
	 */
	public function GDAction($image, QuarkFile $file);
}