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
	/**
	 * @var float|int $lat
	 */
	public $lat;

	/**
	 * @var float|int $lng
	 */
	public $lng;

	/**
	 * @var float|int $width
	 */
	public $width;

	/**
	 * @param float|int $lat = 0.0
	 * @param float|int $lng = 0.0
	 * @param float|int $width = -1
	 */
	public function __construct ($lat = 0.0, $lng = 0.0, $width = -1) {
		$this->lat = $lat;
		$this->lng = $lng;
		$this->width = $width;
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
	 * @return mixed
	 */
	public function BeforeExtract () {
		unset($this->width);
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
}