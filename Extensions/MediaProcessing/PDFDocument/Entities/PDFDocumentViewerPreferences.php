<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Entities;

/**
 * Class PDFDocumentViewerPreferences
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Entities
 */
class PDFDocumentViewerPreferences {
	const PDF_KEY = 'ViewerPreferences';

	const DIRECTION_L2R = 'L2R';
	const DIRECTION_R2L = 'R2L';

	/**
	 * @var string[]
	 */
	private static $_properties = array(
		'HideToolbar' => 'HideToolBar',
		'HideMenubar' => 'HideMenuBar',
		'HideWindowUI' => 'HideWindowUI',
		'FitWindow' => 'FitWindow',
		'CenterWindow' => 'CenterWindow',
		'DisplayDocTitle' => 'DisplayDocTitle',
		'NonFullScreenPageMode' => 'NonFullScreenPageMode',
		'Direction' => 'Direction',
		'ViewArea' => 'ViewArea',
		'ViewClip' => 'ViewClip',
		'PrintArea' => 'PrintArea',
		'PrintClip' => 'PrintClip',
		'PrintScaling' => 'PrintScaling',
		'Duplex' => 'Duplex',
		'PickTrayByPDFSize' => 'PickTrayByPDFSizer',
		'PrintPageRange' => 'PrintPageRange',
		'NumCopies' => 'NumCopies'
	);

	/**
	 * @var bool $_hideToolBar = false
	 */
	private $_hideToolBar = false;

	/**
	 * @var bool $_hideMenuBar = false
	 */
	private $_hideMenuBar = false;

	/**
	 * @var bool $_hideWindowUI = false
	 */
	private $_hideWindowUI = false;

	/**
	 * @var bool $_fitWindow = false
	 */
	private $_fitWindow = false;

	/**
	 * @var bool $_centerWindow = false
	 */
	private $_centerWindow = false;

	/**
	 * @var bool $_displayDocTitle = false
	 */
	private $_displayDocTitle = false;

	/**
	 * @var string $_nonFullScreenPageMode = PDFDocumentPage::MODE_USE_NONE
	 */
	private $_nonFullScreenPageMode = PDFDocumentPage::MODE_USE_NONE;

	/**
	 * @var string $_direction = self::DIRECTION_L2R
	 */
	private $_direction = self::DIRECTION_L2R;

	/**
	 * @var string $_viewArea = PDFDocumentPage::BOUNDARY_CROP_BOX
	 */
	private $_viewArea = PDFDocumentPage::BOUNDARY_CROP_BOX;

	/**
	 * @var string $_viewClip = PDFDocumentPage::BOUNDARY_CROP_BOX
	 */
	private $_viewClip = PDFDocumentPage::BOUNDARY_CROP_BOX;

	/**
	 * @var string $_printArea = PDFDocumentPage::BOUNDARY_CROP_BOX
	 */
	private $_printArea = PDFDocumentPage::BOUNDARY_CROP_BOX;

	/**
	 * @var string $_printClip = PDFDocumentPage::BOUNDARY_CROP_BOX
	 */
	private $_printClip = PDFDocumentPage::BOUNDARY_CROP_BOX;

	/**
	 * @var string $_printScaling = PDFDocumentPage::PRINT_SCALING_APP_DEFAULT
	 */
	private $_printScaling = PDFDocumentPage::PRINT_SCALING_APP_DEFAULT;

	/**
	 * @var string $_duplex = PDFDocumentPage::DUPLEX_NONE
	 */
	private $_duplex = PDFDocumentPage::DUPLEX_NONE;

	/**
	 * @var bool $_pickTrayByPDFSize = false
	 */
	private $_pickTrayByPDFSize = false;

	/**
	 * @var int[] $_printPageRange = []
	 */
	private $_printPageRange = array();

	/**
	 * @var int $_numCopies = 1
	 */
	private $_numCopies = 1;

	/**
	 * @return string[]
	 */
	public static function Properties () {
		return self::$_properties;
	}

	/**
	 * @param bool $hide = false
	 *
	 * @return bool
	 */
	public function HideToolBar ($hide = false) {
		if (func_num_args() != 0)
			$this->_hideToolBar = $hide;

		return $this->_hideToolBar;
	}

	/**
	 * @param bool $hide = false
	 *
	 * @return bool
	 */
	public function HideMenuBar ($hide = false) {
		if (func_num_args() != 0)
			$this->_hideMenuBar = $hide;

		return $this->_hideMenuBar;
	}

