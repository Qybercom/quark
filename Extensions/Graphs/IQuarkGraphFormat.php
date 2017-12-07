<?php
namespace Quark\Extensions\Graphs;

/**
 * Interface IQuarkGraphFormat
 *
 * @package Quark\Extensions\Graphs
 */
interface IQuarkGraphFormat {
	/**
	 * @return string
	 */
	public function GraphFormatMIME();

	/**
	 * @param Graph $graph
	 *
	 * @return string
	 */
	public function GraphFormatRender(Graph $graph);
}