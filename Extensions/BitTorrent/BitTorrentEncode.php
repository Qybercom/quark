<?php
namespace Quark\Extensions\BitTorrent;

use Quark\IQuarkIOProcessor;

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
		if (is_bool($data)) return 'd' . ($data ? 1 : 0) . 'e';
		if (is_int($data)) return 'd' . $data . 'e';
		if (is_float($data)) return 'd' . ((int)$data) . 'e';
		if (is_string($data)) return strlen($data) . ':' . $data;

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

		return null;
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
		return array($raw);
	}

	/**
	 * @return bool
	 */
	public function ForceInput () {
		return false;
	}
}