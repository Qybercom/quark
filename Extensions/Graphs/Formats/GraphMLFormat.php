<?php
namespace Quark\Extensions\Graphs\Formats;

use Quark\QuarkObject;
use Quark\QuarkXMLIOProcessor;

use Quark\Extensions\Graphs\Graph;
use Quark\Extensions\Graphs\IQuarkGraphFormat;

/**
 * Class GraphMLFormat
 *
 * @package Quark\Extensions\Graphs\Formats
 */
class GraphMLFormat implements IQuarkGraphFormat {
	/**
	 * @var array $_attributes_nodes = []
	 */
	private $_attributes_nodes = array();

	/**
	 * @var array $_attributes_edges = []
	 */
	private $_attributes_edges = array();

	/**
	 * @var bool $_edgeAutoId = false
	 */
	private $_edgeAutoId = false;

	/**
	 * @param array $attributes_nodes = []
	 * @param array $attributes_edges = []
	 * @param bool $edgeAutoId = false
	 */
	public function __construct ($attributes_nodes = [], $attributes_edges = [], $edgeAutoId = false) {
		$this->_attributes_nodes = QuarkObject::Merge($attributes_nodes);
		$this->_attributes_edges = QuarkObject::Merge($attributes_edges);
		$this->_edgeAutoId = $edgeAutoId;
	}

	/**
	 * @return string
	 */
	public function GraphFormatMIME () {
		return QuarkXMLIOProcessor::MIME;
	}

	/**
	 * @param Graph $graph
	 *
	 * @return string
	 */
	public function GraphFormatRender (Graph $graph) {
		$out = ''
			. '<?xml version="1.0" encoding="UTF-8" ?>' . "\r\n"
			. '<graphml xmlns="http://graphml.graphdrawing.org/xmlns" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns http://graphml.graphdrawing.org/xmlns/1.0/graphml.xsd">' . "\r\n";

		foreach ($this->_attributes_nodes as $key => &$default)
			$out .= '	<key id="n_' . $key . '" attr.name="' . $key . '" attr.type="' . gettype($default) . '" for="node"><default>' . $default . '</default></key>' . "\r\n";

		foreach ($this->_attributes_edges as $key => &$default)
			$out .= '	<key id="e_' . $key . '" attr.name="' . $key . '" attr.type="' . gettype($default) . '" for="edge"><default>' . $default . '</default></key>' . "\r\n";

		$out .= "\r\n"
			. '	<graph id="' . $graph->ID() . '">' . "\r\n";

		$nodes = $graph->Nodes();

		foreach ($nodes as $i => &$node) {
			$out .= '		<node id="' . $node->ID() . '">' . "\r\n";
			foreach ($this->_attributes_nodes as $key => &$default)
				$out .= '			<data key="n_' . $key . '">' . $node->AttributeOrDefault($key, $default) . '</data>' . "\r\n";
			$out .= '		</node>' . "\r\n";
		}

		$out .= "\r\n";

		$edges = $graph->Edges();

		foreach ($edges as $i => &$edge) {
			$out .= '		<edge id="' . $edge->IDOrAuto('e' . $i) . '" source="' . $edge->Source() . '" target="' . $edge->Target() . '" directed="' . ($edge->Directed() ? 'true' : 'false') . '">' . "\r\n";
			foreach ($this->_attributes_edges as $key => &$default)
				$out .= '			<data key="e_' . $key . '">' . $edge->AttributeOrDefault($key, $default) . '</data>' . "\r\n";
			$out .= '		</edge>' . "\r\n";
		}

		$out .= ''
			. '	</graph>' . "\r\n"
			. '</graphml>';

		return $out;
	}
}