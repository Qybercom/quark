<?php
namespace Quark\ViewResources\ChartJS\Charts;

use Quark\ViewResources\ChartJS\IQuarkChartJSChart;

/**
 * Class LineChart
 *
 * @package Quark\ViewResources\ChartJS\Charts
 */
class LineChart implements IQuarkChartJSChart {
	/**
	 * @return string
	 */
	public function ChartJSType () {
		return 'Line';
	}
}