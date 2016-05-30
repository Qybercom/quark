<?php
namespace Quark\ViewResources\Google\MapComponents;

use Quark\ViewResources\Google\IQuarkGoogleMapComponent;

/**
 * Class GoogleMapStyle
 *
 * @package Quark\ViewResources\Google\MapComponents
 */
class GoogleMapStyle implements IQuarkGoogleMapComponent {
	const FEATURE_ALL = 'all';
	const FEATURE_ADMINISTRATIVE = 'administrative';
	const FEATURE_ADMINISTRATIVE_COUNTRY = 'administrative.country';
	const FEATURE_ADMINISTRATIVE_LAND_PARCEL = 'administrative.land_parcel';
	const FEATURE_ADMINISTRATIVE_LOCALITY = 'administrative.locality';
	const FEATURE_ADMINISTRATIVE_NEIGHBORHOOD = 'administrative.neighborhood';
	const FEATURE_ADMINISTRATIVE_PROVINCE = 'administrative.province';
	const FEATURE_LANDSCAPE = 'landscape';
	const FEATURE_LANDSCAPE_MAN_MADE = 'landscape.man_made';
	const FEATURE_LANDSCAPE_NATURAL = 'landscape.natural';
	const FEATURE_LANDSCAPE_NATURAL_LANDCOVER = 'landscape.natural.landcover';
	const FEATURE_LANDSCAPE_NATURAL_TERRAIN = 'landscape.natural.terrain';
	const FEATURE_POI = 'poi';
	const FEATURE_POI_ATTRACTION = 'poi.attraction';
	const FEATURE_POI_BUSINESS = 'poi.business';
	const FEATURE_POI_GOVERNMENT = 'poi.government';
	const FEATURE_POI_MEDICAL = 'poi.medical';
	const FEATURE_POI_PARK = 'poi.park';
	const FEATURE_PLACE_OF_WORSHIP = 'poi.place_of_worship';
	const FEATURE_POI_SCHOOL = 'poi.school';
	const FEATURE_POI_SPORTS_COMPLEX = 'poi.sports_complex';
	const FEATURE_ROAD = 'road';
	const FEATURE_ROAD_ARTERIAL = 'road.arterial';
	const FEATURE_ROAD_HIGHWAY = 'road.highway';
	const FEATURE_ROAD_HIGHWAY_CONTROLLED_ACCESS = 'road.highway.controlled_access';
	const FEATURE_ROAD_LOCAL = 'road.local';
	const FEATURE_TRANSIT = 'transit';
	const FEATURE_TRANSIT_LINE = 'transit.line';
	const FEATURE_TRANSIT_STATION = 'transit.station';
	const FEATURE_TRANSIT_STATION_AIRPORT = 'transit.station.airport';
	const FEATURE_TRANSIT_STATION_BUS = 'transit.station.bus';
	const FEATURE_TRANSIT_STATION_RAIL = 'transit.station.rail';
	const FEATURE_WATER = 'water';

	const ELEMENT_ALL = 'all';
	const ELEMENT_GEOMETRY = 'geometry';
	const ELEMENT_GEOMETRY_FILL = 'geometry.fill';
	const ELEMENT_GEOMETRY_STROKE = 'geometry.stroke';
	const ELEMENT_LABELS = 'labels';
	const ELEMENT_LABELS_ICON ='labels.icon';
	const ELEMENT_LABELS_TEXT = 'labels.text';
	const ELEMENT_LABELS_TEXT_FILL = 'labels.text.fill';
	const ELEMENT_LABELS_TEXT_STROKE = 'labels.text.stroke';

	const VISIBILITY_ON = 'on';
	const VISIBILITY_OFF = 'off';
	const VISIBILITY_SIMPLIFIED = 'simplified';

	/**
	 * @var string $_feature = self::FEATURE_ALL
	 */
	private $_feature = self::FEATURE_ALL;

	/**
	 * @var string $_element = self::ELEMENT_ALL
	 */
	private $_element = self::ELEMENT_ALL;

	/**
	 * @var string $_hue = ''
	 */
	private $_hue = '';

