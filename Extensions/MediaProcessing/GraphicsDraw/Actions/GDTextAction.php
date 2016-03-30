<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw\Actions;

use Quark\QuarkFile;

use Quark\Extensions\MediaProcessing\GraphicsDraw\IQuarkGDAction;

use Quark\Extensions\MediaProcessing\GraphicsDraw\GDImage;
use Quark\Extensions\MediaProcessing\GraphicsDraw\GDColor;
use Quark\Extensions\MediaProcessing\GraphicsDraw\GDPosition;

/**
 * Class GDTextAction
 *
 * @note Function `imagettftext` `y` coordinate depends on the BASELINE.
 * 		 For that case, by default, position's `y` coordinate is set to the given size
 *
 * @package Quark\Extensions\MediaProcessing\GraphicsDraw\Actions
 */
class GDTextAction implements IQuarkGDAction {
	const DEFAULT_FONT = 'Arial';
	const DEFAULT_SIZE = 20;

	const ALIGN_LEFT = 'align.left';
	const ALIGN_CENTER = 'align.center';
	const ALIGN_RIGHT = 'align.right';

	/**
	 * @var string $_text
	 */
	private $_text = '';

	/**
	 * @var string $_font = self::DEFAULT_FONT
	 */
	private $_font = self::DEFAULT_FONT;

	/**
	 * @var int $_size = self::DEFAULT_SIZE
	 */
	private $_size = self::DEFAULT_SIZE;

	/**
	 * @var GDPosition $_position
	 */
	private $_position = null;

	/**
	 * @var GDColor $_color
	 */
	private $_color = null;

	/**
	 * @var string $_align = self::ALIGN_LEFT
	 */
	private $_align = self::ALIGN_LEFT;

	/**
	 * @var int $_boxLeft = 0
	 */
	private $_boxLeft = 0;

	/**
	 * @var int $_boxTop = 0
	 */
	private $_boxTop = 0;

	/**
	 * @var int $_boxWidth = 0
	 */
	private $_boxWidth = 0;

	/**
	 * @var int $_boxHeight = 0
	 */
	private $_boxHeight = 0;

	/**
	 * @var array $_shadow = []
	 */
	private $_shadow = array();

	/**
	 * http://php.net/manual/ru/function.imagettfbbox.php#105593
	 *
	 * @param string $text = ''
	 * @param string $font = self::DEFAULT_FONT
	 * @param int $size = self::DEFAULT_SIZE
	 * @param GDPosition $position = null
	 * @param GDColor $color = null
	 * @param string $align = self::ALIGN_LEFT
	 */
	public function __construct ($text = '', $font = self::DEFAULT_FONT, $size = self::DEFAULT_SIZE, GDPosition $position = null, GDColor $color = null, $align = self::ALIGN_LEFT) {
		$this->_text = $text;
		$this->_font = $font;
		$this->_size = $size;
		$this->_position = $position ? $position : new GDPosition(0, $size);
		$this->_color = $color ? $color : GDColor::FromHEX(GDColor::BLACK);
		$this->_align = $align;

		$rect = imagettfbbox($size, $this->_position->angle, $font, $text);

		$minX = min(array($rect[0], $rect[2], $rect[4], $rect[6]));
		$maxX = max(array($rect[0], $rect[2], $rect[4], $rect[6]));
		$minY = min(array($rect[1], $rect[3], $rect[5], $rect[7]));
		$maxY = max(array($rect[1], $rect[3], $rect[5], $rect[7]));

		$this->_boxLeft = abs($minX) - 1;
		$this->_boxTop = abs($minY) - 1;
		$this->_boxWidth = $maxX - $minX;
		$this->_boxHeight = $maxY - $minY;
	}

	/**
	 * @param string $text = ''
	 *
	 * @return string
	 */
	public function Text ($text = '') {
		if (func_num_args() != 0)
			$this->_text = $text;

		return $this->_text;
	}

	/**
	 * @param string $font = self::DEFAULT_FONT
	 *
	 * @return string
	 */
	public function Font ($font = self::DEFAULT_FONT) {
		if (func_num_args() != 0)
			$this->_font = $font;

		return $this->_font;
	}

