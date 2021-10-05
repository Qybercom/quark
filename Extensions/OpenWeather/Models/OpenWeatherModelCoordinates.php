<?php
namespace Quark\Extensions\OpenWeather\Models;

use Quark\Extensions\OpenWeather\IQuarkOpenWeatherModel;

/**
 * Class OpenWeatherModelCoordinates
 *
 * @package Quark\Extensions\OpenWeather\Models
 */
class OpenWeatherModelCoordinates implements IQuarkOpenWeatherModel {
	/**
	 * @var float $_latitude = null
	 */
	private $_latitude = null;

	/**
	 * @var float $_longitude = null
	 */
	private $_longitude = null;

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function Latitude ($value = null) {
		if (func_num_args() != 0)
			$this->_latitude = $value;

		return $this->_latitude;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function Longitude ($value = null) {
		if (func_num_args() != 0)
			$this->_longitude = $value;

		return $this->_longitude;
	}

	/**
	 * @param object $source
	 *
	 * @return mixed
	 */
	public function OpenWeatherModelInit ($source) {
		if (isset($source->lat))
			$this->Latitude($source->lat);

		if (isset($source->lon))
			$this->Longitude($source->lon);
	}

	/**
	 * @return object
	 */
	public function OpenWeatherModelData () {
		return (object)array(
			'latitude' => $this->_latitude,
			'longitude' => $this->_latitude
		);
	}
}