<?php
namespace Quark\ViewResources\ChartJS;

/**
 * Interface IQuarkChartJSChart
 *
 * @package Quark\ViewResources\ChartJS
 */
interface IQuarkChartJSChart {
	/**
	 * @return string
	 */
	public function ChartJSType();

	/**
	 * @return mixed
	 */
	public function ChartJSData();

	/**
	 * @param string[] $labels
	 *
	 * @return string[]
	 */
	public function ChartJSLabels($labels);
}