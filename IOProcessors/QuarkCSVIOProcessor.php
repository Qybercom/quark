<?php
namespace Quark\IOProcessors;

use Quark\IQuarkIOProcessor;

use Quark\IQuarkModel;
use Quark\QuarkCollection;
use Quark\QuarkModel;
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
	 * @var object $_sample
	 */
	private $_sample;

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
	 * @var bool $_keysLikePHP = true
	 */
	private $_keysLikePHP = true;

	/**
	 * @param object $sample = null
	 * @param string $separatorValue = self::SEPARATOR_VALUE_COMMA
	 * @param bool $header = false
	 * @param string[] $force = []
	 * @param string $separatorBit = self::SEPARATOR_BIT_DOT
	 * @param bool $keysLikePHP = true
	 */
	public function __construct ($sample = null, $separatorValue = self::SEPARATOR_VALUE_COMMA, $header = false, $force = [], $separatorBit = self::SEPARATOR_BIT_DOT, $keysLikePHP = true) {
		$this->Sample($sample ? $sample : new \stdClass());
		$this->SeparatorValue($separatorValue);
		$this->Header($header);
		$this->Force($force);
		$this->SeparatorBit($separatorBit);
		$this->KeysLikePHP($keysLikePHP);
	}

	/**
	 * @param object $sample = null
	 *
	 * @return object
	 */
	public function Sample ($sample = null) {
		if (is_object($sample))
			$this->_sample = $sample;

		return $this->_sample;
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
	 * http://php.net/manual/ru/language.variables.external.php
	 *
	 * @param bool $enable = true
	 *
	 * @return bool
	 */
	public function KeysLikePHP ($enable = true) {
		if (func_num_args() != 0)
			$this->_keysLikePHP = $enable;

		return $this->_keysLikePHP;
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
	 * @return mixed[]|QuarkCollection
	 */
	public function Decode ($raw) {
		$rows = explode("\n", $raw);
		if (sizeof($rows) == 0) return array();

		$out = array();
		$header = null;
		$data = explode($this->_separatorValue, trim($rows[0]));

		if ($this->Header()) {
			foreach ($data as $j => &$field)
				$header[] = $field;

			$rows = array_slice($rows, 1);
		}

		foreach ($rows as $i => &$row) {
			$row = trim($row);
			if (strlen($row) == 0) continue;

			$line = $header ? clone $this->_sample : array();
			$data = explode($this->_separatorValue, $row);

			foreach ($data as $j => &$field) {
				$strict = preg_match('#="(.*)"#Uis', $field);
				if ($strict) $field = substr($field, 1);

				$field = trim(preg_replace('#(".*"|.*)#Uis', '$1', $field), '"');

				if (!$header) $line[] = $field;
				else {
					if (!isset($header[$j]) || ($this->_sample && $header[$j] == '')) continue;

					$key = $header[$j];
					if ($this->_keysLikePHP)
						$key = str_replace('.', '_', str_replace(' ', '_', $key));

					$line->$key = $field;

					if (!$strict) {
						if (preg_match('#^[0-9]+$#', $field)) $line->$key = (int)$line->$key;
						if (preg_match('#^[0-9\\' . $this->_separatorBit . ']+$#', $field)) $line->$key = (float)$line->$key;
						if ($field == 'true' || $field == 'false') $line->$key = (bool)$line->$key;
						if ($field == 'null') $line->$key = null;
					}
				}
			}

			if ($this->_sample instanceof IQuarkModel)
				$line = new QuarkModel($this->_sample, $line);

			$out[] = $line;
		}

		if ($this->_sample instanceof IQuarkModel)
			$out = new QuarkCollection($this->_sample, $out);

		return $out;
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
	 * @param object $sample = null
	 * @param string[] $force = []
	 * @param string $separatorBit = self::SEPARATOR_BIT_DOT
	 * @param bool $keysLikePHP = true
	 *
	 * @return QuarkCSVIOProcessor
	 */
	public static function ForExcel ($sample = null, $force = [], $separatorBit = self::SEPARATOR_BIT_DOT, $keysLikePHP = true) {
		return new self($sample, self::SEPARATOR_VALUE_SEMICOLON, true, $force, $separatorBit, $keysLikePHP);
	}

	/**
	 * @param object $sample = null
	 * @param string[] $force = []
	 * @param string $separatorBit = self::SEPARATOR_BIT_DOT
	 * @param bool $keysLikePHP = true
	 *
	 * @return QuarkCSVIOProcessor
	 */
	public static function WithHeader ($sample = null, $force = [], $separatorBit = self::SEPARATOR_BIT_DOT, $keysLikePHP = true) {
		return new self($sample, self::SEPARATOR_VALUE_COMMA, true, $force, $separatorBit, $keysLikePHP);
	}
}