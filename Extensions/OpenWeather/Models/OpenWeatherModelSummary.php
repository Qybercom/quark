<?php
namespace Quark\Extensions\OpenWeather\Models;

use Quark\Extensions\OpenWeather\IQuarkOpenWeatherModel;

/**
 * Class OpenWeatherModelSummary
 *
 * @package Quark\Extensions\OpenWeather\Models
 */
class OpenWeatherModelSummary implements IQuarkOpenWeatherModel {
	/**
	 * @var float $_temperature = null
	 */
	private $_temperature = null;

	/**
	 * @var float $_temperatureMax = null
	 */
	private $_temperatureMax = null;

	/**
	 * @var float $_temperatureMin = null
	 */
	private $_temperatureMin = null;

	/**
	 * @var float $_temperatureFeelsLike = null
	 */
	private $_temperatureFeelsLike = null;

	/**
	 * @var float $_humidityRelative = null
	 */
	private $_humidityRelative = null;

	/**
	 * @var float $_pressure = null
	 */
	private $_pressure = null;

	/**
	 * @var float $_pressureLevelSea = null
	 */
	private $_pressureLevelSea = null;

	/**
	 * @var float $_pressureLevelGround = null
	 */
	private $_pressureLevelGround = null;

	/**
	 * @var int $_pressureHgDecimals = 0
	 */
	private $_pressureHgDecimals = 0;

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function Temperature ($value = null) {
		if (func_num_args() != 0)
			$this->_temperature = $value;
		
		return $this->_temperature;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function TemperatureMax ($value = null) {
		if (func_num_args() != 0)
			$this->_temperatureMax = $value;

		return $this->_temperatureMax;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function TemperatureMin ($value = null) {
		if (func_num_args() != 0)
			$this->_temperatureMin = $value;

		return $this->_temperatureMin;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function TemperatureFeelsLike ($value = null) {
		if (func_num_args() != 0)
			$this->_temperatureFeelsLike = $value;

		return $this->_temperatureFeelsLike;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function HumidityRelative ($value = null) {
		if (func_num_args() != 0)
			$this->_humidityRelative = $value;

		return $this->_humidityRelative;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function Pressure ($value = null) {
		if (func_num_args() != 0)
			$this->_pressure = $value;

		return $this->_pressure;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function PressureLevelSea ($value = null) {
		if (func_num_args() != 0)
			$this->_pressureLevelSea = $value;

		return $this->_pressureLevelSea;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function PressureLevelGround ($value = null) {
		if (func_num_args() != 0)
			$this->_pressureLevelGround = $value;

		return $this->_pressureLevelGround;
	}

	/**
	 * @param object $source
	 *
	 * @return mixed
	 */
	public function OpenWeatherModelInit ($source) {
		if (isset($source->temp))
			$this->Temperature($source->temp);

		if (isset($source->temp_max))
			$this->TemperatureMax($source->temp_max);

		if (isset($source->temp_min))
			$this->TemperatureMin($source->temp_min);

		if (isset($source->feels_like))
			$this->TemperatureFeelsLike($source->feels_like);

		if (isset($source->humidity))
			$this->HumidityRelative($source->humidity);

		if (isset($source->pressure))
			$this->Pressure($source->pressure);

		if (isset($source->sea_level))
			$this->PressureLevelSea($source->sea_level);

		if (isset($source->grnd_level))
			$this->PressureLevelGround($source->grnd_level);
	}

	/**
	 * @return object
	 */
	public function OpenWeatherModelData () {
		return (object)array(
			'temperature' => $this->_temperature,
			'temperatureMax' => $this->_temperatureMax,
			'temperatureMin' => $this->_temperatureMin,
			'temperatureFeelsLike' => $this->_temperatureFeelsLike,
			'temperaturePlus' => $this->_temperature > 0 ? '+' : '',
			'temperatureMaxPlus' => $this->_temperatureMax > 0 ? '+' : '',
			'temperatureMinPlus' => $this->_temperatureMin > 0 ? '+' : '',
			'temperatureFeelsLikePlus' => $this->_temperatureFeelsLike > 0 ? '+' : '',
			'humidityRelative' => $this->_humidityRelative,
			'pressure' => $this->_pressure,
			'pressureLevelSea' => $this->_pressureLevelSea,
			'pressureLevelGround' => $this->_pressureLevelGround,
			'pressureHg' => $this->_pressureHg($this->_pressure),
			'pressureHgLevelSea' => $this->_pressureHg($this->_pressureLevelSea),
			'pressureHgLevelGround' => $this->_pressureHg($this->_pressureLevelGround),
		);
	}

	/**
	 * @param int $count = 0
	 *
	 * @return int
	 */
	public function PressureHGDecimals ($count = 0) {
		if (func_num_args() != 0)
			$this->_pressureHgDecimals = $count;

		return $this->_pressureHgDecimals;
	}

	/**
	 * @param float $value = 0.0
	 *
	 * @return string
	 */
	private function _pressureHg ($value = 0.0) {
		return number_format($value * 0.75, $this->_pressureHgDecimals, '.', '');
	}
}