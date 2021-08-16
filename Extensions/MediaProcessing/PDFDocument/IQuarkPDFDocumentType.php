<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument;

/**
 * Interface IQuarkPDFDocumentType
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument
 */
interface IQuarkPDFDocumentType {
	/**
	 * @return mixed
	 */
	public function PDFTypeEncode();

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function PDFTypeDecode($raw);
}