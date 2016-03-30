<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw;

/**
 * Class GDPosition
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw
 */
class GDPosition {
	/**
	 * @var int $x = 0
	 */
	public $x = 0;

	/**
	 * @var int $y = 0
	 */
	public $y = 0;

	/**
	 * @var int $angle = 0
	 */
	public $angle = 0;

	/**
	 * @param int $x = 0
	 * @param int $y = 0
	 * @param int $angle = 0
	 */
	public function __construct ($x = 0, $y = 0, $angle = 0) {
		$this->x = (int)$x;
		$this->y = (int)$y;
		$this->angle = (int)$angle;
	}
}