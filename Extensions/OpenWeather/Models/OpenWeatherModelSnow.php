<?php
namespace Quark\Extensions\OpenWeather\Models;

use Quark\Extensions\OpenWeather\IQuarkOpenWeatherModelPhenomenon;

use Quark\Extensions\OpenWeather\OpenWeatherModelPhenomenonBehavior;

/**
 * Class OpenWeatherModelSnow
 *
 * @package Quark\Extensions\OpenWeather\Models
 */
class OpenWeatherModelSnow implements IQuarkOpenWeatherModelPhenomenon {
	const PHENOMENON_TYPE = 'snow';

	use OpenWeatherModelPhenomenonBehavior;

	/**
	 * @var float $_volumeHour1 = 0.0
	 */
	private $_volumeHour1 = 0.0;

	/**
	 * @var float $_volumeHour3 = 0.0
	 */
	private $_volumeHour3 = 0.0;

	/**
	 * @param float $value = 0.0
	 *
	 * @return float
	 */
	public function VolumeHour1 ($value = 0.0) {
		if (func_num_args() != 0)
			$this->_volumeHour1 = $value;

		return $this->_volumeHour1;
	}

	/**
	 * @param float $value = 0.0
	 *
	 * @return float
	 */
	public function VolumeHour3 ($value = 0.0) {
		if (func_num_args() != 0)
			$this->_volumeHour3 = $value;

		return $this->_volumeHour3;
	}

	/**
	 * @param object $source
	 *
	 * @return mixed
	 */
	public function OpenWeatherModelInit ($source) {
		if (isset($source->{'1h'}))
			$this->VolumeHour1($source->{'1h'});

		if (isset($source->{'3h'}))
			$this->VolumeHour3($source->{'3h'});
	}

	/**
	 * @return object
	 */
	public function OpenWeatherModelData () {
		return (object)array(
			'volumeHour1' => $this->_volumeHour1,
			'volumeHour3' => $this->_volumeHour3
		);
	}

	/**
	 * @return string
	 */
	public function OpenWeatherModelPhenomenonType () {
		return self::PHENOMENON_TYPE;
	}

	/**
	 * @return bool
	 */
	public function OpenWeatherModelPhenomenonDisplay () {
		// TODO: Implement OpenWeatherModelPhenomenonDisplay() method.
	}
}