<?php
namespace Quark\ViewResources\Google;

/**
 * Class MapMarker
 *
 * @package Quark\ViewResources\Google
 */
class MapMarker implements IMapComponent {
	const SIZE_TINY = 'tiny';
	const SIZE_MID = 'mid';
	const SIZE_SMALL = 'small';
	const SIZE_DEFAULT = '';

	/**
	 * @var MapPoint $_position
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
	 * @param MapPoint $position
	 * @param string $size = self::SIZE_DEFAULT
	 * @param string $color = ''
	 * @param string $label = ''
	 * @param string $icon = ''
	 * @param bool $shadow = true
	 */
	public function __construct (MapPoint $position, $size = self::SIZE_DEFAULT, $color = '', $label = '', $icon = '', $shadow = true) {
		$this->Position($position);
		$this->Size($size);
		$this->Color($color);
		$this->Label($label);
		$this->Icon($icon);
		$this->Shadow($shadow);
	}

	/**
	 * @param MapPoint $position = null
	 *
	 * @return MapPoint
	 */
	public function Position (MapPoint $position = null) {
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
		return '&markers='
			. ($this->_size != self::SIZE_DEFAULT ? 'size:tiny|' : '')
			. ($this->_color != '' ? 'color:green|' : '')
			. $this->_position->Compile();
	}
}