	/**
	 * @param int $size = self::DEFAULT_SIZE
	 *
	 * @return int
	 */
	public function Size ($size = self::DEFAULT_SIZE) {
		if (func_num_args() != 0)
			$this->_size = $size;

		return $this->_size;
	}

	/**
	 * @param GDPosition $position = null
	 *
	 * @return GDPosition
	 */
	public function Position (GDPosition $position = null) {
		if (func_num_args() != 0)
			$this->_position = $position;

		return $this->_position;
	}

	/**
	 * @param GDColor $color = null
	 *
	 * @return GDColor
	 */
	public function Color (GDColor $color = null) {
		if (func_num_args() != 0)
			$this->_color = $color;

		return $this->_color;
	}

	/**
	 * @param string $align = self::ALIGN_LEFT
	 *
	 * @return string
	 */
	public function Align ($align = self::ALIGN_LEFT) {
		if (func_num_args() != 0)
			$this->_align = $align;

		return $this->_align;
	}

	/**
	 * @return GDPosition
	 */
	public function BoxPosition () {
		return new GDPosition($this->_boxLeft, $this->_boxTop);
	}

	/**
	 * @return GDPosition
	 */
	public function BoxSize () {
		return new GDPosition($this->_boxWidth, $this->_boxHeight);
	}

	/**
	 * @param int $x = 0
	 * @param int $y = 0
	 * @param GDColor $color = null
	 * @param int $blur = 0
	 *
	 * @return GDTextAction
	 */
	public function Shadow ($x = 0, $y = 0, GDColor $color = null, $blur = 0) {
		$this->_shadow[] = array((int)$x, (int)$y, $color, (int)$blur);

		return $this;
	}

	/**
	 * @param resource $image
	 * @param QuarkFile $file
	 *
	 * @return resource
	 */
	public function GDAction ($image, QuarkFile $file) {
		if (!$this->_color)
			return $image;
		
		foreach ($this->_shadow as $shadow) {
			if (sizeof($shadow) != 4) continue;
			if (!($shadow[2] instanceof GDColor)) continue;

			$this->_line(
				$image,
				$this->_text,
				$this->_size,
				$this->_font,
				$this->_position->x + $shadow[0],
				$this->_position->y + $shadow[1],
				$this->_position->angle,
				$shadow[2]
			);
		}

		return $this->_line(
			$image,
			$this->_text,
			$this->_size,
			$this->_font,
			$this->_position->x,
			$this->_position->y,
			$this->_position->angle,
			$this->_color
		);
	}

	/**
	 * @param resource $image
	 * @param string $text
	 * @param int $size
	 * @param string $font
	 * @param int $x
	 * @param int $y
	 * @param float $angle
	 * @param GDColor $color
	 *
	 * @return mixed
	 */
	private function _line ($image, $text, $size, $font, $x, $y, $angle, GDColor $color) {
		imagettftext($image, $size, $angle, $x, $y, $color->Allocate($image), $font, $text);
		$color->Deallocate($image);

		return $image;
	}

	/**
	 * @param GDImage $image
	 * @param string $text = ''
	 * @param string $font = self::DEFAULT_FONT
	 * @param int $size = self::DEFAULT_SIZE
	 * @param int $angle = 0
	 * @param GDColor $color = null
	 * @param string $align = self::ALIGN_LEFT
	 *
	 * @return GDTextAction
	 */
	public static function AtCenterOf (GDImage $image, $text = '', $font = self::DEFAULT_FONT, $size = self::DEFAULT_SIZE, $angle = 0, GDColor $color = null, $align = self::ALIGN_LEFT) {
		$center = $image->Center();

		$action = new self($text, $font, $size, new GDPosition(0, 0, $angle), $color, $align);
		$action->Position(new GDPosition(
			$action->_boxLeft + $center->x - $action->_boxWidth / 2,
			$action->_boxTop + $center->y - $action->_boxHeight / 2,
			$angle
		));

		return $action;
	}
}