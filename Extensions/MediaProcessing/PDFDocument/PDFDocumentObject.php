<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument;

use Quark\QuarkObject;

/**
 * Class PDFDocumentObject
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument
 */
class PDFDocumentObject {
	const MARKER_OBJECT = 'obj';
	const MARKER_OBJECT_END = 'endobj';
	const MARKER_STREAM = 'stream';
	const MARKER_STREAM_END = 'endstream';

	const ATTRIBUTE_TYPE  = 'Type';
	const ATTRIBUTE_SUBTYPE = 'Subtype';
	const ATTRIBUTE_S = 'S'; // same as Subtype for some cases
	const ATTRIBUTE_METADATA = 'Metadata';
	const ATTRIBUTE_LENGTH = 'Length';
	const ATTRIBUTE_FILTER = 'Filter';

	const REF_USED = 'n';
	const REF_FREE = 'f';

	/**
	 * @var $_data = null
	 */
	private $_data = null;

	/**
	 * @var string $_stream = null
	 */
	private $_stream = null;

	/**
	 * @var int $_idInternal null
	 */
	private $_idInternal = null;

	/**
	 * @var int $_idExternal = null
	 */
	private $_idExternal = null;

	/**
	 * @var int $_version = 0
	 */
	private $_version = 0;

	/**
	 * @var IQuarkPDFDocumentFilter[] $_filters = []
	 */
	private $_filters = array();

	/**
	 * @param $data = null
	 * @param string $stream = null
	 * @param int $idExternal = null
	 * @param int $version = 0
	 */
	public function __construct ($data = null, $stream = null, $idExternal = null, $version = 0) {
		$this->Data($data);
		$this->Stream($stream);
		$this->IDExternal($idExternal);
		$this->Version($version);
	}

	/**
	 * @param $data = null
	 *
	 * @return mixed
	 */
	public function Data ($data = null) {
		if (func_num_args() != 0)
			$this->_data = $data;

		return $this->_data;
	}

	/**
	 * @param string $stream = null
	 * @param bool $filtersApply = false
	 *
	 * @return string
	 */
	public function Stream ($stream = null, $filtersApply = false) {
		if (func_num_args() != 0) {
			$this->_stream = $stream;

			if ($filtersApply && isset($this->_data[self::ATTRIBUTE_FILTER])) {
				$filters = $this->_data[self::ATTRIBUTE_FILTER];

				if (is_string($filters))
					$filters = array($filters);

				$filter = null;
				foreach ($filters as $i => &$name) {
					$filter = PDFDocument::FilterByName($name);
					if ($filter == null) {
						// TODO: unknown filter

						break;
					}

					$this->_stream = $filter->PDFFilterDecode($this->_stream);
				}
			}
		}

		return $this->_stream;
	}

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
	 * @return IQuarkPDFDocumentFilter[]
	 */
	public function &Filters () {
		return $this->_filters;
	}

	/**
	 * @param IQuarkPDFDocumentFilter $filter = null
	 *
	 * @return IQuarkPDFDocumentFilter[]
	 */
	public function Filter (IQuarkPDFDocumentFilter $filter = null) {
		if ($filter != null) {
			$this->_filters[] = $filter;
			$this->_data(self::ATTRIBUTE_FILTER, $filter->PDFFilterName());
		}

		return $this->_filters;
	}

	/**
	 * @param string $key = ''
	 * @param $value = null
	 * @param bool $array = false
	 */
	private function _data ($key = '', $value = null, $array = false) {
		if ($this->_data === null)
			$this->_data = array();

		if (!$array) {
			if (is_array($this->_data))
				$this->_data[$key] = $value;
			return;
		}

		if (!isset($this->_data[$key]))
			$this->_data[$key] = array();

		$this->_data[$key][] = $value;
	}

	/**
	 * @return string
	 */
	public function PDFEncode () {
		$data = PDFDocumentIOProcessor::Serialize($this->_data);

		return $this->_idExternal . ' ' . $this->_version . ' ' . self::MARKER_OBJECT . "\n"
			. ($data != '' ? $data . "\n" : '')
			. ($this->_stream != '' ? self::MARKER_STREAM . "\n" . $this->_stream . "\n" . self::MARKER_STREAM_END . "\n" : '')
			. self::MARKER_OBJECT_END . "\n";
	}

