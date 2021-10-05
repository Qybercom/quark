<?php
namespace Quark\Extensions\OpenWeather;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkObject;
use Quark\QuarkView;

use Quark\Extensions\OpenWeather\Models\OpenWeatherModelCoordinates;
use Quark\Extensions\OpenWeather\Models\OpenWeatherModelSummary;
use Quark\Extensions\OpenWeather\Models\OpenWeatherModelWeatherCondition;
use Quark\Extensions\OpenWeather\Models\OpenWeatherModelClouds;
use Quark\Extensions\OpenWeather\Models\OpenWeatherModelWind;
use Quark\Extensions\OpenWeather\Models\OpenWeatherModelRain;
use Quark\Extensions\OpenWeather\Models\OpenWeatherModelSnow;

/**
 * Class OpenWeatherState
 *
 * @package Quark\Extensions\OpenWeather
 */
class OpenWeatherState {
	/**
	 * @var OpenWeatherModelCoordinates $_coordinates = null
	 */
	private $_coordinates = null;

	/**
	 * @var QuarkDate $_date = null
	 */
	private $_date = null;

	/**
	 * @var QuarkDate $_dateSunrise = null
	 */
	private $_dateSunrise = null;

	/**
	 * @var QuarkDate $_dateSunset = null
	 */
	private $_dateSunset = null;

	/**
	 * @var string $_timezone = null
	 */
	private $_timezone = null;

	/**
	 * @var string $_countryCode = ''
	 */
	private $_countryCode = '';

	/**
	 * @var string $_countryName = ''
	 */
	private $_countryName = '';

	/**
	 * @var int $_cityID = 0
	 */
	private $_cityID = 0;

	/**
	 * @var string $_cityName = ''
	 */
	private $_cityName = '';

	/**
	 * @var OpenWeatherModelWeatherCondition[] $_conditions = []
	 */
	private $_conditions = array();

	/**
	 * @var IQuarkOpenWeatherModelPhenomenon[] $_phenomena = []
	 */
	private $_phenomena = array();

	/**
	 * @var OpenWeatherModelSummary $_summary = null
	 */
	private $_summary = null;

	/**
	 * @var string $_units = OpenWeather::UNITS_STANDARD
	 */
	private $_units = OpenWeather::UNITS_STANDARD;

	/**
	 * @param string $units = OpenWeather::UNITS_STANDARD
	 */
	public function __construct ($units = OpenWeather::UNITS_STANDARD) {
		$this->Units($units);
	}

	/**
	 * @param string $units = OpenWeather::UNITS_STANDARD
	 *
	 * @return string
	 */
	public function Units ($units = OpenWeather::UNITS_STANDARD) {
		if (func_num_args() != 0)
			$this->_units = $units;

		return $this->_units;
	}

