<?php
namespace Quark\ViewResources\ChartJS;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkMultipleViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

/**
 * Class ChartJSChart
 *
 * @package Quark\ViewResources\ChartJS
 */
class ChartJSChart implements IQuarkViewResource, IQuarkInlineViewResource, IQuarkViewResourceWithDependencies, IQuarkMultipleViewResource {
	/**
	 * @var int $_id = 0
	 */
	private static $_id = 0;

	/**
	 * @var string $_selector
	 */
	private $_selector = '';

	/**
	 * @var IQuarkChartJSChart $_chart
	 */
	private $_chart;

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

		$this->_options = new ChartJSOptions();
		$this->_chart->ChartJSLabels($labels);
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
				. '$(\'' . $this->_selector . '\').each(function(){if(!$(this).is(\'canvas\'))throw new Error(\'Selector "' . $this->_selector . '" is not a canvas\');var chart' . self::$_id++ . '=new Chart($(this)[0].getContext(\'2d\')).'
				. $this->_chart->ChartJSType() . '(' . json_encode($this->_chart->ChartJSData()) . ',' . json_encode($this->_options) . ');});'
			 	. ($script ? '</script>' : '');
	}

	/**
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML ($minimize) {
		return /** @lang text */'<script type="text/javascript">$(function(){' . $this->Render(false) . '});</script>';
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

	/**
	 * @return bool
	 */
	public function Minimize () {
		// TODO: Implement Minimize() method.
	}
}