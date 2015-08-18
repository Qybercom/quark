<?php
namespace Quark\TransportProviders;

use Quark\IQuarkIOProcessor;
use Quark\IQuarkTransportProvider;
use Quark\IQuarkIntermediateTransportProvider;

use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTMLIOProcessor;

use Quark\IOProcessors\WebSocketFrameIOProcessor;

/**
 * Class WebSocketTransportServer
 *
 * @package Quark\TransportProviders
 */
class WebSocketTransportServer implements IQuarkTransportProvider, IQuarkIntermediateTransportProvider {
	const GuID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

	/**
	 * @var string $_buffer
	 */
	private $_buffer = '';

	/**
	 * @var IQuarkTransportProvider
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
	 * @param IQuarkTransportProvider $protocol
	 * @param string $subprotocol
	 * @param IQuarkIOProcessor $processor
	 */
	public function __construct (IQuarkTransportProvider $protocol = null, $subprotocol = '', IQuarkIOProcessor $processor = null) {
		$this->_protocol = $protocol;
		$this->_subprotocol = $subprotocol;

		if ($processor == null)
			$this->_processor = new WebSocketFrameIOProcessor();
	}

	/**
	 * @param IQuarkTransportProvider $protocol
	 *
	 * @return IQuarkTransportProvider
	 */
	public function Protocol (IQuarkTransportProvider $protocol = null) {
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
	 * @param QuarkClient $client
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client) {
		// TODO: Implement OnConnect() method.
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function OnData (QuarkClient $client, $data) {
		if ($client->Connected()) {
			try {
				$this->_buffer .= $data;
				$out = $this->_processor->Decode($this->_buffer);

				if ($out !== false) {
					$this->_buffer = '';
					$this->_protocol->OnData($client, $out);
				}
			}
			catch (\Exception $e) {
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

			$client->Connected(true);
			$client->BeforeSend(function ($data) {
				return $this->_processor->Encode($data);
			});

			$this->_protocol->OnConnect($client);
		}
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client) {
		$this->_protocol->OnClose($client);
	}
}