	/**
	 * @param OpenWeatherModelCoordinates $coordinates = null
	 *
	 * @return OpenWeatherModelCoordinates
	 */
	public function &Coordinates (OpenWeatherModelCoordinates $coordinates = null) {
		if (func_num_args() != 0)
			$this->_coordinates = $coordinates;

		return $this->_coordinates;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function &Date (QuarkDate $date= null) {
		if (func_num_args() != 0)
			$this->_date = $date;

		return $this->_date;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function &DateSunrise (QuarkDate $date= null) {
		if (func_num_args() != 0)
			$this->_dateSunrise = $date;

		return $this->_dateSunrise;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function &DateSunset (QuarkDate $date= null) {
		if (func_num_args() != 0)
			$this->_dateSunset = $date;

		return $this->_dateSunset;
	}

	/**
	 * @param string $timezone = null
	 *
	 * @return string
	 */
	public function Timezone ($timezone = null) {
		if (func_num_args() != 0)
			$this->_timezone = $timezone;

		return $this->_timezone;
	}

	/**
	 * @param string $code = null
	 *
	 * @return string
	 */
	public function CountryCode ($code = null) {
		if (func_num_args() != 0)
			$this->_countryCode = $code;

		return $this->_countryCode;
	}

	/**
	 * @param string $name = null
	 *
	 * @return string
	 */
	public function CountryName ($name = null) {
		if (func_num_args() != 0)
			$this->_countryName = $name;

		return $this->_countryName;
	}

	/**
	 * @param string $id = null
	 *
	 * @return string
	 */
	public function CityID ($id = null) {
		if (func_num_args() != 0)
			$this->_cityID = $id;

		return $this->_cityID;
	}

	/**
	 * @param string $name = null
	 *
	 * @return string
	 */
	public function CityName ($name = null) {
		if (func_num_args() != 0)
			$this->_cityName = $name;

		return $this->_cityName;
	}

	/**
	 * @return OpenWeatherModelWeatherCondition[]
	 */
	public function &Conditions () {
		return $this->_conditions;
	}

	/**
	 * @param OpenWeatherModelWeatherCondition $condition = null
	 *
	 * @return OpenWeatherState
	 */
	public function &Condition (OpenWeatherModelWeatherCondition $condition = null) {
		if ($condition != null)
			$this->_conditions[] = $condition;

		return $this;
	}

	/**
	 * @return IQuarkOpenWeatherModelPhenomenon[]
	 */
	public function &Phenomena () {
		return $this->_phenomena;
	}

	/**
	 * @param IQuarkOpenWeatherModelPhenomenon $phenomenon = null
	 *
	 * @return OpenWeatherState
	 */
	public function &Phenomenon (IQuarkOpenWeatherModelPhenomenon $phenomenon = null) {
		if ($phenomenon != null)
			$this->_phenomena[] = $phenomenon;

		return $this;
	}

	/**
	 * @param OpenWeatherModelSummary $summary = null
	 *
	 * @return OpenWeatherModelSummary
	 */
	public function &Summary (OpenWeatherModelSummary $summary = null) {
		if (func_num_args() != 0)
			$this->_summary = $summary;

		return $this->_summary;
	}

	/**
	 * @param string $localization = ''
	 * @param string[] $localizationPhenomenon = []
	 * @param string[] $localizationUnits = []
	 * @param string $dateFormat = QuarkDate::FORMAT_ISO_FULL
	 *
	 * @return string
	 */
	public function Description ($localization = '', $localizationPhenomenon = [], $localizationUnits = [], $dateFormat = QuarkDate::FORMAT_ISO_FULL) {
		$data = array(
			'countryCode' => $this->CountryCode(),
			'countryName' => $this->CountryName(),
			'cityID' => $this->CityID(),
			'cityName' => $this->CityName(),
			'timezone' => $this->Timezone(),
			'date' => $this->_date->Format($dateFormat),
			'dateSunrise' => $this->_dateSunrise->Format($dateFormat),
			'dateSunset' => $this->_dateSunset->Format($dateFormat),
			'coordinates' => $this->_coordinates->OpenWeatherModelData(),
			'summary' => $this->_summary->OpenWeatherModelData(),
			'conditions' => $this->DescriptionCondition(),
			'phenomena' => $this->DescriptionPhenomenon($localizationPhenomenon, $localizationUnits),
			'units' => $this->_localizationUnits($localizationUnits)
		);

		return $this->_localization($localization, $data);
	}

	/**
	 * @return string
	 */
	public function DescriptionCondition () {
		$out = array();

		foreach ($this->_conditions as $i => &$condition)
			$out[] = $condition->Description();

		return implode(', ', $out);
	}

	/**
	 * @param string[] $localization = []
	 * @param string[] $localizationUnits = []
	 *
	 * @return string
	 */
	public function DescriptionPhenomenon ($localization = [], $localizationUnits = []) {
		$out = array();
		$type = null;
		$display = null;
		$data = array();

		foreach ($this->_phenomena as $i => &$phenomenon) {
			$type = $phenomenon->OpenWeatherModelPhenomenonType();
			$display = $phenomenon->OpenWeatherModelPhenomenonDisplay();

			if (isset($localization[$type]) && ($display || $display === null)) {
				$data = $phenomenon->OpenWeatherModelData();
				$data->units = $this->_localizationUnits($localizationUnits);

				$out[] = $this->_localization($localization[$type], $data);
			}
		}

		return implode(', ', $out);
	}

	/**
	 * @param IQuarkOpenWeatherModel $model
	 * @param string $property = ''
	 * @param $source = null
	 *
	 * @return bool
	 */
	private function _model (IQuarkOpenWeatherModel $model, $property = '', $source = null) {
		if (!method_exists($this, $property)) return false;

		$model->OpenWeatherModelInit($source);
		$this->$property($model);

		return true;
	}

	/**
	 * @param string $key = ''
	 * @param $data = []
	 *
	 * @return string
	 */
	private function _localization ($key = '', $data = []) {
		return QuarkView::TemplateString(Quark::Config()->CurrentLocalizationOf($key), $data);
	}

	/**
	 * @param string[] $localization = []
	 *
	 * @return string[]
	 */
	private function _localizationUnits ($localization = []) {
		$out = array();
		$buffer = null;
		$units = $this->_units;

		foreach ($localization as $units => &$key) {
			$buffer = QuarkObject::isTraversable($key) ? (object)$key : null;

			$out[$units] = $this->_localization(isset($buffer->$units) ? $buffer->$units : $key);
		}

		return $out;
	}

	/**
	 * @param QuarkDTO $dto = null
	 * @param string $units = OpenWeather::UNITS_STANDARD
	 *
	 * @return OpenWeatherState
	 */
	public static function FromDTO (QuarkDTO $dto = null, $units = OpenWeather::UNITS_STANDARD) {
		if ($dto == null) return null;

		$out = new self($units);

		if (isset($dto->id))
			$out->CityID($dto->id);

		if (isset($dto->name))
			$out->CityName($dto->name);

		if (isset($dto->sys->country))
			$out->CountryCode($dto->sys->country);

		if (isset($dto->dt))
			$out->Date(QuarkDate::FromTimestamp($dto->dt));

		/*if (isset($dto->timezone)) {
			$timezones = QuarkDate::TimezoneListByOffset($dto->timezone);
			var_dump($timezones);

			if (sizeof($timezones) != 0)
				$out->Timezone($timezones[0]);
		}*/

		if (isset($dto->sys->sunrise))
			$out->DateSunrise(QuarkDate::FromTimestamp($dto->sys->sunrise));

		if (isset($dto->sys->sunset))
			$out->DateSunset(QuarkDate::FromTimestamp($dto->sys->sunset));

		if (isset($dto->coord))
			$out->_model(new OpenWeatherModelCoordinates(), 'Coordinates', $dto->coord);

		if (isset($dto->main))
			$out->_model(new OpenWeatherModelSummary(), 'Summary', $dto->main);

		if (isset($dto->weather) && is_array($dto->weather))
			foreach ($dto->weather as $i => &$item)
				$out->_model(new OpenWeatherModelWeatherCondition(), 'Condition', $item);

		if (isset($dto->clouds))
			$out->_model(new OpenWeatherModelClouds(), 'Phenomenon', $dto->clouds);

		if (isset($dto->wind))
			$out->_model(new OpenWeatherModelWind(), 'Phenomenon', $dto->wind);

		if (isset($dto->rain))
			$out->_model(new OpenWeatherModelRain(), 'Phenomenon', $dto->rain);

		if (isset($dto->snow))
			$out->_model(new OpenWeatherModelSnow(), 'Phenomenon', $dto->snow);

		return $out;
	}
}