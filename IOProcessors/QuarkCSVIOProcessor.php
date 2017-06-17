<?php
namespace Quark\IOProcessors;

use Quark\IQuarkIOProcessor;

use Quark\QuarkCollection;
use Quark\QuarkObject;

/**
 * Class QuarkCSVIOProcessor
 *
 * https://ru.wikipedia.org/wiki/CSV
 * https://habrahabr.ru/company/mailru/blog/129476/
 *
 * @package Quark\IOProcessors
 */
class QuarkCSVIOProcessor implements IQuarkIOProcessor {
	const MIME = 'text/csv';

	const SEPARATOR_VALUE_COMMA = ',';
	const SEPARATOR_VALUE_SEMICOLON = ';';
	const SEPARATOR_BIT_DOT = '.';
	const SEPARATOR_BIT_COMMA = ',';

	/**
	 * @var string $_separatorValue = self::SEPARATOR_VALUE_COMMA
	 */
	private $_separatorValue = self::SEPARATOR_VALUE_COMMA;

	/**
	 * @var bool $_header = false
	 */
	private $_header = false;

	/**
	 * @var string[] $_force = []
	 */
	private $_force = array();

	/**
	 * @var string $_separatorBit = self::SEPARATOR_BIT_DOT
	 */
	private $_separatorBit = self::SEPARATOR_BIT_DOT;

	/**
	 * @param string $separatorValue = self::SEPARATOR_VALUE_COMMA
	 * @param bool $header = false
	 * @param string[] $force = []
	 * @param string $separatorBit = self::SEPARATOR_BIT_DOT
	 */
	public function __construct ($separatorValue = self::SEPARATOR_VALUE_COMMA, $header = false, $force = [], $separatorBit = self::SEPARATOR_BIT_DOT) {
		$this->SeparatorValue($separatorValue);
		$this->Header($header);
		$this->Force($force);
		$this->SeparatorBit($separatorBit);
	}

	/**
	 * @param string $separator = self::SEPARATOR_VALUE_COMMA
	 *
	 * @return string
	 */
	public function SeparatorValue ($separator = self::SEPARATOR_VALUE_COMMA) {
		if (func_num_args() != 0)
			$this->_separatorValue = $separator;

		return $this->_separatorValue;
	}

	/**
	 * @param bool $header = false
	 *
	 * @return bool
	 */
	public function Header ($header = false) {
		if (func_num_args() != 0)
			$this->_header = $header;

		return $this->_header;
	}

	/**
	 * @param string[] $force = []
	 *
	 * @return string[]
	 */
	public function Force ($force = []) {
		if (func_num_args() != 0)
			$this->_force = $force;

		return $this->_force;
	}

	/**
	 * @param string $separator = self::SEPARATOR_BIT_DOT
	 *
	 * @return string
	 */
	public function SeparatorBit ($separator = self::SEPARATOR_BIT_DOT) {
		if (func_num_args() != 0)
			$this->_separatorBit = $separator;

		return $this->_separatorBit;
	}

	/**
	 * @return string
	 */
	public function MimeType () { return self::MIME; }

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function Encode ($data) {
		if (!QuarkObject::isTraversable($data)) return '';

		if ($data instanceof QuarkCollection)
			$data = $data->Extract();

		$out = '';
		$header = array();

		foreach ($data as $i => &$item) {
			if (!QuarkObject::isTraversable($item)) continue;

			$line = array();

			foreach ($item as $key => &$value) {
				if ($this->_header && !in_array($key, $header))
					$header[] = $key;

				$line[] = (in_array($key, $this->_force) ? '=' : '')
						. ('"' . str_replace('"', '""', (is_float($value) ? str_replace('.', $this->_separatorBit, (string)$value) : $value)) . '"');
			}

			$out .= implode($this->_separatorValue, $line) . "\r\n";
		}

		return ($this->_header ? (implode($this->_separatorValue, $header) . "\r\n") : '') . $out;
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) {
		// TODO: Implement Decode() method.
	}

	/**
	 * @param string $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) {
		// TODO: Implement Batch() method.
	}

	/**
	 * @param string[] $force = []
	 * @param string $separatorBit = self::SEPARATOR_BIT_DOT
	 *
	 * @return QuarkCSVIOProcessor
	 */
	public static function ForExcel ($force = [], $separatorBit = self::SEPARATOR_BIT_DOT) {
		return new self(self::SEPARATOR_VALUE_SEMICOLON, true, $force, $separatorBit);
	}
}