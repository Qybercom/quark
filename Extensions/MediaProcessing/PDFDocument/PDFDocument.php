<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument;

use Quark\QuarkFile;
use Quark\QuarkCollection;
use Quark\QuarkKeyValuePair;

use Quark\Extensions\MediaProcessing\PDFDocument\Entities\PDFDocumentPage;
use Quark\Extensions\MediaProcessing\PDFDocument\Entities\PDFDocumentPages;
use Quark\Extensions\MediaProcessing\PDFDocument\Entities\PDFDocumentCatalog;
use Quark\Extensions\MediaProcessing\PDFDocument\Entities\PDFDocumentInfo;
use Quark\Extensions\MediaProcessing\PDFDocument\Entities\PDFDocumentTrailer;

use Quark\Extensions\MediaProcessing\PDFDocument\Filters\PDFDocumentFilterASCIIHexDecode;
use Quark\Extensions\MediaProcessing\PDFDocument\Filters\PDFDocumentFilterFlateDecode;
use Quark\Extensions\MediaProcessing\PDFDocument\Filters\PDFDocumentFilterLZWDecode;

/**
 * Class PDFDocument
 *
 * https://archive.org/details/pdf320002008_201911/page/n53/mode/2up
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument
 */
class PDFDocument {
	const VERSION_1_0 = '%PDF-1.0';
	const VERSION_1_1 = '%PDF-1.1';
	const VERSION_1_2 = '%PDF-1.2';
	const VERSION_1_3 = '%PDF-1.3';
	const VERSION_1_4 = '%PDF-1.4';
	const VERSION_1_5 = '%PDF-1.5';
	const VERSION_1_6 = '%PDF-1.6';
	const VERSION_1_7 = '%PDF-1.7';

	const MARKER_BINARY = 0xE2E3CFD3;
	const MARKER_XREF = 'xref';
	const MARKER_XREF_START = 'startxref';
	const MARKER_TRAILER = 'trailer';
	const MARKER_EOF = '%%EOF';

	const ATTRIBUTE_ID = 'ID';
	const ATTRIBUTE_TYPE = 'Type';

	const REF_DEFAULT_OFFSET = '0000000000';
	const REF_DEFAULT_VERSION = 65535;
	const REF_DEFAULT_MODE = 'f';

	/**
	 * @var IQuarkPDFDocumentEntity[] $_entityTypes
	 */
	private static $_entityTypes = array();

	/**
	 * @var IQuarkPDFDocumentFilter[] $_filters = []
	 */
	private static $_filters = array();

	/**
	 * @var null $_null = null
	 */
	private static $_null = null;

	/**
	 * @var string $_version = self::VERSION_1_4
	 */
	private $_version = self::VERSION_1_4;

	/**
	 * @var QuarkCollection|PDFDocumentPage[] $_pages
	 */
	private $_pages;

	/**
	 * @var PDFDocumentPages[] $_nodes = []
	 */
	private $_nodes = array();

	/**
	 * @var PDFDocumentInfo $_info
	 */
	private $_info;

	/**
	 * @var PDFDocumentCatalog $_catalog
	 */
	private $_catalog;

	/**
	 * @var PDFDocumentTrailer $_trailer
	 */
	private $_trailer;

	/**
	 * @param string $version = self::VERSION_1_4
	 */
	public function __construct ($version = self::VERSION_1_4) {
		$this->_pages = new QuarkCollection(new PDFDocumentPage());
		$this->_info = new PDFDocumentInfo();
		$this->_catalog = new PDFDocumentCatalog();
		$this->_trailer = new PDFDocumentTrailer();

		if (sizeof(self::$_entityTypes) == 0)
			self::$_entityTypes = array(
				new PDFDocumentPage(),
				new PDFDocumentPages()
			);

		if (sizeof(self::$_filters) == 0)
			self::$_filters = array(
				new PDFDocumentFilterASCIIHexDecode(),
				new PDFDocumentFilterFlateDecode(),
				new PDFDocumentFilterLZWDecode()
			);
	}

	/**
	 * @return QuarkCollection|PDFDocumentPage[]
	 */
	public function &Pages () {
		return $this->_pages;
	}

	/**
	 * @return PDFDocumentPages[]
	 */
	public function &Nodes () {
		return $this->_nodes;
	}

	/**
	 * @return PDFDocumentInfo
	 */
	public function &Info () {
		return $this->_info;
	}

	/**
	 * @return PDFDocumentCatalog
	 */
	public function &Catalog () {
		return $this->_catalog;
	}

	/**
	 * @return PDFDocumentTrailer
	 */
	public function &Trailer () {
		return $this->_trailer;
	}

