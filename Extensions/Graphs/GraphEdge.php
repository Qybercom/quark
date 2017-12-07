<?php
namespace Quark\Extensions\Graphs;

/**
 * Class GraphEdge
 *
 * @package Quark\Extensions\Graphs
 */
class GraphEdge {
	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var bool $_directed = false
	 */
	private $_directed = false;

	/**
	 * @var string $_source = null
	 */
	private $_source = null;

	/**
	 * @var string $_target = null
	 */
	private $_target = null;

	/**
	 * @var array $_attributes = []
	 */
	private $_attributes = array();

	/**
	 * @param string $source = null
	 * @param string $target = null
	 * @param bool $directed = false
	 * @param string $id = ''
	 */
	public function __construct ($source = null, $target = null, $directed = false, $id = '') {
		$this->Source($source);
		$this->Target($target);
		$this->Directed($directed);
		$this->ID($id);
	}

	/**
	 * @param string $source = null
	 *
	 * @return string
	 */
	public function Source ($source = null) {
		if (func_num_args() != 0)
			$this->_source = $source;

		return $this->_source;
	}

	/**
	 * @param string $target = null
	 *
	 * @return string
	 */
	public function Target ($target = null) {
		if (func_num_args() != 0)
			$this->_target = $target;

		return $this->_target;
	}

	/**
	 * @param bool $directed = false
	 *
	 * @return bool
	 */
	public function Directed ($directed = false) {
		if (func_num_args() != 0)
			$this->_directed = (bool)$directed;

		return $this->_directed;
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
	 * @param string $auto = ''
	 *
	 * @return string
	 */
	public function IDOrAuto ($auto = '') {
		return $this->_id == '' ? $auto : $this->_id;
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