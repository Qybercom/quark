<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Entities;

use Quark\QuarkDate;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentEntity;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentReference;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentObject;
use Quark\Extensions\MediaProcessing\PDFDocument\Types\PDFDocumentRectangle;

/**
 * Class PDFDocumentPage
 *
 * https://www.oreilly.com/library/view/pdf-explained/9781449321581/ch04.html
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Entities
 */
class PDFDocumentPage implements IQuarkPDFDocumentEntity {
	const TYPE_PDF = '/Page';

	const BOUNDARY_MEDIA_BOX = 'MediaBox';
	const BOUNDARY_CROP_BOX = 'CropBox';
	const BOUNDARY_BLEED_BOX = 'BleedBox';
	const BOUNDARY_TRIM_BOX = 'TrimBox';
	const BOUNDARY_ART_BOX = 'ArtBox';

	const MODE_USE_NONE = 'UseNone';
	const MODE_USE_OUTLINES = 'UseOutlines';
	const MODE_USE_THUMBS = 'UseThumbs';
	const MODE_FULL_SCREEN = 'FullScreen';
	const MODE_USE_OPTIONAL_CONTENT = 'UseOC';
	const MODE_USE_ATTACHMENTS = 'UseAttachments';

	const LAYOUT_SINGLE_PAGE = 'SinglePage';
	const LAYOUT_ONE_COLUMN = 'OneColumn';
	const LAYOUT_TWO_COLUMN_LEFT = 'TwoColumnLeft';
	const LAYOUT_TWO_COLUMN_RIGHT = 'TwoColumnRight';
	const LAYOUT_TWO_PAGE_LEFT = 'TwoPageLeft';
	const LAYOUT_TWO_PAGE_RIGHT = 'TwoPageRight';

	const DIRECTION_L2R = 'L2R';
	const DIRECTION_R2L = 'R2L';

	const PRINT_SCALING_NONE = 'None';
	const PRINT_SCALING_APP_DEFAULT = 'AppDefault';

	const DUPLEX_SIMPLEX = 'Simplex';
	const DUPLEX_DUPLEX_FLIP_SHORT_EDGE = 'DuplexFlipShortEdge';
	const DUPLEX_DUPLEX_FLIP_LONG_EDGE = 'DuplexFlipLongEdge';
	const DUPLEX_NONE = null;