	/**
	 * @return string
	 */
	public function PDFEncode () {
		$out = $this->_version . "\n" . self::MARKER_BINARY . "\n";

		$ref = array(
			self::REF_DEFAULT_OFFSET . ' ' . self::REF_DEFAULT_VERSION . ' ' . self::REF_DEFAULT_MODE
		);

		$registry = array();

		/**
		 * @var PDFDocumentObject[] $objects
		 */
		$objects = array();

		foreach ($objects as $i => &$object) {
			$object->IDExternal($registry[$object->IDInternal()]);

			$ref[] = str_pad(strlen($out), 10, '0', STR_PAD_LEFT) . ' ' . $object->Version() . ' n';

			$out .= $object->PDFEncode();
		}

		$trailer = $this->_trailer->PDFObjectList()[0];

		return $out
			. self::MARKER_XREF . "\n"
			. '0 ' . sizeof($objects) . "\n"
			. implode("\n", $ref) . "\n"
			. self::MARKER_TRAILER . "\n"
			. $trailer->PDFEncode() . "\n"
			. self::MARKER_XREF_START . "\n"
			. strlen($out) . "\n"
			. self::MARKER_EOF . "\n";
	}

	/**
	 * @param string $raw = ''
	 *
	 * @return bool
	 */
	public function PDFDecode ($raw = '') {
		$len = strlen($raw);

		$i = 0;
		$buffer = '';
		$version = false;

		$trailer = false;
		$trailer_str = "\n" . self::MARKER_TRAILER;
		$trailer_str_len = strlen($trailer_str);
		$trailer_start = 0;
		$trailer_data = null;
		$trailer_obj = null;

		while ($i < $len) {
			if (!$version) {
				$buffer .= $raw[$i];

				if ($raw[$i] == "\n") {
					$this->_version = trim($buffer);
					$buffer = '';
					$version = true;
				}
			}

			if (!$trailer) {
				if (substr($raw, $i, $trailer_str_len) == $trailer_str) {
					$trailer_start = $i + $trailer_str_len;
					$trailer = true;
				}
			}
			else {
				if (PDFDocumentIOProcessor::IsSpace($raw[$trailer_start])) $trailer_start++;
				else {
					$trailer = false;
					$trailer_data = PDFDocumentIOProcessor::Unserialize($raw, $trailer_start, $len);

					if (isset($trailer_data[self::ATTRIBUTE_ID]) && sizeof($trailer_data[self::ATTRIBUTE_ID]) == 2)
						$trailer_data[self::ATTRIBUTE_ID] = new QuarkKeyValuePair(
							$trailer_data[self::ATTRIBUTE_ID][0],
							$trailer_data[self::ATTRIBUTE_ID][1]
						);

					$trailer_obj = new PDFDocumentObject($trailer_data);
					$this->_trailer->PDFEntityPopulate($trailer_obj);
				}
			}

			$i++;
		}

		$objects = PDFDocumentObject::Recognize($raw);
		$buffer = null;
		$data = null;

		$obj_root = $this->_trailer->Root();
		$obj_info = $this->_trailer->Info();

		foreach ($objects as $i => &$object) {
			$data = $object->Data();

			if (isset($data[self::ATTRIBUTE_TYPE]))
				foreach (self::$_entityTypes as $j => &$sample) {
					if ($data[self::ATTRIBUTE_TYPE] != $sample->PDFEntityType()) continue;

					if ($sample instanceof IQuarkPDFDocumentEntity) {
						/**
						 * @var IQuarkPDFDocumentEntity $buffer
						 */
						$buffer = clone $sample;

						$buffer->PDFEntityPopulate($object);

						if ($sample instanceof PDFDocumentPage)
							$this->_pages[] = $buffer;

						if ($sample instanceof PDFDocumentPages)
							$this->_nodes[] = $buffer;
					}
				}

			if ($obj_root != null && $i == $obj_root->IDInternal())
				$this->_catalog->PDFEntityPopulate($object);

			if ($obj_info != null && $i == $obj_info->IDInternal())
				$this->_catalog->PDFEntityPopulate($object);
		}

		/*if ($this->_trailer->Root() != null)
			$this->_trailer->Root()->PopulateByReference($this->_catalog, $objects);

		if ($this->_trailer->Info() != null)
			$this->_trailer->Info()->PopulateByReference($this->_info, $objects);*/

		//print_r($objects);
		print_r($this);

		return true;
	}

	/**
	 * @param QuarkFile $file = null
	 *
	 * @return bool
	 */
	public function PDFDecodeFile (QuarkFile $file = null) {
		if ($file == null) return false;

		return true;
	}

	/**
	 * @param string $name
	 *
	 * @return IQuarkPDFDocumentFilter
	 */
	public static function &FilterByName ($name) {
		foreach (self::$_filters as $i => &$filter)
			if ($filter->PDFFilterName() == $name)
				return $filter;

		return self::$_null;
	}
}