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
 * @important
 * http://www.lenholgate.com/blog/2011/07/websockets-is-a-stream-not-a-message-based-protocol.html
 * http://stackoverflow.com/questions/31265789/websocket-invalid-frame-header#comment50526750_31265789
 * https://github.com/CycloneCode/WSServer/blob/master/src/WSServer.php
 * https://github.com/Cyclonecode/WSServer/blob/master/src/WSFrame.php
 *
 * @package Quark\NetworkTransports
 */
class WebSocketNetworkTransportServer implements IQuarkNetworkTransport {
	const GuID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

	const OP_CONTINUATION = 0X0;
	const OP_TEXT = 0x1;
	const OP_BINARY = 0x2;
	const OP_CLOSE = 0x8;
	const OP_PING = 0x09;
	const OP_PONG = 0x0A;
	
	const FRAME_MIN_SIZE = 2;
	
	/**
	 * @var string $_buffer = ''
	 */
	private $_buffer = '';

	/**
	 * @var bool $_connected = false
	 */
	private $_connected = false;

	/**
	 * @var string $_subProtocol = ''
	 */
	private $_subProtocol = '';

	/**
	 * @param QuarkClient &$client
	 *
	 * @return void
	 */
	public function EventConnect (QuarkClient &$client) {
		// TODO: Implement EventConnect() method.
	}

	/**
	 * @param QuarkClient &$client
	 * @param string $data
	 *
	 * @return void
	 */
	public function EventData (QuarkClient &$client, $data) {
		$this->_buffer .= $data;

		if ($this->_connected) {
			$input = self::FrameIn($this->_buffer);
			$this->_buffer = '';
			
			if ($input !== null)
				$client->TriggerData($input);
		}
		else {
			if (!preg_match(QuarkDTO::HTTP_PROTOCOL_REQUEST, $this->_buffer)) return;

			$request = new QuarkDTO();
			$request->UnserializeRequest($this->_buffer. "\r\n");

			$this->_buffer = '';

			$response = new QuarkDTO(new QuarkHTMLIOProcessor());
			$response->Protocol(QuarkDTO::HTTP_VERSION_1_1);
			$response->Status(QuarkDTO::STATUS_101_SWITCHING_PROTOCOLS);
			$response->Headers(array(
				QuarkDTO::HEADER_CONNECTION => QuarkDTO::CONNECTION_UPGRADE,
				QuarkDTO::HEADER_UPGRADE => QuarkDTO::UPGRADE_WEBSOCKET,
				QuarkDTO::HEADER_SEC_WEBSOCKET_ACCEPT => base64_encode(sha1($request->Header(QuarkDTO::HEADER_SEC_WEBSOCKET_KEY) . self::GuID, true)),
			));

			if (strlen($this->_subProtocol) != 0)
				$response->Header(QuarkDTO::HEADER_SEC_WEBSOCKET_PROTOCOL, $this->_subProtocol);

			$client->Send($response->SerializeResponse());

			$this->_connected = true;

			$client->TriggerConnect();
		}
	}

	/**
	 * @param QuarkClient &$client
	 *
	 * @return void
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
		if (!isset($data[1])) return null;
		
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
	 * @param int $op = self::OP_TEXT
	 *
	 * @return string
	 */
	public static function FrameOut ($data, $op = self::OP_TEXT) {
		$length = strlen($data);
		$out = pack('C', $op | 0x80);
		
		if ($length > 125 && $length <= 0xffff)
			return $out . pack('Cn', 126, $length) . $data;
		
		if ($length > 0xffff)
			return $out . pack('CNN', 127, 0, $length) . $data;
		
		return $out . pack('C', $length) . $data;
	}
}