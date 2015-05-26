<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;
use Quark\IQuarkTransportProtocol;
use Quark\IQuarkTransportProviderServer;

use Quark\QuarkArchException;
use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkServer;
use Quark\QuarkURI;

/**
 * Class ClusterController
 *
 * @package Quark\Scenarios
 */
class ClusterController implements IQuarkTask, IQuarkTransportProviderServer {
	const PORT = 25800;

	/**
	 * @param int   $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		$server = new QuarkServer('tcp://0.0.0.0:' . self::PORT, $this);
		$server->Action();
	}

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
	 * @throws QuarkArchException
	 */
	public function Server (QuarkServer $server) {
		if (!$server->Bind())
			throw new QuarkArchException('Cannot start Quark ClusterController at ' . $server->URI()->URI());

		$server->Listen();
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
	 * @param QuarkClient   $client
	 * @param QuarkClient[] $clients
	 * @param string        $data
	 *
	 * @return mixed
	 */
	public function OnData ($client, $clients, $data) {
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

	/**
	 * @param IQuarkTransportProtocol $protocol
	 *
	 * @return IQuarkTransportProtocol
	 */
	public function Protocol (IQuarkTransportProtocol $protocol) {
		// TODO: Implement Protocol() method.
	}
}