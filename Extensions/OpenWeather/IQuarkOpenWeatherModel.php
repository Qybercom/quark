<?php
namespace Quark\Extensions\OpenWeather;

/**
 * Interface IQuarkOpenWeatherModel
 *
 * @package Quark\Extensions\OpenWeather
 */
interface IQuarkOpenWeatherModel {
	/**
	 * @param object $source
	 *
	 * @return mixed
	 */
	public function OpenWeatherModelInit($source);

	/**
	 * @return object
	 */
	public function OpenWeatherModelData();
}