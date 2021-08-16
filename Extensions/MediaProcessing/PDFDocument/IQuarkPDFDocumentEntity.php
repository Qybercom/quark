<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument;

/**
 * Interface IQuarkPDFDocumentEntity
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument
 */
interface IQuarkPDFDocumentEntity {
	/**
	 * @return string
	 */
	public function PDFEntityType();

	/**
	 * @return PDFDocumentObject[]
	 */
	public function PDFEntityObjectList();

	/**
	 * @param PDFDocumentObject $object
	 *
	 * @return IQuarkPDFDocumentEntity
	 */
	public function PDFEntityPopulate(PDFDocumentObject &$object);
}