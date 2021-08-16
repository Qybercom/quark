<?php
namespace Quark\Extensions\MediaProcessing\PDFDocument;

use Quark\IQuarkIOProcessor;

use Quark\QuarkDate;
use Quark\QuarkObject;

/**
 * Class PDFDocumentIOProcessor
 *
 * @package Quark\Extensions\MediaProcessing\PDFDocument
 */
class PDFDocumentIOProcessor implements IQuarkIOProcessor {
	const MIME = 'application/pdf';

	const REGEX_DATE = '#D:(\d{4})(0[1-9]|1[0-2])?(0[1-9]|[12][0-9]|3[01])?(0[0-9]|1[0-9]|2[0-3])?(0[0-9]|[12345][0-9])?(0[0-9]|[12345][0-9])?([\-+Z]((0[0-9]|1[0-9]|2[0-3])\'(0[0-9]|[12345][0-9]))?)?#s';
	const FORMAT_DATE = '\(\D\:YmdHis\)';
	const FORMAT_DATE_FULL = '\(\D\:YmdHis-00\'00\)';

	/**
	 * @var bool $_forceNull = false
	 */
	private $_forceNull = false;

	/**
	 * @param bool $force = false
	 *
	 * @return bool
	 */
	public function ForceNull ($force = false) {
		if (func_num_args() != 0)
			$this->_forceNull = $force;

		return $this->_forceNull;
	}

