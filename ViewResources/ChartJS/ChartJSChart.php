<?php
namespace Quark\ViewResources\ChartJS;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class ChartJSChart
 *
 * @package Quark\ViewResources\ChartJS
 */
class ChartJSChart implements IQuarkViewResource, IQuarkInlineViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @var string $_selector
	 */
	private $_selector = '';

	/**
	 * @var IQuarkChartJSChart $_chart
	 */
	private $_chart;

	/**
	 * @var string[] $labels
	 */
	private $_labels = array();

	/**
	 * @var ChartJSDataSet[] $_data
	 */
	private $_data = array();

	/**
	 * @var ChartJSOptions $_options
	 */
	private $_options;

	/**
	 * @param string $selector
	 * @param IQuarkChartJSChart $chart
	 * @param string[] $labels
	 */
	public function __construct ($selector, IQuarkChartJSChart $chart, $labels = []) {
		$this->_selector = $selector;
		$this->_chart = $chart;
		$this->_labels = $labels;
		$this->_options = new ChartJSOptions();
	}

	/**
	 * @param string[] $labels
	 *
	 * @return string[]
	 */
	public function &Labels ($labels = []) {
		if (func_num_args() != 0 && is_array($labels))
			$this->_labels = $labels;

		return $this->_labels;
	}

	/**
	 * @param ChartJSDataSet $set
	 *
	 * @return ChartJSChart
	 */
	public function DataSet (ChartJSDataSet $set) {
		$this->_data[] = $set;

		return $this;
	}

	/**
	 * @param ChartJSOptions $options
	 *
	 * @return ChartJSOptions
	 */
	public function &Options (ChartJSOptions $options = null) {
		if (func_num_args() != 0)
			$this->_options = $options;

		return $this->_options;
	}

	/**
	 * @param bool $script = true
	 *
	 * @return string
	 */
	public function Render ($script = true) {
		return ($script ? '<script type="text/javascript">' : '')
				. '$(\'' . $this->_selector . '\').each(function(){var chart=new Chart($(this)[0].getContext(\'2d\')).'
				. $this->_chart->ChartJSType() . '({labels:' . json_encode($this->_labels) . ',datasets:' . json_encode($this->_data) . '},' . json_encode($this->_options) . ');});'
			 	. ($script ? '</script>' : '');
	}

	/**
	 * @return IQuarkViewResourceType;
	 */
	public function Type () {
		// TODO: Implement Type() method.
	}

	/**
	 * @return string
	 */
	public function Location () {
		// TODO: Implement Location() method.
	}

	/**
	 * @info EXTERNAL_FRAGMENT need to suppress the PHPStorm 8+ invalid spell check
	 * @return string
	 */
	public function HTML () {
		return '<script type="text/javascript">var EXTERNAL_FRAGMENT;$(function(){' . $this->Render(false) . '});</script>';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new ChartJS()
		);
	}

	/**
	 * @param int $count = 5
	 * @param int $min = 1
	 * @param int $max = 20
	 *
	 * @return array
	 */
	public static function RandomData ($count = 5, $min = 1, $max = 20) {
		$i = 0;
		$out = array();

		while ($i < $count) {
			$out[] = mt_rand($min, $max);
			$i++;
		}

		return $out;
	}
}