	/**
	 * @var int $_lightness = 0
	 */
	private $_lightness = 0;

	/**
	 * @var int $_saturation = 0
	 */
	private $_saturation = 0;

	/**
	 * @var float $_gamma = 1.0
	 */
	private $_gamma = 1.0;

	/**
	 * @var bool $_inverseLightness = false
	 */
	private $_inverseLightness = false;

	/**
	 * @var string $_visibility = self::VISIBILITY_ON
	 */
	private $_visibility = self::VISIBILITY_ON;

	/**
	 * @param string $feature = self::FEATURE_ALL
	 * @param string $element = self::ELEMENT_ALL
	 * @param string $hue = ''
	 * @param int $lightness = 0
	 * @param int $saturation = 0
	 * @param float $gamma = 1.0
	 * @param bool $inverseLightness = false
	 * @param string $visibility = self::VISIBILITY_ON
	 */
	public function __construct ($feature = self::FEATURE_ALL, $element = self::ELEMENT_ALL, $hue = '', $lightness = 0, $saturation = 0, $gamma = 1.0, $inverseLightness = false, $visibility = self::VISIBILITY_ON) {
		$this->Feature($feature);
		$this->Element($element);
		$this->Hue($hue);
		$this->Lightness($lightness);
		$this->Saturation($saturation);
		$this->Gamma($gamma);
		$this->InverseLightness($inverseLightness);
		$this->Visibility($visibility);
	}

	/**
	 * @param string $feature = self::FEATURE_ALL
	 *
	 * @return string
	 */
	public function Feature ($feature = self::FEATURE_ALL) {
		if (func_num_args() != 0)
			$this->_feature = $feature;

		return $this->_feature;
	}

	/**
	 * @param string $element = self::ELEMENT_ALL
	 *
	 * @return string
	 */
	public function Element ($element = self::ELEMENT_ALL) {
		if (func_num_args() != 0)
			$this->_element = $element;

		return $this->_element;
	}

	/**
	 * @param string $hue = ''
	 *
	 * @return string
	 */
	public function Hue ($hue = '') {
		if (func_num_args() != 0)
			$this->_hue = $hue;

		return $this->_hue;
	}

	/**
	 * @param int $lightness = 0
	 *
	 * @return int
	 */
	public function Lightness ($lightness = 0) {
		if (func_num_args() != 0)
			$this->_lightness = $lightness;

		return $this->_lightness;
	}

	/**
	 * @param int $saturation = 0
	 *
	 * @return int
	 */
	public function Saturation ($saturation = 0) {
		if (func_num_args() != 0)
			$this->_saturation = $saturation;

		return $this->_saturation;
	}

	/**
	 * @param float $gamma = 1.0
	 *
	 * @return float
	 */
	public function Gamma ($gamma = 1.0) {
		if (func_num_args() != 0)
			$this->_gamma = $gamma;

		return $this->_gamma;
	}

	/**
	 * @param bool $inverseLightness = false
	 *
	 * @return bool
	 */
	public function InverseLightness ($inverseLightness = false) {
		if (func_num_args() != 0)
			$this->_inverseLightness = $inverseLightness;

		return $this->_inverseLightness;
	}

	/**
	 * @param string $visibility = self::VISIBILITY_ON
	 *
	 * @return string
	 */
	public function Visibility ($visibility = self::VISIBILITY_ON) {
		if (func_num_args() != 0)
			$this->_visibility = $visibility;

		return $this->_visibility;
	}

	/**
	 * @return string
	 */
	public function Compile () {
		return '&style='
			. 'feature:' . $this->_feature . '|'
			. 'element:' . $this->_element . '|'
			. ($this->_hue != '' ? 'hue:' . $this->_hue . '|' : '')
			. 'lightness:' . $this->_lightness . '|'
			. 'saturation:' . $this->_saturation . '|'
			. 'gamma:' . number_format($this->_gamma, 1, '.', '') . '|'
			. ($this->_inverseLightness ? 'inverse_lightness:true|' : '')
			. 'visibility:' . $this->_visibility;
	}
}