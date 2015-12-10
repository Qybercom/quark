<?php
namespace Quark\ViewResources\ChartJS\Charts;

use Quark\ViewResources\ChartJS\IQuarkChartJSChart;
use Quark\ViewResources\ChartJS\ChartJSSingleChartBehavior;

/**
 * Class PieChart
 *
 * @package Quark\ViewResources\ChartJS\Charts
 */
class PieChart implements IQuarkChartJSChart {
	use ChartJSSingleChartBehavior;

	/**
	 * @param array $values = []
	 * @param string $color = 'rgba(220,220,220,1)'
	 * @param string $highlight = 'rgba(220,220,220,0.7)'
	 * @param string[] $labels = []
	 */
	public function __construct ($values = [], $color = 'rgba(220,220,220,1)', $highlight = 'rgba(220,220,220,0.7)', $labels = []) {
		$this->Data($values, $color, $highlight, $labels);
	}

	/**
	 * @return string
	 */
	public function ChartJSType () {
		return 'Pie';
	}
}