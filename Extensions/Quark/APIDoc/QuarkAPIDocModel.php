<?php
namespace Quark\Extensions\Quark\APIDoc;

use Quark\QuarkField;
use Quark\QuarkObject;

/**
 * Class QuarkAPIDocModel
 *
 * @package Quark\Extensions\Quark\APIDoc
 */
class QuarkAPIDocModel {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_description = ''
	 */
	private $_description = '';

	/**
	 * @var QuarkField[] $_fields = []
	 */
	private $_fields = array();

	/**
	 * @var QuarkField[] $_constants = []
	 */
	private $_constants = array();

	/**
	 * @param string $name = ''
	 * @param string $description = ''
	 * @param QuarkField[] $fields = []
	 * @param QuarkField[] $constants = []
	 */
	public function __construct ($name = '', $description = '', $fields = [], $constants = []) {
		$this->_name = $name;
		$this->_description = strlen($description) == 0
			? '<i>No description</i>'
			:  $description;

		if (QuarkObject::IsArrayOf($fields, new QuarkField()))
			$this->_fields = $fields;

		if (QuarkObject::IsArrayOf($constants, new QuarkField()))
			$this->_constants = $constants;
	}

	/**
	 * @return string
	 */
	public function Name () {
		return $this->_name;
	}

	/**
	 * @return string
	 */
	public function Description () {
		return $this->_description;
	}

	/**
	 * @return QuarkField[]
	 */
	public function Fields () {
		return $this->_fields;
	}

	/**
	 * @return QuarkField[]
	 */
	public function Constants () {
		return $this->_constants;
	}
}