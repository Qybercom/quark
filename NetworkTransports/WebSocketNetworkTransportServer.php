<?php
namespace Quark\NetworkTransports;

use Quark\IQuarkNetworkTransport;

use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTMLIOProcessor;

/**
 * Class WebSocketNetworkTransportServer
 *
 * http://habrahabr.ru/company/ifree/blog/209864/
 * http://www.iana.org/assignments/websocket/websocket.xml
 * http://www.askdev.ru/a/26682
 * http://www.sanwebe.com/2013/05/chat-using-websocket-php-socket
 *
 * @package Quark\NetworkTransports
 */
class WebSocketNetworkTransportServer implements IQuarkNetworkTransport {
	const GuID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

	/**
	 * @var string $_buffer
	 */
	private $_buffer = '';

	/**
	 * @var bool $_connected = false
	 */
	private $_connected = false;

	/**
	 * @var string $_subprotocol
	 */
	private $_subprotocol = '';

	/**
	 * @param QuarkClient &$client
	 *
	 * @return mixed
	 */
	public function EventConnect (QuarkClient &$client) {
		// TODO: Implement EventConnect() method.
	}

	/**
	 * @param QuarkClient &$client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function EventData (QuarkClient &$client, $data) {
		$this->_buffer .= $data;

		if ($this->_connected) {
			$input = $this->_input();

			if ($input !== false)
				$client->TriggerData($input);
		}
		else {
			if (!preg_match(QuarkDTO::HTTP_PROTOCOL_REQUEST, $this->_buffer)) return;

			$request = new QuarkDTO();
			$request->UnserializeRequest($this->_buffer. "\r\n");

			$this->_buffer = '';

			$response = new QuarkDTO(new QuarkHTMLIOProcessor());
			$response->Status(101, 'Switching Protocols');
			$response->Headers(array(
				QuarkDTO::HEADER_CONNECTION => QuarkDTO::CONNECTION_UPGRADE,
				QuarkDTO::HEADER_UPGRADE => QuarkDTO::UPGRADE_WEBSOCKET,
				QuarkDTO::HEADER_SEC_WEBSOCKET_ACCEPT => base64_encode(sha1($request->Header(QuarkDTO::HEADER_SEC_WEBSOCKET_KEY) . self::GuID, true)),
			));

			if (strlen($this->_subprotocol) != 0)
				$response->Header(QuarkDTO::HEADER_SEC_WEBSOCKET_PROTOCOL, $this->_subprotocol);

			$client->Send($response->SerializeResponse());

			$this->_connected = true;

			$client->TriggerConnect();
		}
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
	 * @return bool|string
	 */
	private function _input () {
		$data = $this->_buffer;

		if (strlen($data) < 2) return false;

		$second = self::_byte($data[1]);
		$masked = $second[0] == '1';
		$length = ord($data[1]) & 127;

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
				if (isset($data[$i + 2]))
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
		}
		else {
			$payloadOffset = $payloadOffset - 4;
			$payload = substr($data, $payloadOffset);
		}

		$this->_buffer = substr($this->_buffer, $dataLength);

		return $payload;
	}

	/**
	 * @param QuarkClient &$client
	 *
	 * @return mixed
	 */
	public function EventClose (QuarkClient &$client) {
		$client->TriggerClose();
	}

	/**
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function Send ($data) {
		if (!$this->_connected) return $data;

		$b1 = 0x80 | (0x1 & 0x0f);

		$length = strlen($data);

		if ($length <= 125)
			return pack('CC', $b1, $length) . $data;

		if ($length > 125 && $length < 65536)
			return pack('CCn', $b1, 126, $length) . $data;

		if ($length >= 65536)
			return pack('CCNN', $b1, 127, $length) . $data;

		return $data;
	}
}