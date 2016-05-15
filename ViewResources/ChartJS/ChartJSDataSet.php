<?php
namespace Quark\ViewResources\ChartJS;

/**
 * Class ChartJSDataSet
 * http://www.chartjs.org/docs/
 *
 * @package Quark\ViewResources\ChartJS
 */
class ChartJSDataSet {
	/**
	 * @var array $data = []
	 */
	public $data = array();

	/**
	 * @var string $label = ''
	 */
	public $label = '';

	/**
	 * @var string $fillColor = 'rgba(220,220,220,0.2)'
	 */
	public $fillColor = 'rgba(220,220,220,0.2)';

	/**
	 * @var string $strokeColor = 'rgba(220,220,220,1)'
	 */
	public $strokeColor = 'rgba(220,220,220,1)';

	/**
	 * @var string $pointColor = 'rgba(220,220,220,1)'
	 */
	public $pointColor = 'rgba(220,220,220,1)';

	/**
	 * @var string $pointStrokeColor = '#fff'
	 */
	public $pointStrokeColor = '#fff';

	/**
	 * @var string $pointHighlightFill = '#fff'
	 */
	public $pointHighlightFill = '#fff';

	/**
	 * @var string $pointHighlightStroke = 'rgba(220,220,220,1)'
	 */
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