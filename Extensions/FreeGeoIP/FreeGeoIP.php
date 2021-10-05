<?php
namespace Quark\Extensions\FreeGeoIP;

use Quark\IOProcessors\QuarkCSVIOProcessor;
use Quark\IQuarkExtension;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkXMLIOProcessor;

/**
 * Class FreeGeoIP
 *
 * @package Quark\Extensions\FreeGeoIP
 */
class FreeGeoIP implements IQuarkExtension {
	const URL_API = 'https://freegeoip.app/';

	const FORMAT_JSON = 'json';
	const FORMAT_XML = 'xml';
	const FORMAT_CSV = 'csv';

	/**
	 * @var string $_address = ''
	 */
	private $_address = '';

	/**
	 * @var string $_language = null
	 */
	private $_language = null;

	/**
	 * @var string $_ip = ''
	 */
	private $_ip = '';

	/**
	 * @var string $_countryCode = ''
	 */
	private $_countryCode = '';

	/**
	 * @var string $_counttryName = ''
	 */
	private $_counttryName = '';

	/**
	 * @var string $_regionCode = ''
	 */
	private $_regionCode = '';

	/**
	 * @var string $_regionName = ''
	 */
	private $_regionName = '';

	/**
	 * @var string $_city = ''
	 */
	private $_city = '';

	/**
	 * @var string $_zip = ''
	 */
	private $_zip = '';

	/**
	 * @var string $_timezone
	 */
	private $_timezone = '';

	/**
	 * @var string $_latitude = ''
	 */
	private $_latitude = '';

	/**
	 * @var string $_longitude = ''
	 */
	private $_longitude = '';

	/**
	 * @param string $address = ''
	 */
	public function __construct ($address = '') {
		$this->Address($address);
	}

	/**
	 * @param string $address = ''
	 *
	 * @return string
	 */
	public function Address ($address = '') {
		if (func_num_args() != 0)
			$this->_address = $address;

		return $this->_address;
	}

	/**
	 * @param string $language = null
	 *
	 * @return string
	 */
	public function Language ($language = null) {
		if (func_num_args() != 0)
			$this->_language = $language;

		return $this->_language;
	}

	/**
	 * @param string $ip = ''
	 *
	 * @return string
	 */
	public function IP ($ip = '') {
		if (func_num_args() != 0)
			$this->_ip = $ip;

		return $this->_ip;
	}

	/**
	 * @param string $code = ''
	 *
	 * @return string
	 */
	public function CountryCode ($code = '') {
		if (func_num_args() != 0)
			$this->_countryCode = $code;

		return $this->_countryCode;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function CountryName ($name = '') {
		if (func_num_args() != 0)
			$this->_countryName = $name;

		return $this->_countryName;
	}

	/**
	 * @param string $code = ''
	 *
	 * @return string
	 */
	public function RegionCode ($code = '') {
		if (func_num_args() != 0)
			$this->_regionCode = $code;

		return $this->_regionCode;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function RegionName ($name = '') {
		if (func_num_args() != 0)
			$this->_regionName = $name;

		return $this->_regionName;
	}

	/**
	 * @param string $city = ''
	 *
	 * @return string
	 */
	public function City ($city = '') {
		if (func_num_args() != 0)
			$this->_city = $city;

		return $this->_city;
	}

	/**
	 * @param string $zip = ''
	 *
	 * @return string
	 */
	public function ZIP ($zip = '') {
		if (func_num_args() != 0)
			$this->_zip = $zip;

		return $this->_zip;
	}

	/**
	 * @param string $timezone = ''
	 *
	 * @return string
	 */
	public function Timezone ($timezone = '') {
		if (func_num_args() != 0)
			$this->_timezone = $timezone;

		return $this->_timezone;
	}

	/**
	 * @param string $latitude = ''
	 *
	 * @return string
	 */
	public function Latitude ($latitude = '') {
		if (func_num_args() != 0)
			$this->_latitude = $latitude;

		return $this->_latitude;
	}

	/**
	 * @param string $longitude = ''
	 *
	 * @return string
	 */
	public function Longitude ($longitude = '') {
		if (func_num_args() != 0)
			$this->_longitude = $longitude;

		return $this->_longitude;
	}

	/**
	 * @param QuarkDTO $dto = null
	 *
	 * @return FreeGeoIP
	 */
	public static function FromDTO (QuarkDTO $dto = null) {
		if ($dto == null) return null;

		$out = new self();

		if (isset($dto->ip))
			$out->IP($dto->ip);

		if (isset($dto->country_code))
			$out->CountryCode($dto->country_code);

		if (isset($dto->country_name))
			$out->CountryName($dto->country_name);

		if (isset($dto->region_code))
			$out->RegionCode($dto->region_code);

		if (isset($dto->region_name))
			$out->RegionName($dto->region_name);

		if (isset($dto->city))
			$out->City($dto->city);

		if (isset($dto->zip_code))
			$out->ZIP($dto->zip_code);

		if (isset($dto->time_zone))
			$out->Timezone($dto->time_zone);

		if (isset($dto->latitude))
			$out->Latitude($dto->latitude);

		if (isset($dto->longitude))
			$out->Longitude($dto->longitude);

		return $out;
	}

	/**
	 * @param string $address = ''
	 * @param string $language = null
	 * @param string $format = self::FORMAT_JSON
	 *
	 * @return QuarkDTO|bool
	 */
	public static function API ($address = '', $language = null, $format = self::FORMAT_JSON) {
		$processor = null;
		if ($format == self::FORMAT_JSON) $processor = new QuarkJSONIOProcessor();
		if ($format == self::FORMAT_XML) $processor = new QuarkXMLIOProcessor();
		if ($format == self::FORMAT_CSV) $processor = new QuarkCSVIOProcessor();

		$request = QuarkDTO::ForGET();
		if ($language != null)
			$request->Header(QuarkDTO::HEADER_ACCEPT_LANGUAGE, $language);

		return QuarkHTTPClient::To(self::URL_API . $format . '/' . $address, $request, new QuarkDTO($processor));
	}

	/**
	 * @param string $address = ''
	 * @param string $language = null
	 *
	 * @return FreeGeoIP
	 */
	public static function Info ($address = '', $language = null) {
		$data = self::API($address, $language);
		if (!$data) return null;

		$out = self::FromDTO($data);
		$out->Address($address);
		$out->Language($language);

		return $out;
	}
}