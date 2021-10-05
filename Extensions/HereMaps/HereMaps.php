<?php
namespace Quark\Extensions\HereMaps;

use Quark\IQuarkExtension;
use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkLanguage;

/**
 * Class HereMaps
 *
 * @package Quark\Extensions\HereMaps
 */
class HereMaps implements IQuarkExtension {
	const URL_API = '';

	const API_SEARCH = 'search.hereapi.com/v1';
	const API_ROUTER = 'router.hereapi.com/v8';

	const SERVICE_SEARCH_GEOCODE = 'geocode';
	const SERVICE_SEARCH_GEOCODE_REVERSE = 'revgeocode';
	const SERVICE_SEARCH_AUTOCOMPLETE = 'autocomplete';

	const VERSION_MAP_IMAGE = '1.6';

	/**
	 * @var HereMapsConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @return HereMapsConfig
	 */
	public function &Config () {
		return $this->_config;
	}

	/**
	 * @param string $method = QuarkDTO::METHOD_GET
	 * @param string $url = ''
	 * @param array $data = []
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return QuarkDTO|bool
	 */
	public function API ($method = QuarkDTO::METHOD_GET, $url = '', $data = [], $language = QuarkLanguage::ANY) {
		$query = array(
			'apiKey' => $this->_config->APIKey()
		);

		if ($language != QuarkLanguage::ANY)
			$query['lang'] = $language;

		$request = QuarkDTO::ForRequest($method, new QuarkJSONIOProcessor());

		if ($method == QuarkDTO::METHOD_GET)
			$request->URIParams($data);

		$request->Data($data);

		$request->URI()->ParamsMerge($query);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To($url, $request, $response);
	}

	/**
	 * @param string $service = ''
	 * @param array $query = []
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return QuarkDTO|bool
	 */
	public function APISearch ($service = '', $query = [], $language = QuarkLanguage::ANY) {
		return $this->API(
			QuarkDTO::METHOD_GET,
			'https://' . $service . '.' . self::API_SEARCH . '/',
			$query,
			$language
		);
	}

	/**
	 * @param string $query = ''
	 * @param string $language = QuarkLanguage::ANY
	 */
	public function SearchGeocode ($query = '', $language = QuarkLanguage::ANY) {

	}

	/**
	 * @param string $query = ''
	 * @param string $language = QuarkLanguage::ANY
	 */
	public function SearchGeocodeReverse ($query = '', $language = QuarkLanguage::ANY) {

	}

	/**
	 * @param string $mode
	 * @param HereMapsCoordinates $origin = null
	 * @param HereMapsCoordinates $destination = null
	 */
	public function RouterCalculate ($mode, HereMapsCoordinates $origin = null, HereMapsCoordinates $destination = null) {

	}

	/**
	 * @param HereMapsCoordinates $coordinates = null
	 * @param string $version = self::VERSION_MAP_IMAGE
	 *
	 * @return string
	 */
	public function MapImage (HereMapsCoordinates $coordinates = null, $version = self::VERSION_MAP_IMAGE) {
		return $coordinates == null ? '' : 'https://image.maps.ls.hereapi.com/mia/' . $version . '/mapview?apiKey=' . $this->_config->APIKey() . '&c=' . $coordinates->Stringify();
	}
}