<?php
namespace Quark\Extensions\BitTorrent;

use Quark\IQuarkIOProcessor;

use Quark\QuarkKeyValuePair;
use Quark\QuarkObject;
use Quark\QuarkPlainIOProcessor;

/**
 * Class BitTorrentEncode
 *
 * @package Quark\Extensions\BitTorrent
 */
class BitTorrentEncode implements IQuarkIOProcessor {
	const MIME = 'application/x-bittorrent';

	/**
	 * @var bool $_plain = false
	 */
	private $_plain = false;

	/**
	 * @param bool $plain = false
	 */
	public function __construct ($plain = false) {
		$this->Plain($plain);
	}

	/**
	 * @param bool $plain = false
	 *
	 * @return bool
	 */
	public function Plain ($plain = false) {
		if (func_num_args() != 0)
			$this->_plain = $plain;

		return $this->_plain;
	}

	/**
	 * @return string
	 */
	public function MimeType () {
		return $this->_plain ? QuarkPlainIOProcessor::MIME : self::MIME;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function Encode ($data) {
		if ($data === null) return null;

		if (is_bool($data)) return 'i' . ($data ? 1 : 0) . 'e';
		if (is_int($data)) return 'i' . $data . 'e';

		if (QuarkObject::isIterative($data)) {
			$out = 'l';

			foreach ($data as $i => &$item)
				$out .= $this->Encode($item);

			unset($i , $item);

			return $out . 'e';
		}

		if (QuarkObject::isAssociative($data)) {
			$out = 'd';

			foreach ($data as $key => &$value)
				$out .= $this->Encode($key) . $this->Encode($value);

			unset($key , $value);

			return $out . 'e';
		}

		return strlen($data) . ':' . $data;
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) {
		$out = self::DecodeAuto($raw);

		return $out != null ? $out->Key() : null;
	}

	/**
	 * @param string $raw = ''
	 *
	 * @return QuarkKeyValuePair
	 */
	public static function DecodeAuto ($raw = '') {
		$found = ($out = self::DecodeDictionary($raw))
			  || ($out = self::DecodeList($raw))
			  || ($out = self::DecodeInt($raw))
			  || ($out = self::DecodeString($raw));

		return $found ? $out : null;
	}

	/**
	 * @param string $raw = ''
	 *
	 * @return QuarkKeyValuePair
	 */
	public static function DecodeInt ($raw = '') {
		$length = strlen($raw);

		if ($length == 0) return null;
		if ($raw[0] != 'i') return null;

		$i = 1;
		$out = '';

		while ($i < $length) {
			if ($raw[$i] == 'e') break;

			$out .= $raw[$i];
			$i++;
		}

		return new QuarkKeyValuePair((int)$out, $i + 1);
	}

	/**
	 * @param string $raw = ''
	 *
	 * @return QuarkKeyValuePair
	 */
	public static function DecodeString ($raw = '') {
		$length = strlen($raw);

		if ($length == 0) return null;
		if (!is_numeric($raw[0])) return null;

		$i = 0;
		$len = '';

		while ($i < $length) {
			if ($raw[$i] == ':') break;

			$len .= $raw[$i];
			$i++;
		}

		$buffer = substr($raw, $i + 1, (int)$len);

		return new QuarkKeyValuePair($buffer, strlen($len) + strlen($buffer) + 1);
	}

	/**
	 * @param string $raw = ''
	 *
	 * @return QuarkKeyValuePair
	 */
	public static function DecodeList ($raw = '') {
		$length = strlen($raw);

		if ($length == 0) return null;
		if ($raw[0] != 'l') return null;

		$i = 1;
		$out = array();

		while ($i <= $length) {
			$item = self::DecodeAuto(substr($raw, $i));

			if ($item != null) {
				$out[] = $item->Key();
				$i += $item->Value();

				continue;
			}

			break;
		}

		return new QuarkKeyValuePair($out, $i + 1);
	}

	/**
	 * @param string $raw = ''
	 *
	 * @return QuarkKeyValuePair
	 */
	public static function DecodeDictionary ($raw = '') {
		$length = strlen($raw);

		if ($length == 0) return null;
		if ($raw[0] != 'd') return null;

		$i = 1;
		$out = array();

		while ($i <= $length) {
			$key = self::DecodeString(substr($raw, $i));
			if ($key == null) break;

			$i += $key->Value();

			$value = self::DecodeAuto(substr($raw, $i));

			if ($value != null) {
				$out[$key->Key()] = $value->Key();
				$i += $value->Value();

				continue;
			}

			break;
		}

		return new QuarkKeyValuePair($out, $i + 1);
	}

	/**
	 * @param string $raw
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		return array($raw);
	}

	/**
	 * @return bool
	 */
	public function ForceInput () {
		return false;
	}
}