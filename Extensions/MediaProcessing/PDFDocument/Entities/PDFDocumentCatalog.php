<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Entities;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentEntity;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentObject;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentReference;

/**
 * Class PDFDocumentCatalog
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Entities
 */
class PDFDocumentCatalog implements IQuarkPDFDocumentEntity {
	const TYPE_PDF = '/Catalog';

	const VERSION_1_0 = '/1.0';
	const VERSION_1_1 = '/1.1';
	const VERSION_1_2 = '/1.2';
	const VERSION_1_3 = '/1.3';
	const VERSION_1_4 = '/1.4';
	const VERSION_1_5 = '/1.5';
	const VERSION_1_6 = '/1.6';
	const VERSION_1_7 = '/1.7';

	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'Type' => 'Type',
		'Pages' => 'Pages',
		'Outlines' => 'Outlines'
	);

	/**
	 * @var string $_type = self::TYPE_PDF
	 */
	private $_type = self::TYPE_PDF;

	/**
	 * @var string $_version
	 */
	private $_version;

	private $_extensions;

	/**
	 * @var PDFDocumentReference $_pages
	 */
	private $_pages;

	private $_pageLabels;

	private $_pageLayout;

	private $_pageMode;

	private $_names;

	private $_dests;

	/**
	 * @var PDFDocumentViewerPreferences $_viewerPreferences
	 */
	private $_viewerPreferences;

	/**
	 * @var PDFDocumentReference $_outlines
	 */
	private $_outlines;

	private $_threads;

	private $_openAction;

	private $_aa;

	private $_uri;

	private $_acroForm;

	private $_metadata;

	private $_structTreeRoot;

	private $_markInfo;

	private $_lang;

	private $_spiderInfo;

	private $_outputIntents;

	private $_pieceInfo;

	private $_ocProperties;

	private $_perms;

	private $_legal;

	private $_requirements;

	private $_collection;

	/**
	 * @var bool $_needsRendering = null
	 */
	private $_needsRendering = null;

	/**
	 * @param string $version = self::VERSION_1_4
	 */
	public function __construct ($version = self::VERSION_1_4) {
		$this->_viewerPreferences = new PDFDocumentViewerPreferences();
	}

	/**
	 * @param string $type = self::TYPE_PDF
	 *
	 * @return string
	 */
	public function Type ($type = self::TYPE_PDF) {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @param PDFDocumentReference $pages = null
	 *
	 * @return PDFDocumentReference
	 */
	public function &Pages (PDFDocumentReference &$pages = null) {
		if (func_num_args() != 0)
			$this->_pages = $pages;

		return $this->_pages;
	}

	/**
	 * @param PDFDocumentReference $outlines = null
	 *
	 * @return PDFDocumentReference
	 */
	public function &Outlines (PDFDocumentReference &$outlines = null) {
		if (func_num_args() != 0)
			$this->_outlines = $outlines;

		return $this->_outlines;
	}

	/**
	 * @return PDFDocumentViewerPreferences
	 */
	public function &ViewerPreferences () {
		return $this->_viewerPreferences;
	}

	/**
	 * @return string
	 */
	public function PDFEntityType () {
		return self::TYPE_PDF;
	}

	/**
	 * @return PDFDocumentObject[]
	 */
	public function PDFEntityObjectList () {
		// TODO: Implement PDFEntityObjectList() method.
	}

	/**
	 * @param PDFDocumentObject $object
	 *
	 * @return IQuarkPDFDocumentEntity
	 */
	public function PDFEntityPopulate (PDFDocumentObject &$object) {
		$object->PDFEntityPopulate($this, self::$_properties);

		$data = $object->Data();

		if (isset($data[PDFDocumentViewerPreferences::PDF_KEY]))
			$object->PDFEntityPopulate($this->_viewerPreferences, PDFDocumentViewerPreferences::Properties());
	}
}