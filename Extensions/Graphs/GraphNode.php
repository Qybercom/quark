<?php
namespace Quark\Extensions\Graphs;

/**
 * Class GraphNode
 *
 * @package Quark\Extensions\Graphs
 */
class GraphNode {
	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var array $_attributes = []
	 */
	private $_attributes = array();

	/**
	 * @param string $id = ''
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
	 * @param string $key = ''
	 * @param string $value = null
	 *
	 * @return mixed
	 */
	public function Attribute ($key = '', $value = null) {
		if (func_num_args() == 2)
			$this->_attributes[$key] = $value;

		return isset($this->_attributes[$key]) ? $this->_attributes[$key] : null;
	}

	/**
	 * @param string $key = ''
	 * @param string $default = null
	 *
	 * @return mixed
	 */
	public function AttributeOrDefault ($key = '', $default = null) {
		return isset($this->_attributes[$key]) ? $this->_attributes[$key] : $default;
	}

	/**
	 * @return array
	 */
	public function Attributes () {
		return $this->_attributes;
	}
}