<?php
namespace Quark\Extensions\Quark\SOAP;

use Quark\QuarkXMLNode;

/**
 * Class SOAPElement
 *
 * @package QuarkTools\SOAP
 */
class SOAPElement {
	/**
	 * @var string $_key = ''
	 */
	private $_key = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_xmlns = ''
	 */
	private $_xmlns = '';

	/**
	 * @var $_data = []
	 */
	private $_data = array();

	/**
	 * @param string $key = ''
	 * @param string $name = ''
	 * @param string $xmlns = ''
	 * @param $data = []
	 */
	public function __construct ($key = '', $name = '', $xmlns = '', $data = []) {
		$this->Key($key);
		$this->Name($name);
		$this->XMLNS($xmlns);
		$this->Data($data);
	}

	/**
	 * @param string $key = ''
	 *
	 * @return string
	 */
	public function Key ($key = '') {
		if (func_num_args() != 0)
			$this->_key = $key;

		return $this->_key;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}

	/**
	 * @param string $xmlns = ''
	 *
	 * @return string
	 */
	public function XMLNS ($xmlns = '') {
		if (func_num_args() != 0)
			$this->_xmlns = $xmlns;

		return $this->_xmlns;
	}

	/**
	 * @param $data = []
	 *
	 * @return mixed
	 */
	public function Data ($data = []) {
		if (func_num_args() != 0)
			$this->_data = $data;

		return $this->_data;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function ToXML () {
		return new QuarkXMLNode(
			$this->_key . ':' . $this->_name,
			$this->_data,
			array('xmlns:' . $this->_key => $this->_xmlns)
		);
	}

	/**
	 * @param QuarkXMLNode $xml = null
	 *
	 * @return SOAPElement
	 */
	public static function FromXML (QuarkXMLNode $xml = null) {
		if ($xml == null) return null;

		$name = explode(':', $xml->Name());
		if (sizeof($name) != 2) return null;

		return new self($name[0], $name[1], $xml->Attribute('xmlns'), $xml->Data());
	}
}