<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Entities;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentEntity;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentObject;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentReference;

/**
 * Class PDFDocumentBead
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Entities
 */
class PDFDocumentBead implements IQuarkPDFDocumentEntity {
	const TYPE_PDF = '/Bead';

	/**
	 * @return PDFDocumentObject[]
	 */
	public function PDFObjectList () {
		// TODO: Implement PDFObjectList() method.
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