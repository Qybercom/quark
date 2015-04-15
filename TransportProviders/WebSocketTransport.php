<?php
namespace Quark\TransportProviders;

use Quark\IOProcessors\WebSocketFrameIOProcessor;
use Quark\IQuarkTransportProviderServer;

use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkServer;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkHTMLIOProcessor;

/**
 * Class WebSocketTransport
 *
 * @package Quark\TransportProviders
 */
class WebSocketTransport implements IQuarkTransportProviderServer {
	const GuID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;
	private $_buffer = '';
	private $_connected = false;

	/**
	 * @var IQuarkTransportProviderServer
	 */
	private $_protocol;

	/**
	 * @var WebSocketFrameIOProcessor $_processor
	 */
	private $_processor;

	/**
	 * @var QuarkClient[] $_clients
	 */
	private $_clients = array();

	/**
	 * @param IQuarkTransportProviderServer $protocol
	 */
	public function __construct (IQuarkTransportProviderServer $protocol = null) {
		$this->_protocol = $protocol;
		$this->_processor = new WebSocketFrameIOProcessor();
	}

	/**
	 * @return WebSocketFrameIOProcessor
	 */
	public function Processor () {
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
		$this->_protocol->Setup($uri, $certificate);
	}

	/**
	 * @param QuarkServer $server
	 *
	 * @return mixed
	 */
	public function Server (QuarkServer $server) {
		if (!$server->Bind()) return false;

		$server->Listen();
		$this->_protocol->Server($server);

		return true;
	}

	/**
	 * @param QuarkClient   $client
	 * @param QuarkClient[] $clients
	 *
	 * @return bool
	 */
	public function OnConnect ($client, $clients) {
		$this->_clients = $clients;
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData ($client, $clients, $data) {
		if ($client->Connected())
			$this->_protocol->OnData($client, $clients, $this->_processor->Decode($data));
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

			//if (strlen($this->_protocol) != 0)
				//$response->Header(QuarkDTO::HEADER_SEC_WEBSOCKET_PROTOCOL, $this->_protocol);

			$client->Send($response->SerializeResponse());

			$client->Connected(true);
			$client->BeforeSend(function ($data) {
				return $this->_processor->Encode($data);
			});

			$this->_protocol->OnConnect($client, $this->_clients);
		}
	}

	/**
	 * @param QuarkClient   $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function OnClose ($client, $clients) {
		if ($this->_connected)
			$this->_protocol->OnClose($client, $clients);
	}
}