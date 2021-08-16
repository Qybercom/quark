<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Entities;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentEntity;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentObject;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentReference;

/**
 * Class PDFDocumentMetadata
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Entities
 */
class PDFDocumentMetadata implements IQuarkPDFDocumentEntity {
	const TYPE_PDF = '/Metadata';
	const TYPE_PDF_SUBTYPE = 'XML';

	/**
	 * @var string $_type = self::TYPE_PDF
	 */
	private $_type = self::TYPE_PDF;

	/**
	 * @var string $_subType = self::TYPE_PDF_SUBTYPE
	 */
	private $_subType = self::TYPE_PDF_SUBTYPE;

	/**
	 * @var string $_data
	 */
	private $_data;

	/**
	 * @return string
	 */
	public function Type () {
		return $this->_type;
	}

	/**
	 * @return string
	 */
	public function SubType () {
		return $this->_subType;
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function Data ($data = '') {
		if (func_num_args() != 0)
			$this->_data = $data;

		return $this->_data;
	}

	/**
	 * @return PDFDocumentObject[]
	 */
	public function PDFObjectList () {
		return array(
			PDFDocumentObject::WithType(
				self::TYPE_PDF,
				array(),
				$this->_data,
				$this->_subType
			)
		);
	}

	/**
	 * @param PDFDocumentObject[] $objects
	 *
	 * @return IQuarkPDFDocumentEntity
	 */
	public function PDFEntity_old ($objects) {
		// TODO: Implement PDFEntity_old() method.
	}

	/**
	 * @param PDFDocumentObject[] $objects
	 * @param PDFDocumentReference $reference
	 *
	 * @return IQuarkPDFDocumentEntity
	 */
	public function PDFEntityByReference ($objects, PDFDocumentReference $reference) {
		// TODO: Implement PDFEntityByReference() method.
	}
}