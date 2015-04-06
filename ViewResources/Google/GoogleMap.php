<?php
namespace Quark\ViewResources\Google;

use Quark\IQuarkViewResource;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkViewResourceType;

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

	const TYPE_ROADMAP = 'roadmap';
	const TYPE_SATELLITE = 'satellite';
	const TYPE_TERRAIN = 'terrain';
	const TYPE_HYBRID = 'hybrid';

	private $_center = '0.0-0.0';
	private $_zoom = 8;
	private $_size = '160x90';
	private $_scale = 1;
	private $_format = self::FORMAT_PNG;
	private $_type = self::TYPE_ROADMAP;
	private $_markers = '';
	private $_paths = '';
	private $_sensor = false;

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
	 * @return array
	 */
	public function Dependencies () {
		return array(
			new jQueryCore(),
			new QuarkLocalCoreJSViewResource(),
			new MapAPI($this->_key)
		);
	}

	/**
	 * @param float $lat
	 * @param float $lng
	 *
	 * @return GoogleMap
	 */
	public function CenterByCoordinates ($lat, $lng) {
		$this->_center = ((float)$lat) . '-' . ((float)$lng);
		return $this;
	}

	/**
	 * @param string $location
	 *
	 * @return GoogleMap
	 */
	public function CenterByLocation ($location) {
		$this->_center = (string)$location;
		return $this;
	}

	/**
	 * @param int $zoom
	 *
	 * @return GoogleMap
	 */
	public function Zoom ($zoom) {
		$this->_zoom = (int)$zoom;
		return $this;
	}

	/**
	 * @param int $width
	 * @param int $height
	 *
	 * @return GoogleMap
	 */
	public function Size ($width, $height) {
		$this->_size = ((int)$width) . 'x' . ((int)$height);
		return $this;
	}

	/**
	 * @param int $scale
	 *
	 * @return GoogleMap
	 */
	public function Scale ($scale) {
		$this->_scale = (int)$scale;
		return $this;
	}

	/**
	 * @param string $format
	 *
	 * @return GoogleMap
	 */
	public function Format ($format) {
		$this->_format = (string)$format;
		return $this;
	}

	/**
	 * @param string $type
	 *
	 * @return GoogleMap
	 */
	public function MapType ($type) {
		$this->_type = (string)$type;
		return $this;
	}

	/**
	 * @param bool $sensor
	 *
	 * @return GoogleMap
	 */
	public function Sensor ($sensor) {
		$this->_sensor = (bool)$sensor;
		return $this;
	}

	/**
	 * @param MapMarker $marker
	 *
	 * @return GoogleMap
	 */
	public function Marker (MapMarker $marker) {
		$this->_markers .= $marker->Compile();
		return $this;
	}

	/**
	 * @param MapPath $path
	 *
	 * @return GoogleMap
	 */
	public function Path (MapPath $path) {
		$this->_paths .= $path->Compile();
		return $this;
	}

	/**
	 * @return string
	 */
	public function Image () {
		return urlencode(
			'http://maps.googleapis.com/maps/api/staticmap'
			. '?center=' . $this->_center
			. '&zoom=' . $this->_zoom
			. '&size=' . $this->_size
			. '&scale=' . $this->_scale
			. '&format=' . $this->_format
			. '&maptype=' . $this->_type
			. '&sensor=' . ($this->_sensor ? 'true' : 'false')
			. (strlen($this->_key) != 0 ? '&key=' . $this->_key : '')
		);
	}
}