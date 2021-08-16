<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument;

/**
 * Class PDFDocumentReference
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument
 */
class PDFDocumentReference {
	/**
	 * @var int $_idInternal null
	 */
	private $_idInternal = null;

	/**
	 * @var string $_idExternal = null
	 */
	private $_idExternal = null;

	/**
	 * @var int $_version = 0
	 */
	private $_version = 0;

	/**
	 * @var int $_offset = null
	 */
	private $_offset = null;

	/**
	 * @param int $id = null
	 *
	 * @return int
	 */
	public function IDInternal ($id = null) {
		if (func_num_args() != 0)
			$this->_idInternal = $id;

		return $this->_idInternal;
	}

	/**
	 * @param int $id = null
	 *
	 * @return int
	 */
	public function IDExternal ($id = null) {
		if (func_num_args() != 0)
			$this->_idExternal = $id;

		return $this->_idExternal;
	}

	/**
	 * @param int $version = 0
	 *
	 * @return int
	 */
	public function Version ($version = 0) {
		if (func_num_args() != 0)
			$this->_version = $version;

		return $this->_version;
	}

	/**
	 * @param int $offset = null
	 *
	 * @return int
	 */
	public function Offset ($offset = null) {
		if (func_num_args() != 0)
			$this->_offset = $offset;

		return $this->_offset;
	}

	/**
	 * @param IQuarkPDFDocumentEntity $entity = null
	 * @param PDFDocumentObject[] $source = []
	 *
	 * @return bool
	 */
	public function PopulateByReference (IQuarkPDFDocumentEntity &$entity = null, $source = []) {
		if ($entity == null) return false;
		if (!isset($source[$this->IDInternal()])) return false;

		$entity->PDFEntity_old(array($source[$this->IDInternal()]));

		return true;
	}

	/**
	 * @param array $link = []
	 *
	 * @return PDFDocumentReference
	 */
	public static function FromReference ($link = []) {
		if (sizeof($link) != 3) return null;

		$out = new self();

		$out->IDInternal((int)$link[1]);
		$out->Version((int)$link[2]);

		return $out;
	}
}