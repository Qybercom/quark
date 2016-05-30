<?php
namespace Quark\ViewResources\Google\MapComponents;

use Quark\ViewResources\Google\IQuarkGoogleMapComponent;

use Quark\ViewResources\Google\GoogleMapPoint;

/**
 * Class GoogleMapMarker
 *
 * @package Quark\ViewResources\Google\MapComponents
 */
class GoogleMapMarker implements IQuarkGoogleMapComponent {
	const SIZE_TINY = 'tiny';
	const SIZE_MID = 'mid';
	const SIZE_SMALL = 'small';
	const SIZE_DEFAULT = '';

	/**
	 * @var GoogleMapPoint $_position
	 */
	private $_position;

	/**
	 * @var string $_size = self::SIZE_DEFAULT
	 */
	private $_size = self::SIZE_DEFAULT;

	/**
	 * @var string $_color = ''
	 */
	private $_color = '';

	/**
	 * @var string $_label = ''
	 */
	private $_label = '';

	/**
	 * @var string $_icon = ''
	 */
	private $_icon = '';

	/**
	 * @var bool $_shadow = true
	 */
	private $_shadow = true;

	/**
	 * @param GoogleMapPoint $position
	 * @param string $size = self::SIZE_DEFAULT
	 * @param string $color = ''
	 * @param string $label = ''
	 * @param string $icon = ''
	 * @param bool $shadow = true
	 */
	public function __construct (GoogleMapPoint $position, $size = self::SIZE_DEFAULT, $color = '', $label = '', $icon = '', $shadow = true) {
		$this->Position($position);
		$this->Size($size);
		$this->Color($color);
		$this->Label($label);
		$this->Icon($icon);
		$this->Shadow($shadow);
	}

	/**
	 * @param GoogleMapPoint $position = null
	 *
	 * @return GoogleMapPoint
	 */
	public function Position (GoogleMapPoint $position = null) {
		if (func_num_args() != 0)
			$this->_position = $position;

		return $this->_position;
	}

	/**
	 * @param string $size = self::SIZE_DEFAULT
	 *
	 * @return string
	 */
	public function Size ($size = self::SIZE_DEFAULT) {
		if (func_num_args() != 0)
			$this->_size = $size;

		return $this->_size;
	}

	/**
	 * @param string $color = ''
	 *
	 * @return string
	 */
	public function Color ($color = '') {
		if (func_num_args() != 0)
			$this->_color = $color;

		return $this->_color;
	}

	/**
	 * @param string $label = ''
	 *
	 * @return string
	 */
	public function Label ($label = '') {
		if (func_num_args() != 0)
			$this->_label = $label;

		return $this->_label;
	}

	/**
	 * @param string $icon = ''
	 *
	 * @return string
	 */
	public function Icon ($icon = '') {
		if (func_num_args() != 0)
			$this->_icon = $icon;

		return $this->_icon;
	}

	/**
	 * @param bool $shadow = true
	 *
	 * @return bool
	 */
	public function Shadow ($shadow = true) {
		if (func_num_args() != 0)
			$this->_shadow = $shadow;

		return $this->_shadow;
	}

	/**
	 * @return string
	 */
	public function Compile () {
		return $this->_position == null ? '' : ('&markers='
			. ($this->_size != self::SIZE_DEFAULT ? 'size:' . $this->_size . '|' : '')
			. ($this->_color != '' ? 'color:' . $this->_color . '|' : '')
			. ($this->_icon != '' ? 'icon:' . urlencode($this->_icon) . '|' : '')
			. (!$this->_shadow ? 'shadow:false|' : '')
			. $this->_position->Compile());
	}
}