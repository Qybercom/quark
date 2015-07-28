<?php
namespace Quark\IOProcessors;

use Quark\IQuarkIOProcessor;

use Quark\Quark;

/**
 * Class WebSocketFrameIOProcessor
 *
 * http://habrahabr.ru/company/ifree/blog/209864/
 * http://www.iana.org/assignments/websocket/websocket.xml
 *
 * @package Quark\IOProcessors
 */
class WebSocketFrameIOProcessor implements IQuarkIOProcessor {
	public static $IFrames = array(
		1 => 'text',
		2 => 'binary',
		8 => 'close',
		9 => 'ping',
		10 => 'pong'
	);

	public static $OFrames = array(
		'text' => 129,
		'close' => 136,
		'ping' => 137,
		'pong' => 138
	);

	/**
	 * @return string
	 */
	public function MimeType () {
		// TODO: Implement MimeType() method.
	}

	/**
	 * @param string $payload
	 * @param string $type
	 * @param bool $masked
	 *
	 * @return mixed
	 */
	public function Encode ($payload, $type = 'text', $masked = false) {
		$head = array();
		$length = strlen($payload);

		if (!isset(self::$OFrames[$type])) {
			Quark::Log('WebSocket error. Unknown type ' . $type);
			return '';
		}

		$head[0] = self::$OFrames[$type];

		// set mask and payload length (using 1, 3 or 9 bytes)
		if ($length > 65535) {
			$lengthBin = self::_lengthBin($length, '%064b');
			$head[1] = $masked ? 255 : 127;

			$i = 0;

			while ($i < 8) {
				$head[$i + 2] = bindec($lengthBin[$i]);

				$i++;
			}

			// most significant bit MUST be 0
			if ($head[2] > 127) {
				Quark::Log('WebSocket error 1004. Frame too large ' . $type);
				return '';
			}
		}
		elseif ($length > 125) {
			$lengthBin = self::_lengthBin($length, '%016b');
			$head[1] = $masked ? 254 : 126;

			$head[2] = bindec($lengthBin[0]);
			$head[3] = bindec($lengthBin[1]);
		}
		else $head[1] = $masked ? $length + 128 : $length;

		// convert frame-head to string:
		foreach (array_keys($head) as $i)
			$head[$i] = chr($head[$i]);

		$mask = array();

		if ($masked) {
			// generate a random mask:
			$i = 0;

			while ($i < 4) {
				$mask[$i] = chr(rand(0, 255));

				$i++;
			}

			$head = array_merge($head, $mask);
		}

		$frame = implode('', $head);
		$i = 0;

		while ($i < $length) {
			$frame .= $masked ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];

			$i++;
		}

		return $frame;
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function Decode ($data) {
		$first = self::_byte($data[0]);
		$second = self::_byte($data[1]);

		$op = bindec(substr($first, 4, 4));
		$masked = $second[0] == '1';
		$length = ord($data[1]) & 127;

		if (!$masked) {
			Quark::Log('WebSocket error 1002. Data applied for Decode must not be masked.');
			return '';
		}

		if (!isset(self::$IFrames[$op])) {
			Quark::Log('WebSocket error 1003. Unknown op code ' . $op);
			return '';
		}

		if ($length === 126) {
			$mask = substr($data, 4, 4);
			$payloadOffset = 8;

			$dataLength = bindec(self::_byte($data[2]) . self::_byte($data[3])) + $payloadOffset;
		}
		elseif ($length === 127) {
			$mask = substr($data, 10, 4);
			$payloadOffset = 14;

			$tmp = '';
			$i = 0;

			while ($i < 8) {
				$tmp .= self::_byte($data[$i + 2]);
				$i++;
			}

			$dataLength = bindec($tmp) + $payloadOffset;
			unset($tmp, $i);
		}
		else {
			$mask = substr($data, 2, 4);
			$payloadOffset = 6;

			$dataLength = $length + $payloadOffset;
		}

		/**
		 * We have to check for large frames here. socket_recv cuts at 1024 bytes
		 * so if WebSocket-frame is > 1024 bytes we have to wait until whole
		 * data is transferred.
		 */
		if (strlen($data) < $dataLength) return false;

		$payload = '';

		if ($masked) {
			$i = $payloadOffset;

			while ($i < $dataLength) {
				$j = $i - $payloadOffset;

				if (isset($data[$i]))
					$payload .= $data[$i] ^ $mask[$j % 4];

				$i++;
			}
		} else {
			$payloadOffset = $payloadOffset - 4;
			$payload = substr($data, $payloadOffset);
		}

		return $payload;
	}

	/**
	 * @param $source
	 * @param $format = '%08b'
	 *
	 * @return string
	 */
	private static function _byte ($source, $format = '%08b') {
		return sprintf($format, ord($source));
	}

	/**
	 * @param string $source
	 * @param string $format
	 *
	 * @return array
	 */
	private static function _lengthBin ($source, $format) {
		return str_split(sprintf($format, $source), 8);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) { return $raw; }
}