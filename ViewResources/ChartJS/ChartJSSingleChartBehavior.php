<?php
namespace Quark\ViewResources\ChartJS;

/**
 * Class ChartJSSingleChartBehavior
 *
 * @package Quark\ViewResources\ChartJS
 */
trait ChartJSSingleChartBehavior {
	/**
	 * @var ChartJSDataBlock[] $_data
	 */
	private $_data = array();

	/**
	 * @param ChartJSDataBlock $block
	 *
	 * @return IQuarkChartJSChart
	 */
	public function DataBlock (ChartJSDataBlock $block) {
		$this->_data[] = $block;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function ChartJSData () {
		return $this->_data;
	}

	/**
	 * @param string[] $labels
	 *
	 * @return string[]
	 */
	public function ChartJSLabels ($labels = []) {
		$out = array();

		$i = 0;
		$size = sizeof($labels);

		while ($i < $size) {
			if (isset($this->_data[$i])) {
				$out[] = $labels[$i];
				$this->_data[$i]->label = $labels[$i];
			}

			$i++;
		}

		return $out;
	}

	/**
	 * @param array $values = []
	 * @param string $color = 'rgba(220,220,220,1)'
	 * @param string $highlight = 'rgba(220,220,220,0.7)'
	 * @param string[] $labels = []
	 *
	 * @return array
	 */
	public function Data ($values = [], $color = 'rgba(220,220,220,1)', $highlight = 'rgba(220,220,220,0.7)', $labels = []) {
		$i = 0;
		$size = sizeof($values);

		while ($i < $size) {
			$block = new ChartJSDataBlock($values[$i], isset($labels[$i]) ? $labels[$i] : 'undefined');
			$block->color = $color;
			$block->highlight = $highlight;

			$this->_data[] = $block;

			$i++;
		}

		return $this->_data;
	}
}