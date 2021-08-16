<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Filters;

use Quark\Extensions\Quark\Compressors\LZWCompressor;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentFilter;

/**
 * Class PDFDocumentFilterLZWDecode
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Filters
 */
class PDFDocumentFilterLZWDecode implements IQuarkPDFDocumentFilter {
	const TYPE_PDF_NAME = '/LZWDecode';

	/**
	 * @var LZWCompressor $_compressor
	 */
	private $_compressor;

	/**
	 * PDFDocumentFilterLZWDecode constructor.
	 */
	public function __construct () {
		$this->_compressor = new LZWCompressor(self::Dictionary());
	}

	/**
	 * @return LZWCompressor
	 */
	public function &Compressor () {
		return $this->_compressor;
	}

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
		return $this->_compressor->Compress($data);
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function PDFFilterDecode ($data) {
		return $this->_compressor->Decompress($data);
	}

	/**
	 * @return string[]
	 */
	public static function Dictionary () {
		return LZWCompressor::DictionaryASCII(LZWCompressor::ASCII_MAX_EXTENDED + 2);
	}
}