<?php
namespace Quark\ViewResources\ChartJS\Charts;

use Quark\ViewResources\ChartJS\IQuarkChartJSChart;

use Quark\ViewResources\ChartJS\ChartJSMultipleChartBehavior;

/**
 * Class BarChart
 *
 * @package Quark\ViewResources\ChartJS\Charts
 */
class BarChart implements IQuarkChartJSChart {
	use ChartJSMultipleChartBehavior;

	/**
	 * @return string
	 */
	public function ChartJSType () {
		return 'Bar';
	}
}