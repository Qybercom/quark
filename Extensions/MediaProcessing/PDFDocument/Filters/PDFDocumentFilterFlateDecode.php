<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Filters;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentFilter;

/**
 * Class PDFDocumentFilterFlateDecode
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Filters
 */
class PDFDocumentFilterFlateDecode implements IQuarkPDFDocumentFilter {
	const TYPE_PDF_NAME = '/FlateDecode';

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
		return zlib_encode($data, ZLIB_ENCODING_DEFLATE);
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function PDFFilterDecode ($data) {
		return zlib_decode($data);
	}
}