<?php
namespace Quark\TransportProviders;

use Quark\IOProcessors\WebSocketFrameIOProcessor;
use Quark\IQuarkIOProcessor;
use Quark\IQuarkTransportProviderServer;
use Quark\IQuarkTransportProtocol;

use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkServer;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkHTMLIOProcessor;

/**
 * Class WebSocketTransportServer
 *
 * @package Quark\TransportProviders
 */
class WebSocketTransportServer implements IQuarkTransportProviderServer {
	const GuID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;
	private $_buffer = '';

	/**
	 * @var IQuarkTransportProtocol
	 */
	private $_protocol;

	/**
	 * @var string $_subprotocol
	 */
	private $_subprotocol = '';

	/**
	 * @var WebSocketFrameIOProcessor|IQuarkIOProcessor $_processor
	 */
	private $_processor;

	/**
	 * @param IQuarkTransportProtocol $protocol
	 * @param string $subprotocol
	 * @param IQuarkIOProcessor $processor
	 */
	public function __construct (IQuarkTransportProtocol $protocol = null, $subprotocol = '', IQuarkIOProcessor $processor = null) {
		$this->_protocol = $protocol;
		$this->_subprotocol = $subprotocol;

		if ($processor == null)
			$this->_processor = new WebSocketFrameIOProcessor();
	}

	/**
	 * @param IQuarkTransportProtocol $protocol
	 *
	 * @return IQuarkTransportProtocol
	 */
	public function Protocol (IQuarkTransportProtocol $protocol = null) {
		if (func_num_args() != 0)
			$this->_protocol = $protocol;

		return $this->_protocol;
	}

	/**
	 * @param string $subprotocol
	 *
	 * @return string
	 */
	public function Subprotocol ($subprotocol = '') {
		if (func_num_args() != 0)
			$this->_subprotocol = $subprotocol;

		return $this->_subprotocol;
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 *
	 * @return WebSocketFrameIOProcessor|IQuarkIOProcessor
	 */
	public function Processor (IQuarkIOProcessor $processor = null) {
		if (func_num_args() != 0)
			$this->_processor = $processor;

		return $this->_processor;
	}

	/**
	 * @param QuarkURI         $uri
	 * @param QuarkCertificate $certificate
	 *
	 * @return mixed
	 */
	public function Setup (QuarkURI $uri, QuarkCertificate $certificate = null) {
		$this->_uri = $uri;
	}

	/**
	 * @param QuarkServer $server
	 *
	 * @return mixed
	 */
	public function Server (QuarkServer $server) {
		if (!$server->Bind()) return false;

		$server->Listen();

		return true;
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client, $clients) {
		// TODO: Implement OnConnect() method.
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $clients, $data) {
		if ($client->Connected()) {
			$out = $this->_processor->Decode(strlen($this->_buffer) == 0 ? $data : $this->_buffer . $data);

			if ($out === false) {
				$this->_buffer .= $data;
			}
			else {
				$this->_protocol->OnData($client, $clients, $out);
				$this->_buffer = '';
			}
		}
		else {
			if ($data != "\r\n") {
				$this->_buffer .= $data;
				return;
			}

			$request = new QuarkDTO();
			$request->UnserializeRequest($this->_buffer. "\r\n");

			$this->_buffer = '';

			$response = new QuarkDTO(new QuarkHTMLIOProcessor(), $this->_uri);
			$response->Status(101, 'Switching Protocols');
			$response->Headers(array(
				QuarkDTO::HEADER_CONNECTION => QuarkDTO::CONNECTION_UPGRADE,
				QuarkDTO::HEADER_UPGRADE => QuarkDTO::UPGRADE_WEBSOCKET,
				QuarkDTO::HEADER_SEC_WEBSOCKET_ACCEPT => base64_encode(sha1($request->Header(QuarkDTO::HEADER_SEC_WEBSOCKET_KEY) . self::GuID, true)),
			));

			if (strlen($this->_subprotocol) != 0)
				$response->Header(QuarkDTO::HEADER_SEC_WEBSOCKET_PROTOCOL, $this->_subprotocol);

			$client->Send($response->SerializeResponse());

			$client->Connected(true);
			$client->BeforeSend(function ($data) {
				return $this->_processor->Encode($data);
			});

			$this->_protocol->OnConnect($client, $clients);
		}
	}

	/**
	 * @param QuarkClient   $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client, $clients) {
		$this->_protocol->OnClose($client, $clients);
	}
}