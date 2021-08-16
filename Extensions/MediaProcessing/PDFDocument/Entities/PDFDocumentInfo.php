<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument\Entities;

use Quark\QuarkDate;

use Quark\Extensions\MediaProcessing\PDFDocument\IQuarkPDFDocumentEntity;
use Quark\Extensions\MediaProcessing\PDFDocument\PDFDocumentObject;

/**
 * Class PDFDocumentInfo
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument\Entities
 */
class PDFDocumentInfo implements IQuarkPDFDocumentEntity {
	const TRAPPED_TRUE = 'True';
	const TRAPPED_FALSE = 'False';
	const TRAPPED_UNKNOWN = 'Unknown';

	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'Title' => 'Title',
		'Author' => 'Author',
		'Subject' => 'Subject',
		'Keywords' => 'Keywords',
		'Creator' => 'Creator',
		'Producer' => 'Producer',
		'CreationDate' => 'CreationDate',
		'ModDate' => 'ModDate',
		'Trapped' => 'Trapped'
	);

	/**
	 * @var string $_title
	 */
	private $_title;

	/**
	 * @var string $_author
	 */
	private $_author;

	/**
	 * @var string $_subject
	 */
	private $_subject;

	/**
	 * @var string $_keywords
	 */
	private $_keywords;

	/**
	 * @var string $_creator
	 */
	private $_creator;

	/**
	 * @var string $_producer
	 */
	private $_producer;

	/**
	 * @var QuarkDate $_creationDate
	 */
	private $_creationDate;

	/**
	 * @var QuarkDate $_modDate
	 */
	private $_modDate;

	/**
	 * @var string $_trapped
	 */
	private $_trapped;

	/**
	 * @param string $title = ''
	 *
	 * @return string
	 */
	public function Title ($title = '') {
		if (func_num_args() != 0)
			$this->_title = $title;

		return $this->_title;
	}

	/**
	 * @param string $author = ''
	 *
	 * @return string
	 */
	public function Author ($author = '') {
		if (func_num_args() != 0)
			$this->_author = $author;

		return $this->_author;
	}

	/**
	 * @param string $subject = ''
	 *
	 * @return string
	 */
	public function Subject ($subject = '') {
		if (func_num_args() != 0)
			$this->_subject = $subject;

		return $this->_subject;
	}

	/**
	 * @param string $keywords = ''
	 *
	 * @return string
	 */
	public function Keywords ($keywords = '') {
		if (func_num_args() != 0)
			$this->_keywords = $keywords;

		return $this->_keywords;
	}

	/**
	 * @param string $creator = ''
	 *
	 * @return string
	 */
	public function Creator ($creator = '') {
		if (func_num_args() != 0)
			$this->_creator = $creator;

		return $this->_creator;
	}

	/**
	 * @param string $producer = ''
	 *
	 * @return string
	 */
	public function Producer ($producer = '') {
		if (func_num_args() != 0)
			$this->_producer = $producer;

		return $this->_producer;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function CreationDate (QuarkDate $date = null) {
		if (func_num_args() != 0)
			$this->_creationDate = $date;

		return $this->_creationDate;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function ModDate (QuarkDate $date = null) {
		if (func_num_args() != 0)
			$this->_modDate = $date;

		return $this->_modDate;
	}

	/**
	 * @param string $trapped = ''
	 *
	 * @return string
	 */
	public function Trapped ($trapped = '') {
		if (func_num_args() != 0)
			$this->_trapped = $trapped;

		return $this->_trapped;
	}

	/**
	 * @return string
	 */
	public function PDFEntityType () {
		// TODO: Implement PDFEntityType() method.
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
	}
}