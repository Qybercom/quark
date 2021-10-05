<?php
namespace Quark\Extensions\HereMaps;

/**
 * Class HereMapsCoordinates
 *
 * @package Quark\Extensions\HereMaps
 */
class HereMapsCoordinates {
	/**
	 * @var float $_latitude = 0.0
	 */
	private $_latitude = 0.0;

	/**
	 * @var float $_longitude = 0.0
	 */
	private $_longitude = 0.0;

	/**
	 * @param float $latitude = 0.0
	 * @param float $longitude = 0.0
	 */
	public function __construct ($latitude = 0.0, $longitude = 0.0) {
		$this->Latitude($latitude);
		$this->Longitude($longitude);
	}

	/**
	 * @param float $latitude = 0.0
	 *
	 * @return float
	 */
	public function Latitude ($latitude = 0.0) {
		if (func_num_args() != 0)
			$this->_latitude = $latitude;

		return $this->_latitude;
	}

	/**
	 * @param float $longitude = 0.0
	 *
	 * @return float
	 */
	public function Longitude ($longitude = 0.0) {
		if (func_num_args() != 0)
			$this->_longitude = $longitude;

		return $this->_longitude;
	}

	/**
	 * @return string
	 */
	public function Stringify () {
		return $this->_latitude . ',' . $this->_longitude;
	}
}