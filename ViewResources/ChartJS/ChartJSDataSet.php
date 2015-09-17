<?php
namespace Quark\ViewResources\ChartJS;

/**
 * Class ChartJSDataSet
 * http://www.chartjs.org/docs/
 *
 * @package Quark\ViewResources\ChartJS
 */
class ChartJSDataSet {
	public $data = array();
	public $label = '';

	public $fillColor = 'rgba(220,220,220,0.2)';
	public $strokeColor = 'rgba(220,220,220,1)';

	public $pointColor = 'rgba(220,220,220,1)';
	public $pointStrokeColor = '#fff';
	public $pointHighlightFill = '#fff';
	public $pointHighlightStroke = 'rgba(220,220,220,1)';

	/**
	 * @param array $data
	 * @param string $label
	 */
	public function __construct ($data = [], $label = '') {
		$this->data = $data;
		$this->label = $label;
	}
}