	/**
	 * @param bool $hide = false
	 *
	 * @return bool
	 */
	public function HideWindowUI ($hide = false) {
		if (func_num_args() != 0)
			$this->_hideWindowUI = $hide;

		return $this->_hideWindowUI;
	}

	/**
	 * @param bool $fit = false
	 *
	 * @return bool
	 */
	public function FitWindow ($fit = false) {
		if (func_num_args() != 0)
			$this->_fitWindow = $fit;

		return $this->_fitWindow;
	}

	/**
	 * @param bool $center = false
	 *
	 * @return bool
	 */
	public function CenterWindow ($center = false) {
		if (func_num_args() != 0)
			$this->_centerWindow = $center;

		return $this->_centerWindow;
	}

	/**
	 * @param bool $display = false
	 *
	 * @return bool
	 */
	public function DisplayDocTitle ($display = false) {
		if (func_num_args() != 0)
			$this->_displayDocTitle = $display;

		return $this->_displayDocTitle;
	}

	/**
	 * @param string $mode = PDFDocumentPage::MODE_USE_NONE
	 *
	 * @return string
	 */
	public function NonFullScreenPageMode ($mode = PDFDocumentPage::MODE_USE_NONE) {
		if (func_num_args() != 0)
			$this->_nonFullScreenPageMode = $mode;

		return $this->_nonFullScreenPageMode;
	}

	/**
	 * @param string $direction = self::DIRECTION_L2R
	 *
	 * @return string
	 */
	public function Direction ($direction = self::DIRECTION_L2R) {
		if (func_num_args() != 0)
			$this->_direction = $direction;

		return $this->_direction;
	}

	/**
	 * @param string $viewArea = PDFDocumentPage::BOUNDARY_CROP_BOX
	 *
	 * @return string
	 */
	public function ViewArea ($viewArea = PDFDocumentPage::BOUNDARY_CROP_BOX) {
		if (func_num_args() != 0)
			$this->_viewArea = $viewArea;

		return $this->_viewArea;
	}

	/**
	 * @param string $viewClip = PDFDocumentPage::BOUNDARY_CROP_BOX
	 *
	 * @return string
	 */
	public function ViewClip ($viewClip = PDFDocumentPage::BOUNDARY_CROP_BOX) {
		if (func_num_args() != 0)
			$this->_viewClip = $viewClip;

		return $this->_viewClip;
	}

	/**
	 * @param string $printArea = PDFDocumentPage::BOUNDARY_CROP_BOX
	 *
	 * @return string
	 */
	public function PrintArea ($printArea = PDFDocumentPage::BOUNDARY_CROP_BOX) {
		if (func_num_args() != 0)
			$this->_printArea = $printArea;

		return $this->_printArea;
	}

	/**
	 * @param string $printClip = PDFDocumentPage::BOUNDARY_CROP_BOX
	 *
	 * @return string
	 */
	public function PrintClip ($printClip = PDFDocumentPage::BOUNDARY_CROP_BOX) {
		if (func_num_args() != 0)
			$this->_printClip = $printClip;

		return $this->_printClip;
	}

	/**
	 * @param string $scaling = PDFDocumentPage::PRINT_SCALING_APP_DEFAULT
	 *
	 * @return string
	 */
	public function PrintScaling ($scaling = PDFDocumentPage::PRINT_SCALING_APP_DEFAULT) {
		if (func_num_args() != 0)
			$this->_printScaling = $scaling;

		return $this->_printScaling;
	}

	/**
	 * @param string $duplex = PDFDocumentPage::DUPLEX_NONE
	 *
	 * @return string
	 */
	public function Duplex ($duplex = PDFDocumentPage::DUPLEX_NONE) {
		if (func_num_args() != 0)
			$this->_duplex = $duplex;

		return $this->_duplex;
	}

	/**
	 * @param bool $pick = false
	 *
	 * @return bool
	 */
	public function PickTrayByPDFSize ($pick = false) {
		if (func_num_args() != 0)
			$this->_pickTrayByPDFSize = $pick;

		return $this->_pickTrayByPDFSize;
	}

	/**
	 * @param int[] $range = []
	 *
	 * @return int[]
	 */
	public function PrintPageRange ($range = []) {
		if (func_num_args() != 0)
			$this->_printPageRange = $range;

		return $this->_printPageRange;
	}

	/**
	 * @param int $num = 1
	 *
	 * @return int
	 */
	public function NumCopies ($num = 1) {
		if (func_num_args() != 0)
			$this->_numCopies = $num;

		return $this->_numCopies;
	}
}