	const TAB_ORDER_ROW = 'R';
	const TAB_ORDER_COLUMN = 'C';
	const TAB_ORDER_STRUCTURE = 'S';

	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'Type' => 'Type',
		'Contents' => 'Contents',
		'Parent' => 'Parent',
		'LastModified' => 'LastModified',
		'MediaBox' => 'MediaBox',
		'CropBox' => 'CropBox',
		'BleedBox' => 'BleedBox',
		'TrimBox' => 'TrimBox',
		'ArtBox' => 'ArtBox',
		'Rotate' => 'Rotate'
	);

	/**
	 * @var string $_propertiesRectangle
	 */
	private static $_propertiesRectangle = array(
		'MediaBox',
		'CropBox',
		'BleedBox',
		'TrimBox',
		'ArtBox'
	);

	/**
	 * @var string $_type = self::TYPE_PDF
	 */
	private $_type = self::TYPE_PDF;

	/**
	 * @var PDFDocumentReference[] $_contents
	 */
	private $_contents;

	/**
	 * @var PDFDocumentReference $_Parent
	 */
	private $_Parent; // dictionary (Required; shall be an indirect reference)

	/**
	 * @var QuarkDate $_LastModified
	 */
	private $_LastModified; // date (Required if PieceInfo is present; optional otherwise; PDF 1.3)

	/**
	 * @var array|object $_Resources
	 */
	private $_Resources; // dictionary (Required; inheritable)

	/**
	 * @var PDFDocumentRectangle $_MediaBox
	 */
	private $_MediaBox; // rectangle (Required; inheritable)

	/**
	 * @var PDFDocumentRectangle $_CropBox
	 */
	private $_CropBox; // rectangle (Optional; inheritable)

	/**
	 * @var PDFDocumentRectangle $_BleedBox
	 */
	private $_BleedBox; // rectangle (Optional; PDF 1.3)

	/**
	 * @var PDFDocumentRectangle $_TrimBox
	 */
	private $_TrimBox; // rectangle (Optional; PDF 1.3)

	/**
	 * @var PDFDocumentRectangle $_ArtBox
	 */
	private $_ArtBox; // rectangle (Optional; PDF 1.3)

	/**
	 * @var array|object $_BoxColorInfo
	 */
	private $_BoxColorInfo; // dictionary (Optional; PDF 1.4)

	/**
	 * @var int $_Rotate
	 */
	private $_Rotate; // integer (Optional; inheritable) Default value: 0.

	/**
	 * @var array|object $_Group
	 */
	private $_Group; // dictionary (Optional; PDF 1.4)

	/**
	 * @var string $_Thumb
	 */
	private $_Thumb; // stream (Optional)

	/**
	 * @var PDFDocumentReference[] $_B
	 */
	private $_B; // array (Optional; PDF 1.1; recommended if the page contains article beads)

	/**
	 * @var int $_Dur
	 */
	private $_Dur; // number (Optional; PDF 1.1)

	/**
	 * @var PDFDocumentTransition $_Trans
	 */
	private $_Trans; // dictionary (Optional; PDF 1.1)

	/**
	 * @var PDFDocumentReference[] $_Annots
	 */
	private $_Annots; // array (Optional)

	/**
	 * @var array|object $_AA
	 */
	private $_AA; // dictionary (Optional; PDF 1.2)

	/**
	 * @var string $_Metadata
	 */
	private $_Metadata; // stream (Optional; PDF 1.4)

	/**
	 * @var array|object $_PieceInfo
	 */
	private $_PieceInfo; // dictionary (Optional; PDF 1.3)

	/**
	 * @var int $_StructParents
	 */
	private $_StructParents; // integer (Required if the page contains structural content items; PDF 1.3)

	/**
	 * @var string $_ID
	 */
	private $_ID; // byte string (Optional; PDF 1.3; indirect reference preferred)

	/**
	 * @var int $_PZ
	 */
	private $_PZ; // number (Optional; PDF 1.3)

	/**
	 * @var array|object $_SeparationInfo
	 */
	private $_SeparationInfo; // dictionary (Optional; PDF 1.3)

	/**
	 * @var string $_Tabs
	 */
	private $_Tabs; // name (Optional; PDF 1.5)

	/**
	 * @var string $_TemplateInstantiated
	 */
	private $_TemplateInstantiated; // name (Required if this page was created from a named page object; PDF 1.5)

	/**
	 * @var array|object $_PresSteps
	 */
	private $_PresSteps; // dictionary (Optional; PDF 1.5)

	/**
	 * @var float $_UserUnit
	 */
	private $_UserUnit; // number (Optional; PDF 1.6) Default value: 1.0 (user space unit is 1⁄72 inch).

	/**
	 * @var array|object $_VP
	 */
	private $_VP; // dictionary (Optional; PDF 1.6)

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
	 * @param PDFDocumentReference[] $contents = []
	 *
	 * @return PDFDocumentReference[]
	 */
	public function &Contents ($contents = []) {
		if (func_num_args() != 0)
			$this->_contents = $contents instanceof PDFDocumentReference ? array($contents) : $contents;

		return $this->_contents;
	}

	/**
	 * @param PDFDocumentReference $parent = null
	 *
	 * @return PDFDocumentReference
	 */
	public function &Parent (PDFDocumentReference &$parent = null) {
		if (func_num_args() != 0)
			$this->_Parent = $parent;

		return $this->_Parent;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function &LastModified (QuarkDate $date = null) {
		if (func_num_args() != 0)
			$this->_LastModified = $date;

		return $this->_LastModified;
	}

	/**
	 * @param PDFDocumentRectangle $box = null
	 *
	 * @return PDFDocumentRectangle
	 */
	public function MediaBox (PDFDocumentRectangle $box = null) {
		if (func_num_args() != 0)
			$this->_MediaBox = $box;

		return $this->_MediaBox;
	}

	/**
	 * @param PDFDocumentRectangle $box = null
	 *
	 * @return PDFDocumentRectangle
	 */
	public function CropBox (PDFDocumentRectangle $box = null) {
		if (func_num_args() != 0)
			$this->_CropBox = $box;

		return $this->_CropBox;
	}

	/**
	 * @param PDFDocumentRectangle $box = null
	 *
	 * @return PDFDocumentRectangle
	 */
	public function BleedBox (PDFDocumentRectangle $box = null) {
		if (func_num_args() != 0)
			$this->_BleedBox = $box;

		return $this->_BleedBox;
	}

	/**
	 * @param PDFDocumentRectangle $box = null
	 *
	 * @return PDFDocumentRectangle
	 */
	public function TrimBox (PDFDocumentRectangle $box = null) {
		if (func_num_args() != 0)
			$this->_TrimBox = $box;

		return $this->_TrimBox;
	}

	/**
	 * @param PDFDocumentRectangle $box = null
	 *
	 * @return PDFDocumentRectangle
	 */
	public function ArtBox (PDFDocumentRectangle $box = null) {
		if (func_num_args() != 0)
			$this->_ArtBox = $box;

		return $this->_ArtBox;
	}

	/**
	 * @param int $rotate = 0
	 *
	 * @return int
	 */
	public function Rotate ($rotate = 0) {
		if (func_num_args() != 0)
			$this->_Rotate = $rotate;

		return $this->_Rotate;
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
		$object->PDFEntityPopulate($this, self::$_properties, function ($key, &$value) {
			if (in_array($key, self::$_propertiesRectangle))
				return PDFDocumentRectangle::FromArray($value);

			//if ($key == 'Trans')

			return $value;
		});
	}
}