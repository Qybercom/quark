<?php
namespace Quark\Extensions\OpenWeather;

/**
 * Interface IQuarkOpenWeatherModelPhenomenon
 *
 * @package Quark\Extensions\OpenWeather
 */
interface IQuarkOpenWeatherModelPhenomenon extends IQuarkOpenWeatherModel {
	/**
	 * @return string
	 */
	public function OpenWeatherModelPhenomenonType();

	/**
	 * @return bool
	 */
	public function OpenWeatherModelPhenomenonDisplay();
}