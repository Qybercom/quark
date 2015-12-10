<?php
namespace Quark\ViewResources\ChartJS;

/**
 * Class ChartJSMultipleChartBehavior
 *
 * @package Quark\ViewResources\ChartJS
 */
trait ChartJSMultipleChartBehavior {
	/**
	 * @var ChartJSDataSet[] $_data
	 */
	private $_data = array();

	/**
	 * @var string[] $_labels
	 */
	private $_labels = array();

	/**
	 * @param ChartJSDataSet $set
	 *
	 * @return IQuarkChartJSChart
	 */
	public function DataSet (ChartJSDataSet $set) {
		$this->_data[] = $set;

		return $this;
	}

	/**
	 * @return array
	 */
	public function ChartJSData () {
		return array(
			'labels' => $this->_labels,
			'datasets' => $this->_data
		);
	}

	/**
	 * @param string[] $labels
	 *
	 * @return string[]
	 */
	public function ChartJSLabels ($labels = []) {
		if (func_num_args() != 0 && is_array($labels))
			$this->_labels = $labels;

		return $this->_labels;
	}
}