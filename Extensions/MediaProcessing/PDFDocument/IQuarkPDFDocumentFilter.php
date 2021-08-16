<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument;

/**
 * Interface IQuarkPDFDocumentFilter
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument
 */
interface IQuarkPDFDocumentFilter {
	/**
	 * @return string
	 */
	public function PDFFilterName();

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function PDFFilterEncode($data);

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function PDFFilterDecode($data);
}