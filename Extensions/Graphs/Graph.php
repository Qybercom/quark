<?php
namespace Quark\Extensions\Graphs;

use Quark\IQuarkExtension;

use Quark\QuarkCollectionBehaviorWithArrayAccess;
use Quark\QuarkFile;

/**
 * Class Graph
 *
 * @package Quark\Extensions\Graphs
 */
class Graph implements IQuarkExtension {
	/*
	use QuarkCollectionBehaviorWithArrayAccess {
		Select as private _select;
		SelectRandom as private _selectRandom;
		ChangeAndReturn as private _changeAndReturn;
		PurgeAndReturn as private _purgeAndReturn;
		Aggregate as private _aggregate;
		Map as private _map;
		offsetSet as private _offsetSet;
	}
	*/
	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var GraphNode[] $_nodes = []
	 */
	private $_nodes = array();

	/**
	 * @var GraphEdge[] $_edges = []
	 */
	private $_edges = array();

	/**
	 * @param string $id = ''
	 * @param bool $directed = false
	 */
	public function __construct ($id = '') {
		$this->ID($id);
	}

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param GraphNode $node = null
	 *
	 * @return Graph
	 */
	public function Node (GraphNode $node = null) {
		if ($node != null)
			$this->_nodes[] = $node;

		return $this;
	}

	/**
	 * @return GraphNode[]
	 */
	public function Nodes () {
		return $this->_nodes;
	}

	/**
	 * @param GraphEdge $edge = null
	 *
	 * @return Graph
	 */
	public function Edge (GraphEdge $edge = null) {
		if ($edge != null)
			$this->_edges[] = $edge;

		return $this;
	}

	/**
	 * @return GraphEdge[]
	 */
	public function Edges () {
		return $this->_edges;
	}

	/**
	 * @param IQuarkGraphFormat $format = null
	 *
	 * @return string
	 */
	public function Render (IQuarkGraphFormat $format = null) {
		return $format != null ? $format->GraphFormatRender($this) : null;
	}

	/**
	 * @param IQuarkGraphFormat $format = null
	 *
	 * @return QuarkFile
	 */
	public function RenderFile (IQuarkGraphFormat $format = null) {
		if ($format == null) return null;

		$out = new QuarkFile();
		$out->Content($format->GraphFormatRender($this), true);

		return $out;
	}
}