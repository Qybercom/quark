<?php
namespace Quark\Extensions\Quark\Compressors;

use Quark\IQuarkCompressor;
use Quark\Quark;

/**
 * Class LZWCompressor
 *
 * @important Experimental methods seems to be not working for large amount of text, maybe because of the LZW itself.
 * 			  Barely working on dictionary size of 246
 * 			  Need more investigation and possible refactoring.
 *
 * @note ASCII codes over 255 seems to be working only with poly-fill mb_* functions
 * 		 Also need more investigation and refactoring
 *
 * https://www2.cs.duke.edu/csed/curious/compression/lzw.html
 * https://www.dcode.fr/lzw-compression
 * https://neerc.ifmo.ru/wiki/index.php?title=%D0%90%D0%BB%D0%B3%D0%BE%D1%80%D0%B8%D1%82%D0%BC_LZW
 * https://github.com/vrana/php-lzw/blob/master/lzw.inc.php
 * https://habr.com/ru/post/152683/
 *
 * @package Quark\Extensions\Quark\Compressors
 */
class LZWCompressor implements IQuarkCompressor {
	const ASCII_MAX_DEFAULT = 128;
	const ASCII_MAX_EXTENDED = 256;

	/**
	 * @var string[] $_dictionary = []
	 */
	private $_dictionary = array();

	/**
	 * @param string[] $dictionary = []
	 */
	public function __construct ($dictionary = []) {
		if (func_num_args() == 0)
			$dictionary = self::DictionaryDefault();

		$this->Dictionary($dictionary);
	}

	/**
	 * @param string[] $dictionary = []
	 *
	 * @return string[]
	 */
	public function Dictionary ($dictionary = []) {
		if (func_num_args() != 0)
			$this->_dictionary = $dictionary;

		return $this->_dictionary;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function CompressExperimental ($data) {
		$len = strlen($data);
		$out = '';

		if ($len != 0) {
			$dictionary = array_flip($this->_dictionary);

			$word = '';

			/** @noinspection PhpUnusedLocalVariableInspection */
			$buffer = '';
			/** @noinspection PhpUnusedLocalVariableInspection */
			$item = '';
			$i = 0;

			while ($i < $len) {
				$item = $data[$i];
				$buffer = $word . $item;

				if (isset($dictionary[$buffer])) $word = $buffer;
				else {
					$out .= self::mb_chr($dictionary[$word]);
					$dictionary[$buffer] = sizeof($dictionary);
					$word = $item;
				}

				$i++;
			}

			$out .= self::mb_chr($dictionary[$word]);
		}

		return $out;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function Compress ($data) {
		$len = strlen($data);
		$dictionary = array_flip($this->_dictionary);
		$dictionary_count = sizeof($dictionary);

		$bits = 8;
		$codes = array();
		$word = '';
		$rest = 0;
		$rest_length = 0;
		$out = '';

		$i = 0;
		while ($i <= $len) {
			$x = substr($data, $i, 1);

			if (strlen($x) && isset($dictionary[$word . $x])) $word .= $x;
			elseif ($i) {
				$codes[] = $dictionary[$word];
				$dictionary[$word . $x] = sizeof($dictionary);
				$word = $x;
			}

			$i++;
		}

		foreach ($codes as $i => &$code) {
			$rest = ($rest << $bits) + $code;
			$rest_length += $bits;
			$dictionary_count++;

			if ($dictionary_count >> $bits) $bits++;

			while ($rest_length > 7) {
				$rest_length -= 8;
				$out .= chr($rest >> $rest_length);
				$rest &= (1 << $rest_length) - 1;
			}
		}

		return $out . ($rest_length ? chr($rest << (8 - $rest_length)) : '');
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function DecompressExperimental ($data) {
		$len = strlen($data);
		$out = '';

		if ($len != 0) {
			$dictionary = $this->_dictionary;

			$prev = self::mb_ord($data[0]);
			$out .= $dictionary[$prev];

			/** @noinspection PhpUnusedLocalVariableInspection */
			$buffer = '';
			/** @noinspection PhpUnusedLocalVariableInspection */
			$item = '';
			$i = 1;

			while ($i < $len) {
				$item = self::mb_ord($data[$i]);
				$buffer = $dictionary[$item];

				$out .= $buffer;
				$dictionary[] = $dictionary[$prev] . $buffer[0];
				$prev = $item;

				$i++;
			}
		}

		return $out;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function Decompress ($data) {
		$len = strlen($data);
		$dictionary = $this->_dictionary;
		$dictionary_count = sizeof($dictionary);

		$bits = 8;
		$codes = array();
		$word = '';
		$rest = 0;
		$rest_length = 0;
		$out = '';

		$i = 0;
		while ($i < $len) {
			$rest = ($rest << 8) + self::mb_ord($data[$i]);
			$rest_length += 8;

			if ($rest_length >= $bits) {
				$rest_length -= $bits;
				$codes[] = $rest >> $rest_length;
				$rest &= (1 << $rest_length) - 1;
				$dictionary_count++;

				if ($dictionary_count >> $bits) $bits++;
			}

			$i++;
		}

		foreach ($codes as $i => &$code) {
			$element = isset($dictionary[$code])
				? $dictionary[$code]
				: $word . $word[0];

			$out .= $element;

			if ($i)
				$dictionary[] = $word . $element[0];

			$word = $element;
		}

		return $out;
	}

	/**
	 * @return string[]
	 */
	public static function DictionaryDefault () {
		return range("\0", "\xFF");
	}

	/**
	 * @param int $max = self::ASCII_MAX_DEFAULT
	 *
	 * @return string[]
	 */
	public static function DictionaryASCII ($max = self::ASCII_MAX_DEFAULT) {
		$i = 0;
		$out = array();

		while ($i < $max) {
			$out[] = chr($i);

			$i++;
		}

		return $out;
	}

	/**
	 * @return string[]
	 */
	public static function DictionaryBase64 () {
		return str_split(Quark::ALPHABET_BASE64);
	}

	/**
	 * https://stackoverflow.com/q/11304582/2097055
	 * https://stackoverflow.com/a/10333307/2097055
	 *
	 * @param string $char = ''
	 * @param bool $native = false
	 *
	 * @return int
	 */
	public static function mb_ord ($char = '', $native = false) {
		if ($native && function_exists('\mb_ord')) return \mb_ord($char);

		$out = ord($char);

		if (extension_loaded('mbstring')) {
			$result = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'ASCII'));

			if (isset($result[1]))
				$out = $result[1];
		}

		return $out;
	}

	/**
	 * https://stackoverflow.com/questions/1365583/how-to-get-the-character-from-unicode-code-point-in-php#comment100269700_1365610
	 *
	 * @param int $id
	 * @param bool $native = false
	 *
	 * @return string
	 */
	public static function mb_chr ($id, $native = false) {
		return $native && function_exists('\mb_chr') ? \mb_chr($id) : mb_convert_encoding(pack('N', $id), 'ASCII', 'UCS-4BE');
	}
}