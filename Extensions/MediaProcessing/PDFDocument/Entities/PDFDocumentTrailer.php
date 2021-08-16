<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Entities;

use Quark\QuarkKeyValuePair;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentEntity;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentObject;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentReference;

/**
 * Class PDFDocumentTrailer
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Entities
 */
class PDFDocumentTrailer implements IQuarkPDFDocumentEntity {
	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'Size' => 'Size',
		'Prev' => 'Prev',
		'Root' => 'Root',
		'Encrypt' => 'Encrypt',
		'Info' => 'Info',
		'ID' => 'ID',
		'XRefStm' => 'XRefStm'
	);

	/**
	 * @var int $_size = 0
	 */
	private $_size = 0;

	/**
	 * @var int $_prev
	 */
	private $_prev;

	/**
	 * @var PDFDocumentReference $_root
	 */
	private $_root;

	/**
	 * @var array|object $_encrypt
	 */
	private $_encrypt;

	/**
	 * @var PDFDocumentReference $_info
	 */
	private $_info;

	/**
	 * @var QuarkKeyValuePair $_id
	 */
	private $_id;

	/**
	 * @var array|object $_xRefStm
	 */
	private $_xRefStm;

	/**
	 * @param int $size = 0
	 *
	 * @return int
	 */
	public function Size ($size = 0) {
		if (func_num_args() != 0)
			$this->_size = $size;

		return $this->_size;
	}

	/**
	 * @param int $prev = 0
	 *
	 * @return int
	 */
	public function Prev ($prev = 0) {
		if (func_num_args() != 0)
			$this->_prev = $prev;

		return $this->_prev;
	}

	/**
	 * @param PDFDocumentReference $root = null
	 *
	 * @return PDFDocumentReference
	 */
	public function &Root (PDFDocumentReference &$root = null) {
		if (func_num_args() != 0)
			$this->_root = $root;

		return $this->_root;
	}

	/**
	 * @param array|object $encrypt = null
	 *
	 * @return array|object
	 */
	public function Encrypt ($encrypt = null) {
		if (func_num_args() != 0)
			$this->_encrypt = $encrypt;

		return $this->_encrypt;
	}

	/**
	 * @param PDFDocumentReference $info = null
	 *
	 * @return PDFDocumentReference
	 */
	public function &Info (&$info = null) {
		if (func_num_args() != 0)
			$this->_info = $info;

		return $this->_info;
	}

	/**
	 * @param QuarkKeyValuePair $root = null
	 *
	 * @return QuarkKeyValuePair
	 */
	public function &ID (QuarkKeyValuePair $root = null) {
		if (func_num_args() != 0)
			$this->_id = $root;

		return $this->_root;
	}

	/**
	 * @param array|object $xRefStm = null
	 *
	 * @return array|object
	 */
	public function XRefStm ($xRefStm = null) {
		if (func_num_args() != 0)
			$this->_xRefStm = $xRefStm;

		return $this->_xRefStm;
	}

	/**
	 * @return string
	 */
	public function PDFEntityType () {
		// TODO: Implement PDFEntityType() method.
	}

	/**
	 * @return PDFDocumentObject[]
	 */
	public function PDFEntityObjectList () {
		// TODO: Implement PDFEntityObjectList() method.
	}

	/**
	 * @param PDFDocumentObject $object
	 *
	 * @return IQuarkPDFDocumentEntity
	 */
	public function PDFEntityPopulate (PDFDocumentObject &$object) {
		$object->PDFEntityPopulate($this, self::$_properties);
	}
}