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
			$input = self::FrameIn($this->_buffer);
			$this->_buffer = '';

			$client->TriggerData($input);
		}
		else {
			if (!preg_match(QuarkDTO::HTTP_PROTOCOL_REQUEST, $this->_buffer)) return;

			$request = new QuarkDTO();
			$request->UnserializeRequest($this->_buffer. "\r\n");

			$this->_buffer = '';

			$response = new QuarkDTO(new QuarkHTMLIOProcessor());
			$response->Protocol(QuarkDTO::HTTP_VERSION_1_1);
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
		return $this->_connected ? self::FrameOut($data) : $data;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public static function FrameIn ($data) {
		$length = ord($data[1]) & 127;

		if ($length == 126) {
			$masks = substr($data, 4, 4);
			$data = substr($data, 8);
		}
		elseif ($length == 127) {
			$masks = substr($data, 10, 4);
			$data = substr($data, 14);
		}
		else {
			$masks = substr($data, 2, 4);
			$data = substr($data, 6);
		}

		$out = '';
		$i = 0;
		$len = strlen($data);

		while ($i < $len) {
			$out .= $data[$i] ^ $masks[$i % 4];
			$i++;
		}

		return $out;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public static function FrameOut ($data) {
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