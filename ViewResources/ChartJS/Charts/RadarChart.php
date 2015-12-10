<?php
namespace Quark\ViewResources\ChartJS\Charts;

use Quark\ViewResources\ChartJS\IQuarkChartJSChart;

use Quark\ViewResources\ChartJS\ChartJSMultipleChartBehavior;

/**
 * Class RadarChart
 *
 * @package Quark\ViewResources\ChartJS\Charts
 */
class RadarChart implements IQuarkChartJSChart {
	use ChartJSMultipleChartBehavior;

	/**
	 * @return string
	 */
	public function ChartJSType () {
		return 'Radar';
	}
}