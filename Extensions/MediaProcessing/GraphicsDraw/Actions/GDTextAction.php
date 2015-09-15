<?php
namespace Quark\Extensions\MediaProcessing\GraphicsDraw\Actions;

use Quark\QuarkFile;

use Quark\Extensions\MediaProcessing\GraphicsDraw\GDColor;
use Quark\Extensions\MediaProcessing\GraphicsDraw\GDPosition;
use Quark\Extensions\MediaProcessing\GraphicsDraw\IQuarkGDAction;

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
	CONST DEFAULT_SIZE = 20;

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
	 * @param string $text
	 * @param string $font = self::DEFAULT_FONT
	 * @param int $size = self::DEFAULT_SIZE
	 * @param GDPosition $position
	 * @param GDColor $color
	 */
	public function __construct ($text = '', $font = self::DEFAULT_FONT, $size = self::DEFAULT_SIZE, GDPosition $position = null, GDColor $color = null) {
		$this->_text = $text;
		$this->_font = $font;
		$this->_size = $size;
		$this->_position = $position ? $position : new GDPosition(0, $size);
		$this->_color = $color ? $color : GDColor::FromHEX(GDColor::BLACK);
	}

	/**
	 * @param string $text
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
	 * @param GDPosition $position
	 *
	 * @return GDPosition
	 */
	public function Position (GDPosition $position = null) {
		if (func_num_args() != 0)
			$this->_position = $position;

		return $this->_position;
	}

	/**
	 * @param GDColor $color
	 *
	 * @return GDColor
	 */
	public function Color (GDColor $color = null) {
		if (func_num_args() != 0)
			$this->_color = $color;

		return $this->_color;
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

		imagettftext($image, $this->_size, $this->_position->angle, $this->_position->x, $this->_position->y, $this->_color->Allocate($image), $this->_font, $this->_text);
		$this->_color->Deallocate($image);

		return $image;
	}
}