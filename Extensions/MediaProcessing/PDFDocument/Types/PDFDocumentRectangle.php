<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Types;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentType;

/**
 * Class PDFDocumentRectangle
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Types
 */
class PDFDocumentRectangle implements IQuarkPDFDocumentType {
	/**
	 * @var float $_lowerLeftX = 0.0
	 */
	private $_lowerLeftX = 0.0;

	/**
	 * @var float $_lowerLeftY = 0.0
	 */
	private $_lowerLeftY = 0.0;

	/**
	 * @var float $_upperRightX = 0.0
	 */
	private $_upperRightX = 0.0;

	/**
	 * @var float $_upperRightY = 0.0
	 */
	private $_upperRightY = 0.0;

	/**
	 * @param float $lowerLeftX = 0.0
	 * @param float $lowerLeftY = 0.0
	 * @param float $upperRightX = 0.0
	 * @param float $upperRightY = 0.0
	 */
	public function __construct ($lowerLeftX = 0.0, $lowerLeftY = 0.0, $upperRightX = 0.0, $upperRightY = 0.0) {
		$this->LowerLeftX($lowerLeftX);
		$this->LowerLeftY($lowerLeftY);
		$this->UpperRightX($upperRightX);
		$this->UpperRightY($upperRightY);
	}

	/**
	 * @param float $value = 0.0
	 *
	 * @return float
	 */
	public function LowerLeftX ($value = 0.0) {
		if (func_num_args() != 0)
			$this->_lowerLeftX = $value;

		return $this->_lowerLeftX;
	}

	/**
	 * @param float $value = 0.0
	 *
	 * @return float
	 */
	public function LowerLeftY ($value = 0.0) {
		if (func_num_args() != 0)
			$this->_lowerLeftY = $value;

		return $this->_lowerLeftY;
	}

	/**
	 * @param float $value = 0.0
	 *
	 * @return float
	 */
	public function UpperRightX ($value = 0.0) {
		if (func_num_args() != 0)
			$this->_upperRightX = $value;

		return $this->_upperRightX;
	}

	/**
	 * @param float $value = 0.0
	 *
	 * @return float
	 */
	public function UpperRightY ($value = 0.0) {
		if (func_num_args() != 0)
			$this->_upperRightY = $value;

		return $this->_upperRightY;
	}

	/**
	 * @return float
	 */
	public function Width () {
		return $this->_upperRightX - $this->_lowerLeftX;
	}

	/**
	 * @return float
	 */
	public function Height () {
		return $this->_upperRightY - $this->_lowerLeftY;
	}

	/**
	 * @return mixed
	 */
	public function PDFTypeEncode () {
		return array(
			$this->_lowerLeftX,
			$this->_lowerLeftY,
			$this->_upperRightX,
			$this->_upperRightY
		);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function PDFTypeDecode ($raw) {
		if (is_array($raw) && sizeof($raw) == 4) {
			$this->LowerLeftX($raw[0]);
			$this->LowerLeftY($raw[1]);
			$this->UpperRightX($raw[2]);
			$this->UpperRightY($raw[3]);
		}
	}

	/**
	 * @param float[] $source = []
	 *
	 * @return PDFDocumentRectangle
	 */
	public static function FromArray ($source = []) {
		return sizeof($source) == 4 ? new self((float)$source[0], (float)$source[1], (float)$source[2], (float)$source[3]) : null;
	}
}