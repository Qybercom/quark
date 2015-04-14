<?php
namespace Quark\TransportProviders;

use Quark\IQuarkTransportProviderServer;

use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkServer;
use Quark\QuarkURI;

/**
 * Class WebSocketTransport
 *
 * @package Quark\TransportProviders
 */
class WebSocketTransport implements IQuarkTransportProviderServer {
	/**
	 * @param QuarkURI         $uri
	 * @param QuarkCertificate $certificate
	 *
	 * @return mixed
	 */
	public function Setup (QuarkURI $uri, QuarkCertificate $certificate = null) {
		// TODO: Implement Setup() method.
	}

	/**
	 * @param QuarkServer $server
	 *
	 * @return mixed
	 */
	public function Server (QuarkServer $server) {
		// TODO: Implement Server() method.
	}

	/**
	 * @param QuarkClient   $client
	 * @param QuarkClient[] $clients
	 *
	 * @return bool
	 */
	public function OnConnect ($client, $clients) {
		// TODO: Implement OnConnect() method.
	}

	/**
	 * @param QuarkClient $client
	 * @param string      $data
	 *
	 * @return mixed
	 */
	public function OnData ($client, $data) {
		// TODO: Implement OnData() method.
	}

	/**
	 * @param QuarkClient   $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function OnClose ($client, $clients) {
		// TODO: Implement OnClose() method.
	}
}