	/**
	 * @return string
	 */
	public function MimeType () {
		return self::MIME;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function Encode ($data) {
		$doc = $data;

		if (!($data instanceof PDFDocument)) {
			$doc = new PDFDocument();

			if (QuarkObject::isTraversable($data))
				foreach ($data as $i => &$value) {}
		}
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
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		// TODO: Implement Batch() method.
	}

	/**
	 * @return bool
	 */
	public function ForceInput () {
		// TODO: Implement ForceInput() method.
	}

	/**
	 * @param $data = null
	 * @param bool $forceNull = false
	 *
	 * @return string
	 */
	public static function Serialize (&$data = null, $forceNull = false) {
		$out = null;

		if (is_object($data)) {
			$dict = '';

			foreach ($data as $k => &$v)
				$dict .= '/' . $k . self::Serialize($v);

			unset($k, $v);

			$out = '<<' . $dict . '>>';
		}

		if (is_array($data)) {
			$out = '[';

			foreach ($data as $i => &$value)
				$out .= self::Serialize($value) . ' ';

			unset($i, $value);

			$out = trim($out) . ']';
		}

		if (is_string($data)) $out = '(' . self::EscapeString($data) . ')';
		if (is_float($data)) $out = ' ' . (float)$data;
		if (is_int($data)) $out = ' ' . (int)$data;
		if (is_bool($data)) $out = $data ? ' true' : ' false';
		if ($forceNull && $data === null) $out = ' null';

		return $out;
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public static function EscapeString ($data = '') {
		return str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $data)));
	}

	/**
	 * @param string $raw = ''
	 * @param int $i = 0
	 * @param int $len = 0
	 *
	 * @return array|bool|float|int|null|string
	 */
	public static function Unserialize (&$raw = '', &$i = 0, &$len = 0) {
		if ($raw == '') return null;

		if (func_num_args() < 3)
			$len = strlen($raw);

		if ($len > 1 && $raw[$i] . $raw[$i + 1] == '<<') {
			$i += 2;
			$out = array();

			$key = false;
			$key_data = '';
			$key_space = false;

			while ($i < $len) {
				if ($key) {
					if ($key_space) {
						if (!self::IsSpace($raw[$i])) {
							$key = false;
							$out[$key_data] = self::Unserialize($raw, $i, $len);
						}
					}
					else {
						if (self::IsSpace($raw[$i])) {
							$key_space = true;
							$out[$key_data] = null;
						}
						else {
							if (self::IsSpecialOpen($raw[$i])) {
								$key = false;
								$out[$key_data] = self::Unserialize($raw, $i, $len);
							}
							else {
								$key_data .= $raw[$i];
							}
						}
					}
				}
				else {
					if (isset($raw[$i + 1]) && $raw[$i] . $raw[$i + 1] == '>>') {
						$i += 1;
						break;
					}
					else {
						if ($raw[$i] == '/') {
							$key = true;
							$key_data = '';
							$key_space = false;
						}
					}
				}

				$i++;
			}

			return $out;
		}

		if ($raw[$i] == '[') {
			$i++;
			$out = array();

			$item = false;

			while ($i < $len) {
				if ($item) {
					if ($raw[$i] == ']') break;

					else {
						$out[] = self::Unserialize($raw, $i, $len);
						$item = false;

						if ($raw[$i] == ']') break;
					}
				}
				else {
					if ($raw[$i] == ']') break;

					if (!self::IsSpace($raw[$i])) {
						$item = true;
						$i--;
					}
				}

				$i++;
			}

			return $out;
		}

		if ($raw[$i] == '(') {
			$i++;
			$out = '';

			while ($i < $len) {
				if ($raw[$i] != ')') {
					$out .= $raw[$i];
				}
				else {
					$out = self::UnescapeString($out);

					break;
				}

				$i++;
			}

			if (preg_match(self::REGEX_DATE, $out, $date))
				return QuarkDate::GMTOf(''
					. $date[1]
					. (isset($date[2])
						?
							'-' . $date[2]
							. (isset($date[3]) ? '-' . $date[3] : '01')
							. (isset($date[4]) ? ' ' . $date[4] : '00')
							. (isset($date[5]) ? ':' . $date[5] : '00')
							. (isset($date[6]) ? ':' . $date[6] : '00')
						: ''
					)
					. ''
				);

			return $out;
		}

		if ($raw[$i] == '<') {
			$i++;
			$out = '';

			while ($i < $len) {
				if ($raw[$i] != '>') {
					if (!self::IsSpace($raw[$i]))
						$out .= $raw[$i];
				}
				else {
					$out = self::UnserializeHex($out);

					break;
				}

				$i++;
			}

			return $out;
		}

		if ($raw[$i] == '/') {
			$i++;
			$out = '/';

			while ($i < $len) {
				if (self::IsSpecialClose($raw[$i], true)) {
					$i--;
					break;
				}

				$out .= $raw[$i];

				$i++;
			}

			return $out;
		}

		if (strtolower(substr($raw, $i, 4)) == 'true') {
			$i += 3;
			return true;
		}

		if (strtolower(substr($raw, $i, 5)) == 'false') {
			$i += 4;
			return false;
		}

		if (strtolower(substr($raw, $i, 4)) == 'null') {
			$i += 3;
			return true;
		}

		if (preg_match('#\G([0-9]+)\s+([0-9]+)\s*R#Uis', $raw, $link, 0, $i)) {
			$i += strlen($link[0]) - 1;

			return PDFDocumentReference::FromReference($link);
		}

		$buffer = '';
		$start = false;

		while ($i < $len) {
			if (self::IsSpace($raw[$i])) {
				if ($start) return $buffer;
				else {
					$i++;
					continue;
				}
			}
			else {
				$start = true;
			}

			if (self::IsSpecialClose($raw[$i])) {
				$i--;
				return $buffer;
			}

			$buffer .= $raw[$i];

			$i++;
		}

		//return null;
		return $buffer;
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public static function UnescapeString ($data = '') {
		return str_replace('\\\\', '\\', str_replace('\\(', '(', str_replace('\\)', ')', $data)));
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public static function SerializeHex ($data = '') {
		return strtoupper(bin2hex($data));
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public static function UnserializeHex ($data = '') {
		return hex2bin(strtolower($data));
	}

	/**
	 * @param string $char = ''
	 *
	 * @return bool
	 */
	public static function IsSpace ($char = '') {
		return $char == ' ' || $char == "\n" || $char == "\r" || $char == "\t" || $char == "\f" || $char == "\0";
	}

	/**
	 * @param string $char = ''
	 * @param bool $space = false
	 *
	 * @return bool
	 */
	public static function IsSpecialOpen ($char = '', $space = false) {
		return $char == '<' || $char == '[' || $char == '(' || $char == '/' || ($space ? self::IsSpace($char) : false);
	}

	/**
	 * @param string $char = ''
	 * @param bool $space = false
	 *
	 * @return bool
	 */
	public static function IsSpecialClose ($char = '', $space = false) {
		return $char == '>' || $char == ']' || $char == ')' || $char == '/' || ($space ? self::IsSpace($char) : false);
	}
}