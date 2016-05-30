<?php
namespace Quark\ViewResources\Google\MapComponents;

use Quark\ViewResources\Google\IQuarkGoogleMapComponent;

use Quark\ViewResources\Google\GoogleMapPoint;

/**
 * Class GoogleMapPath
 *
 * @package Quark\ViewResources\Google\MapComponents
 */
class GoogleMapPath implements IQuarkGoogleMapComponent {
	const DEFAULT_WEIGHT = 1;
	const DEFAULT_COLOR = '0x00000000';
	const DEFAULT_FILL_COLOR = '0xFFFF0033';

	/**
	 * @var int $_weight = self::DEFAULT_WEIGHT
	 */
	private $_weight = self::DEFAULT_WEIGHT;

	/**
	 * @var string $_color = self::DEFAULT_COLOR
	 */
	private $_color = self::DEFAULT_COLOR;

	/**
	 * @var string $_fillColor = self::DEFAULT_FILL_COLOR
	 */
	private $_fillColor = self::DEFAULT_FILL_COLOR;

	/**
	 * @var GoogleMapPoint[] $_points = []
	 */
	private $_points = array();

	/**
	 * @param int $weight = self::DEFAULT_WEIGHT
	 * @param string $color = self::DEFAULT_COLOR
	 * @param string $fillColor = self::DEFAULT_FILL_COLOR
	 * @param GoogleMapPoint[] $points = []
	 */
	public function __construct ($weight = self::DEFAULT_WEIGHT, $color = self::DEFAULT_COLOR, $fillColor = self::DEFAULT_FILL_COLOR, $points = []) {
		$this->Weight($weight);
		$this->Color($color);
		$this->FillColor($fillColor);

		if (is_array($points))
			foreach ($points as $point)
				if ($point instanceof GoogleMapPoint)
					$this->Point($point);
	}

	/**
	 * @param int $weight = self::DEFAULT_WEIGHT
	 *
	 * @return int
	 */
	public function Weight ($weight = self::DEFAULT_WEIGHT) {
		if (func_num_args() != 0)
			$this->_weight = $weight;

		return $this->_weight;
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
	 * @param string $color = self::DEFAULT_FILL_COLOR
	 *
	 * @return string
	 */
	public function FillColor ($color = self::DEFAULT_FILL_COLOR) {
		if (func_num_args() != 0)
			$this->_fillColor = $color;

		return $this->_fillColor;
	}

	/**
	 * @param GoogleMapPoint $point = null
	 *
	 * @return GoogleMapPath
	 */
	public function Point (GoogleMapPoint $point = null) {
		if ($point != null)
			$this->_points[] = $point;

		return $this;
	}
	
	/**
	 * @return string
	 */
	public function Compile () {
		if (sizeof($this->_points) == 0) return '';

		$points = array();

		foreach ($this->_points as $point)
			$points[] = $point->Compile();

		return '&path='
			. ($this->_weight != '' ? 'weight:' . $this->_weight . '|' : '')
			. ($this->_color != '' ? 'color:' . $this->_color . '|' : '')
			. ($this->_fillColor != '' ? 'fillcolor:' . $this->_fillColor . '|' : '')
			. implode('|', $points);
	}
}