	/**
	 * @param string $raw
	 * @param int $i = 0
	 * @param int $obj_len = 0
	 */
	public function PDFDecode (&$raw, $i = 0, $obj_len = 0) {
		if (func_num_args() < 3)
			$obj_len = strlen($raw);
		$obj_end = "\n" . self::MARKER_OBJECT_END;
		$obj_end_len = strlen($obj_end);

		$header = true;
		$data = false;
		$stream = false;
		$stream_str = self::MARKER_STREAM;
		$stream_str_len = strlen($stream_str);
		$stream_end = self::MARKER_STREAM_END;
		$stream_end_len = strlen($stream_end);
		$buffer = '';

		while ($i < $obj_len) {
			if ($header) {
				if ($raw[$i] == "\n") {
					$header = false;
					$header_data = explode(' ', $buffer);

					$this->_idInternal = $header_data[0];
					$this->_version = $header_data[1];

					$buffer = '';
				}
				else $buffer .= $raw[$i];
			}
			else {
				if ($stream) {
					if (substr($raw, $i, $stream_end_len) == $stream_end) {
						$this->Stream(substr(ltrim(rtrim($buffer, "\r\0\t\f"), "\r\n\0\t\f"), 0, -1), true);
						$stream = false;
						$i += $stream_end_len - 1;
					}
					else $buffer .= $raw[$i];
				}
				else {
					if ($data) {
						if (
							substr($raw, $i, $stream_str_len) == $stream_str ||
							substr($raw, $i, $obj_end_len) == $obj_end
						) {
							$this->_data = PDFDocumentIOProcessor::Unserialize($buffer);
							$buffer = '';
							$data = false;
							$i--;
						}
						else $buffer .= $raw[$i];
					}
					else {
						if (substr($raw, $i, $stream_str_len) == $stream_str) {
							$stream = true;
							$i += $stream_str_len - 1;
							$buffer = '';
						}
						else {
							$buffer .= $raw[$i];
							$data = true;
						}
					}
				}
			}

			$i++;
		}
	}

	/**
	 * @param IQuarkPDFDocumentEntity $entity = null
	 * @param string[] $properties = []
	 * @param callable $filter = null
	 *
	 * @return bool
	 */
	public function PDFEntityPopulate (IQuarkPDFDocumentEntity &$entity = null, $properties = [], callable $filter = null) {
		if ($entity == null || !QuarkObject::isTraversable($properties)) return false;

		$buffer = null;

		foreach ($properties as $key => &$property) {
			if (!isset($this->_data[$key]) || !method_exists($entity, $property)) continue;

			$buffer = $this->_data[$key];
			if ($filter != null)
				$buffer = $filter($key, $buffer);

			$entity->$property($buffer);
		}

		return true;
	}

	/**
	 * @param string $type = ''
	 * @param array|object $data = null
	 * @param string $stream = null
	 * @param string $subType = null
	 * @param bool $subTypeAlt = false
	 *
	 * @return PDFDocumentObject
	 */
	public static function WithType ($type = '', $data = null, $stream = null, $subType = null, $subTypeAlt = false) {
		$data = is_object($data) || is_array($data) ? (array)$data : array();

		$data[self::ATTRIBUTE_TYPE] = $type;

		if (func_num_args() > 3)
			$data[$subTypeAlt ? self::ATTRIBUTE_S : self::ATTRIBUTE_SUBTYPE] = $subType;

		return new self($data, $stream);
	}

	/**
	 * @param string $raw
	 * @param int $i = 0
	 *
	 * @return PDFDocumentObject[]
	 */
	public static function Recognize (&$raw, $i = 0) {
		$len = strlen($raw);

		$obj = false;
		$obj_str = ' ' . self::MARKER_OBJECT;
		$obj_str_len = strlen($obj_str);
		$obj_end_str = "\n" . self::MARKER_OBJECT_END;
		$obj_end_str_len = strlen($obj_end_str);
		$obj_pos_start = 0;
		$obj_content = '';
		$obj_item = null;

		$last_return = 0;

		$out = array();

		while ($i < $len) {
			if ($raw[$i] == "\n") $last_return = $i;

			if ($obj) {
				if (substr($raw, $i, $obj_end_str_len) == $obj_end_str) {
					$obj = false;

					$obj_item = new self();
					$obj_item->PDFDecode($raw, $obj_pos_start, $i + $obj_end_str_len);

					$out[$obj_item->IDInternal()] = $obj_item;
				}
				else $obj_content .= $raw[$i];
			}
			else {
				if (substr($raw, $i, $obj_str_len) == $obj_str) {
					$obj = true;
					$obj_pos_start = $last_return + 1;
				}
			}

			$i++;
		}

		return $out;
	}
}