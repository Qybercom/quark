<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Entities;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentEntity;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentObject;

/**
 * Class PDFDocumentTransition
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Entities
 */
class PDFDocumentTransition implements IQuarkPDFDocumentEntity {
	const TYPE_PDF = '/Trans';

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
		// TODO: Implement PDFEntityPopulate() method.
	}
}