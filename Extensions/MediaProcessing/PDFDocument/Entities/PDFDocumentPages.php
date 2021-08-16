<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Entities;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentEntity;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentObject;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentReference;

/**
 * Class PDFDocumentPages
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Entities
 */
class PDFDocumentPages implements IQuarkPDFDocumentEntity {
	const TYPE_PDF = '/Pages';

	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'Type' => 'Type',
		'Parent' => 'Parent',
		'Kids' => 'Kids',
		'Count' => 'Count'
	);

	/**
	 * @var string $_type = self::TYPE_PDF
	 */
	private $_type = self::TYPE_PDF;

	/**
	 * @var PDFDocumentReference $_parent
	 */
	private $_parent;

	/**
	 * @var PDFDocumentReference[] $_kids = []
	 */
	private $_kids = array();

	/**
	 * @var int $_count = 0
	 */
	private $_count = 0;

	/**
	 * @param string $type = self::TYPE_PDF
	 *
	 * @return string
	 */
	public function Type ($type = self::TYPE_PDF) {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @param PDFDocumentReference $parent = null
	 *
	 * @return PDFDocumentReference
	 */
	public function &Parent (PDFDocumentReference &$parent = null) {
		if (func_num_args() != 0)
			$this->_parent = $parent;

		return $this->_parent;
	}

	/**
	 * @param PDFDocumentReference[] $kids = []
	 *
	 * @return PDFDocumentReference[]
	 */
	public function &Kids ($kids = []) {
		if (func_num_args() != 0)
			$this->_kids = $kids;

		return $this->_kids;
	}

	/**
	 * @param PDFDocumentReference $kid = null
	 *
	 * @return PDFDocumentPages
	 */
	public function Kid (PDFDocumentReference &$kid = null) {
		if ($kid != null)
			$this->_kids[] = $kid;

		return $this;
	}

	/**
	 * @param int $count = 0
	 *
	 * @return int
	 */
	public function Count ($count = 0) {
		if (func_num_args() != 0)
			$this->_count = $count;

		return $this->_count;
	}

	/**
	 * @return string
	 */
	public function PDFEntityType () {
		return self::TYPE_PDF;
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