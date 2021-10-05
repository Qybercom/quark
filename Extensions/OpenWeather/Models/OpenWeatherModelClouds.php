<?php
namespace Quark\Extensions\OpenWeather\Models;

use Quark\Extensions\OpenWeather\IQuarkOpenWeatherModelPhenomenon;

use Quark\Extensions\OpenWeather\OpenWeatherModelPhenomenonBehavior;

/**
 * Class OpenWeatherModelClouds
 *
 * @package Quark\Extensions\OpenWeather\Models
 */
class OpenWeatherModelClouds implements IQuarkOpenWeatherModelPhenomenon {
	const PHENOMENON_TYPE = 'clouds';

	use OpenWeatherModelPhenomenonBehavior;

	/**
	 * @var float $_cloudiness = null
	 */
	private $_cloudiness = null;

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function Cloudiness ($value = null) {
		if (func_num_args() != 0)
			$this->_cloudiness = $value;

		return $this->_cloudiness;
	}

	/**
	 * @param object $source
	 *
	 * @return mixed
	 */
	public function OpenWeatherModelInit ($source) {
		if (isset($source->all))
			$this->Cloudiness($source->all);
	}

	/**
	 * @return object
	 */
	public function OpenWeatherModelData () {
		return (object)array(
			'cloudiness' => $this->_cloudiness
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
		return $this->_cloudiness != 0;
	}
}