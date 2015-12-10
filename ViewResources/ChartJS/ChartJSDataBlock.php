<?php
namespace Quark\ViewResources\ChartJS;

/**
 * Class ChartJSDataBlock
 * http://www.chartjs.org/docs/
 *
 * @package Quark\ViewResources\ChartJS
 */
class ChartJSDataBlock {
	public $value = 0;
	public $color = 'rgba(220,220,220,1)';
	public $highlight = 'rgba(220,220,220,1)';
	public $label = '';

	/**
	 * @param int $value
	 * @param string $label
	 */
	public function __construct ($value = 0, $label = '') {
		$this->value = $value;
		$this->label = $label;
	}
}