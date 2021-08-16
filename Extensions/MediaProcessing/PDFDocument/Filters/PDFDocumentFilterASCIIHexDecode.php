<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Filters;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentFilter;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentIOProcessor;

/**
 * Class PDFDocumentFilterASCIIHexDecode
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Filters
 */
class PDFDocumentFilterASCIIHexDecode implements IQuarkPDFDocumentFilter {
	const TYPE_PDF_NAME = '/ASCIIHexDecode';

	/**
	 * @return string
	 */
	public function PDFFilterName () {
		return self::TYPE_PDF_NAME;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function PDFFilterEncode ($data) {
		return PDFDocumentIOProcessor::SerializeHex($data);
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function PDFFilterDecode ($data) {
		return PDFDocumentIOProcessor::UnserializeHex($data);
	}
}