<?php
namespace Quark\ViewResources\Google;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;
use Quark\IQuarkModelWithBeforeExtract;

/**
 * Class MapPoint
 *
 * @package Quark\ViewResources\Google
 */
class MapPoint implements IQuarkModel, IQuarkStrongModel, IQuarkModelWithBeforeExtract {
	const EARTH_RADIUS = 6372795;

	/**
	 * @var float|int $lat = 0.0
	 */
	public $lat = 0.0;

	/**
	 * @var float|int $lng = 0.0
	 */
	public $lng = 0.0;

	/**
	 * @var float|int $width = -1
	 */
	public $width = -1;

	/**
	 * @param float|int $lat = 0.0
	 * @param float|int $lng = 0.0
	 * @param float|int $width = -1
	 */
	public function __construct ($lat = 0.0, $lng = 0.0, $width = -1) {
		$this->lat = (float)$lat;
		$this->lng = (float)$lng;
		$this->width = (float)$width;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'lat' => 0.0,
			'lng' => 0.0
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		$this->lat = (float)$this->lat;
		$this->lng = (float)$this->lng;
	}

	/**
	 * @param array $fields
	 * @param bool $weak
	 *
	 * @return mixed
	 */
	public function BeforeExtract ($fields, $weak) {
		unset($this->width);
	}

	/**
	 * @param MapPoint $with
	 *
	 * @link https://www.kobzarev.com/programming/calculation-of-distances-between-cities-on-their-coordinates.html
	 * @return float metres
	 */
	public function Distance (MapPoint $with) {
		// перевести координаты в радианы
		$lat1 = $this->lat * M_PI / 180;
		$lat2 = $with->lat * M_PI / 180;
		$long1 = $this->lng * M_PI / 180;
		$long2 = $with->lng * M_PI / 180;

		// косинусы и синусы широт и разницы долгот
		$cl1 = cos($lat1);
		$cl2 = cos($lat2);
		$sl1 = sin($lat1);
		$sl2 = sin($lat2);
		$delta = $long2 - $long1;
		$cdelta = cos($delta);
		$sdelta = sin($delta);

		// вычисления длины большого круга
		$y = sqrt(pow($cl2 * $sdelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cdelta, 2));
		$x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;

		$ad = atan2($y, $x);

		return $ad * self::EARTH_RADIUS;
	}

	/**
	 * @param int|float $angle = 0
	 * @param int|float $width = 0
	 *
	 * @return float
	 */
	public function Width ($angle = 0, $width = 0) {
		return ((func_num_args() < 2 ? $this->width : $width) / (25400 * pi() * cos(deg2rad($angle)))) * 360;
	}

	/**
	 * @param int|float $width = -1
	 *
	 * @return mixed
	 */
	public function Edge ($width = -1) {
		$dv_lat = $this->Width(0, 						$width < 0 ? $this->width : $width);
		$dv_lng = $this->Width(0, 						0);
		$dh_lat = $this->Width(0, 						0);
		$dh_lng = $this->Width($this->lat,	$width < 0 ? $this->width : $width);

		return (object)array(
			'n' => new self($this->lat + $dv_lat, $this->lng - $dv_lng),
			'e' => new self($this->lat + $dh_lat, $this->lng + $dh_lng),
			's' => new self($this->lat - $dv_lat, $this->lng + $dv_lng),
			'w' => new self($this->lat - $dh_lat, $this->lng - $dh_lng)
		);
	}

	/**
	 * @param int|float $width = -1
	 *
	 * @return mixed
	 */
	public function EdgeDelta ($width = -1) {
		$edge = $this->Edge($width);

		return (object)array(
			'n' => $edge->n->lat,
			'e' => $edge->e->lng,
			's' => $edge->s->lat,
			'w' => $edge->w->lng,
		);
	}

	/**
	 * @param string $lat = 'lat'
	 * @param string $lng = 'lng'
	 * @param int $width = -1
	 *
	 * @return array
	 */
	public function GeoSearch ($lat = 'lat', $lng = 'lng', $width = -1) {
		$edge = $this->EdgeDelta((float)$width);

		return array(
			$lat => array(
				'$lte' => $edge->n,
				'$gte' => $edge->s
			),
			$lng => array(
				'$lte' => $edge->e,
				'$gte' => $edge->w
			)
		);
	}

	/**
	 * @param MapPoint $point
	 * @param int $width = -1
	 *
	 * @return bool
	 */
	public function Match (MapPoint $point, $width = -1) {
		$edge = $this->EdgeDelta((float)$width);

		return
			$point->lat <= $edge->n &&
			$point->lat >= $edge->s &&
			$point->lng <= $edge->e &&
			$point->lng >= $edge->w;
	}
}