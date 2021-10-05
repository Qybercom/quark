<?php
namespace Quark\Extensions\OpenWeather;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkLanguage;

/**
 * Class OpenWeather
 *
 * @package Quark\Extensions\OpenWeather
 */
class OpenWeather implements IQuarkExtension {
	const URL_API = 'https://api.openweathermap.org/data/';
	const URL_ICON_WEATHER = 'https://openweathermap.org/img/wn/';

	const VERSION_2_5 = '2.5';
	const VERSION_DEFAULT = self::VERSION_2_5;

	const UNITS_METRIC = 'metric';
	const UNITS_IMPERIAL = 'imperial';
	const UNITS_STANDARD = 'standard';

	const UNITS_VALUE_TEMPERATURE = 'temperature';
	const UNITS_VALUE_PRESSURE = 'pressure';
	const UNITS_VALUE_PRESSURE_HG = 'pressureHg';
	const UNITS_VALUE_SPEED = 'speed';
	const UNITS_VALUE_PERCENT = 'percent';
	const UNITS_VALUE_LENGTH = 'length';

	const ICON_SIZE1 = 1;
	const ICON_SIZE2 = 2;
	const ICON_SIZE4 = 4;

	/**
	 * @var OpenWeatherConfig $_config
	 */
	private $_config;

	/**
	 * @var string $_units = self::UNITS_STANDARD
	 */
	private $_units = self::UNITS_STANDARD;

	/**
	 * @var string $_language = QuarkLanguage::ANY
	 */
	private $_language = QuarkLanguage::ANY;

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		$this->_config = Quark::Config()->Extension($config);

		$this->Units($this->_config->Units());
		$this->Language($this->_config->Language());
	}

	/**
	 * @return OpenWeatherConfig
	 */
	public function &Config () {
		return $this->_config;
	}

	/**
	 * @param string $units = self::UNITS_STANDARD
	 *
	 * @return string
	 */
	public function Units ($units = self::UNITS_STANDARD) {
		if (func_num_args() != 0)
			$this->_units = $units;

		return $this->_units;
	}

	/**
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return string
	 */
	public function Language ($language = QuarkLanguage::ANY) {
		if (func_num_args() != 0)
			$this->_language = $language;

		return $this->_language;
	}

	/**
	 * @param string $url = ''
	 * @param array $params = []
	 *
	 * @return QuarkDTO|bool
	 */
	public function API ($url = '', $params = []) {
		$params['appId'] = $this->_config->AppID();
		$params['units'] = $this->_units;

		if ($this->_language != QuarkLanguage::ANY)
			$params['lang'] = explode('-', $this->_language)[0];

		$request = QuarkDTO::ForGET();
		$request->URIParams($params);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To(self::URL_API . $this->_config->Version() . $url, $request, $response);
	}

	/**
	 * @param array $params = []
	 *
	 * @return OpenWeatherState
	 */
	public function Weather ($params = []) {
		$response = $this->API('/weather', $params);

		return $response instanceof QuarkDTO ? OpenWeatherState::FromDTO($response, $this->_units) : null;
	}

	/**
	 * @param string $city
	 * @param string $state = ''
	 * @param string $country = ''
	 *
	 * @return OpenWeatherState
	 */
	public function WeatherByCityAndCountry ($city, $state = '', $country = '') {
		$parts = array($city, $state);
		if (func_num_args() > 2) $parts[] = $country;

		return $this->Weather(array(
			'q' => implode(',', $parts)
		));
	}

	/**
	 * @param int $id
	 *
	 * @return OpenWeatherState
	 */
	public function WeatherByCityID ($id) {
		return $this->Weather(array('id' => $id));
	}

	/**
	 * @param float $latitude
	 * @param float $longitude
	 *
	 * @return OpenWeatherState
	 */
	public function WeatherByCoordinates ($latitude, $longitude) {
		return $this->Weather(array(
			'lat' => $latitude,
			'lon' => $longitude
		));
	}

	/**
	 * @param string $zip
	 *
	 * @return OpenWeatherState
	 */
	public function WeatherByZIP ($zip) {
		return $this->Weather(array(
			'zip' => $zip
		));
	}

	/**
	 * @param string $timezone = ''
	 * @param string $country = ''
	 *
	 * @return OpenWeatherState
	 */
	public function WeatherByTimezoneAndCountry ($timezone = '', $country = '') {
		$zone = explode('/', $timezone);

		return isset($zone[1]) ? $this->WeatherByCityAndCountry($zone[1], $country) : null;
	}

	/**
	 * @param string $code = ''
	 * @param int $size = self::ICON_SIZE1
	 *
	 * @return string
	 */
	public static function Icon ($code = '', $size = self::ICON_SIZE1) {
		return self::URL_ICON_WEATHER . '/' . $code . ($size == self::ICON_SIZE1 ? '' : '@' . $size . 'x') . '.png';
	}
}