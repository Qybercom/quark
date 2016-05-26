<?php
namespace Quark\ViewResources\Google;

use Quark\IQuarkViewResource;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkJSViewResourceType;
use Quark\QuarkLocalCoreJSViewResource;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class GoogleMap
 *
 * @package Quark\ViewResources\Google
 */
class GoogleMap implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
	const FORMAT_PNG = 'png';
	const FORMAT_PNG8 = 'png8';
	const FORMAT_PNG32 = 'png32';
	const FORMAT_GIF = 'gif';
	const FORMAT_JPG = 'jpg';
	const FORMAT_JPG_BASELINE = 'jpg-baseline';

	const TYPE_ROADMAP = 'GoogleMap.Type.Roadmap';
	const TYPE_SATELLITE = 'GoogleMap.Type.Satellite';
	const TYPE_TERRAIN = 'GoogleMap.Type.Terrain';
	const TYPE_HYBRID = 'GoogleMap.Type.Hybrid';
	
	const DEFAULT_ZOOM = 8;
	const DEFAULT_WIDTH = 320;
	const DEFAULT_HEIGHT = 240;
	const DEFAULT_SCALE = 1;
	const DEFAULT_SENSOR = false;

	const GEOCODE_STATUS_OK = 'OK';
	const GEOCODE_STATUS_ZERO_RESULTS = 'ZERO_RESULTS';
	const GEOCODE_STATUS_OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT';
	const GEOCODE_STATUS_REQUEST_DENIED = 'REQUEST_DENIED';
	const GEOCODE_STATUS_INVALID_REQUEST = 'INVALID_REQUEST';
	const GEOCODE_STATUS_UNKNOWN_ERROR = 'UNKNOWN_ERROR';

	/**
	 * @var MapPoint $_center
	 */
	private $_center;

	/**
	 * @var int $_zoom = self::DEFAULT_ZOOM
	 */
	private $_zoom = self::DEFAULT_ZOOM;

	/**
	 * @var string $_size = '160x90'
	 */
	private $_size = '160x90';

	/**
	 * @var int $_scale = self::DEFAULT_SCALE
	 */
	private $_scale = self::DEFAULT_SCALE;

	/**
	 * @var string $_format = self::FORMAT_PNG
	 */
	private $_format = self::FORMAT_PNG;

	/**
	 * @var string $_type = self::TYPE_ROADMAP
	 */
	private $_type = self::TYPE_ROADMAP;

	/**
	 * @var MapMarker[] $_markers = []
	 */
	private $_markers = array();

	/**
	 * @var MapPath[] $_paths = []
	 */
	private $_paths = array();

	/**
	 * @var bool $_sensor = false
	 */
	private $_sensor = false;

	/**
	 * @var string $_key = ''
	 */
	private $_key = '';

	/**
	 * @param string $key
	 */
	public function __construct ($key = '') {
		$this->_key = $key;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/JS/GoogleMap.js';
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new jQueryCore(),
			new QuarkLocalCoreJSViewResource(),
			new MapAPI($this->_key)
		);
	}

	/**
	 * @param MapPoint $center = null
	 *
	 * @return MapPoint
	 */
	public function Center (MapPoint $center = null) {
		if (func_num_args() != 0)
			$this->_center = $center;

		return $this->_center;
	}

	/**
	 * @param int $zoom = self::DEFAULT_ZOOM
	 *
	 * @return int
	 */
	public function Zoom ($zoom = self::DEFAULT_ZOOM) {
		if (func_num_args() != 0)
			$this->_zoom = (int)$zoom;

		return $this->_zoom;
	}

	/**
	 * @param int $width = self::DEFAULT_WIDTH
	 * @param int $height = self::DEFAULT_HEIGHT
	 *
	 * @return string
	 */
	public function Size ($width = self::DEFAULT_WIDTH, $height = self::DEFAULT_HEIGHT) {
		if (func_num_args() != 0)
			$this->_size = ((int)$width) . 'x' . ((int)$height);

		return $this->_size;
	}

	/**
	 * @param int $scale = self::DEFAULT_SCALE
	 *
	 * @return int
	 */
	public function Scale ($scale = self::DEFAULT_SCALE) {
		if (func_num_args() != 0)
			$this->_scale = (int)$scale;

		return $this->_scale;
	}

	/**
	 * @param string $format = self::FORMAT_PNG
	 *
	 * @return string
	 */
	public function Format ($format = self::FORMAT_PNG) {
		if (func_num_args() != 0)
			$this->_format = (string)$format;

		return $this->_format;
	}

	/**
	 * @param string $type = self::TYPE_ROADMAP
	 *
	 * @return string
	 */
	public function MapType ($type = self::TYPE_ROADMAP) {
		if (func_num_args() != 0)
			$this->_type = (string)$type;

		return $this->_type;
	}

	/**
	 * @param bool $sensor = self::DEFAULT_SENSOR
	 *
	 * @return bool
	 */
	public function Sensor ($sensor = self::DEFAULT_SENSOR) {
		if (func_num_args() != 0)
			$this->_sensor = (bool)$sensor;

		return $this->_sensor;
	}

	/**
	 * @param MapMarker $marker
	 *
	 * @return GoogleMap
	 */
	public function Marker (MapMarker $marker) {
		$this->_markers[] = $marker;
		return $this;
	}

	/**
	 * @return MapMarker[]
	 */
	public function Markers () {
		return $this->_markers;
	}

	/**
	 * @param MapPath $path
	 *
	 * @return GoogleMap
	 */
	public function Path (MapPath $path) {
		$this->_paths[] = $path;
		return $this;
	}

	/**
	 * @return MapPath[]
	 */
	public function Paths () {
		return $this->_paths;
	}

	/**
	 * @return string
	 */
	public function Image () {
		$markers = '';
		foreach ($this->_markers as $marker)
			$markers .= $marker->Compile();

		$paths = '';
		foreach ($this->_paths as $path)
			$paths .= $path->Compile();

		return 'http://maps.googleapis.com/maps/api/staticmap'
			. '?format=' . $this->_format
			. '&zoom=' . $this->_zoom
			. '&size=' . $this->_size
			. '&scale=' . $this->_scale
			. '&maptype=' . $this->_type
			. '&sensor=' . ($this->_sensor ? 'true' : 'false')
			. ($this->_center != null ? '&center=' . $this->_center->Compile() : '')
			. (sizeof($this->_markers) != 0 ? $markers : '')
			. (sizeof($this->_paths) != 0 ? $paths : '')
			. (strlen($this->_key) != 0 ? '&key=' . $this->_key : '');
	}

	/**
	 * @param string $address
	 *
	 * @return MapPoint
	 */
	public function GeocodeAddress ($address) {
		$map = QuarkHTTPClient::To(
			'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $this->_key,
			QuarkDTO::ForGET(),
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		if (!isset($map->status) || !isset($map->results) || ($map->status != self::GEOCODE_STATUS_OK && $map->status != self::GEOCODE_STATUS_ZERO_RESULTS)) return null;
		if (sizeof($map->results) == 0 || !isset($map->results[0]->geometry->location)) return null;

		$point = $map->results[0]->geometry->location;

		return isset($point->lat) && isset($point->lng) ? new MapPoint($point->lat, $point->lng) : null;
	}
}