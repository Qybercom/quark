<?php
namespace Quark\Extensions\OpenWeather\Models;

use Quark\Extensions\OpenWeather\IQuarkOpenWeatherModelPhenomenon;

use Quark\Extensions\OpenWeather\OpenWeatherModelPhenomenonBehavior;

/**
 * Class OpenWeatherModelWind
 *
 * @package Quark\Extensions\OpenWeather\Models
 */
class OpenWeatherModelWind implements IQuarkOpenWeatherModelPhenomenon {
	const PHENOMENON_TYPE = 'wind';

	const DIRECTION_N = 'N';
	const DIRECTION_S = 'S';
	const DIRECTION_E = 'E';
	const DIRECTION_W = 'W';
	const DIRECTION_NE = 'NE';
	const DIRECTION_NW = 'NW';
	const DIRECTION_SW = 'SW';
	const DIRECTION_SE = 'SE';

	use OpenWeatherModelPhenomenonBehavior;

	/**
	 * @var string[] $_directions = []
	 */
	private static $_directions = array(
		'deg90' => self::DIRECTION_N,
		'deg270' => self::DIRECTION_S,
		'deg0' => self::DIRECTION_E,
		'deg180' => self::DIRECTION_W,
		'deg45' => self::DIRECTION_NE,
		'deg135' => self::DIRECTION_NW,
		'deg225' => self::DIRECTION_SW,
		'deg315' => self::DIRECTION_SE
	);
	
	/**
	 * @var float $_speed = null
	 */
	private $_speed = null;

	/**
	 * @var float $_direction = null
	 */
	private $_direction = null;

	/**
	 * @var float $_flaw = null
	 */
	private $_flaw = null;

	/**
	 * @return string[]
	 */
	public static function Directions () {
		return self::$_directions;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function Speed ($value = null) {
		if (func_num_args() != 0)
			$this->_speed = $value;

		return $this->_speed;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function Direction ($value = null) {
		if (func_num_args() != 0)
			$this->_direction = $value;

		return $this->_direction;
	}

	/**
	 * @return string
	 */
	public function DirectionText () {
		$direction = 'deg' . $this->_direction;

		return isset(self::$_directions[$direction]) ? self::$_directions[$direction] : null;
	}

	/**
	 * @param float $value = null
	 *
	 * @return float
	 */
	public function Flaw ($value = null) {
		if (func_num_args() != 0)
			$this->_flaw = $value;

		return $this->_flaw;
	}

	/**
	 * @param object $source
	 *
	 * @return mixed
	 */
	public function OpenWeatherModelInit ($source) {
		if (isset($source->speed))
			$this->Speed($source->speed);

		if (isset($source->deg))
			$this->Direction($source->deg);

		if (isset($source->gust))
			$this->Flaw($source->gust);
	}

	/**
	 * @return object
	 */
	public function OpenWeatherModelData () {
		return (object)array(
			'speed' => $this->_speed,
			'direction' => $this->_direction,
			'directionText' => $this->DirectionText(),
			'flaw' => $this->_flaw
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