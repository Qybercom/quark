<?php
namespace Quark\Extensions\OpenWeather\Models;

use Quark\Extensions\OpenWeather\IQuarkOpenWeatherModel;

/**
 * Class OpenWeatherModelWeatherCondition
 *
 * @package Quark\Extensions\OpenWeather\Models
 */
class OpenWeatherModelWeatherCondition implements IQuarkOpenWeatherModel {
	/**
	 * @var int $_id = null
	 */
	private $_id = null;

	/**
	 * @var string $_main = null
	 */
	private $_main = null;

	/**
	 * @var string $_description = null
	 */
	private $_description = null;

	/**
	 * @var string $_icon = null
	 */
	private $_icon = null;

	/**
	 * @param int $id = null
	 *
	 * @return int
	 */
	public function ID ($id = null) {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param string $main = null
	 *
	 * @return string
	 */
	public function Main ($main = null) {
		if (func_num_args() != 0)
			$this->_main = $main;

		return $this->_main;
	}

	/**
	 * @param string $description = null
	 *
	 * @return string
	 */
	public function Description ($description = null) {
		if (func_num_args() != 0)
			$this->_description = $description;

		return $this->_description;
	}

	/**
	 * @param string $icon = null
	 *
	 * @return string
	 */
	public function Icon ($icon = null) {
		if (func_num_args() != 0)
			$this->_icon = $icon;

		return $this->_icon;
	}

	/**
	 * @param object $source
	 *
	 * @return mixed
	 */
	public function OpenWeatherModelInit ($source) {
		if (isset($source->id))
			$this->ID($source->id);

		if (isset($source->main))
			$this->Main($source->main);

		if (isset($source->description))
			$this->Description($source->description);

		if (isset($source->icon))
			$this->Icon($source->icon);
	}

	/**
	 * @return object
	 */
	public function OpenWeatherModelData () {
		return (object)array(
			'id' => $this->_id,
			'main' => $this->_main,
			'description' => $this->_description,
			'icon' => $this->_icon
		);
	}
}