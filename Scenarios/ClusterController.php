<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;
use Quark\IQuarkTransportProtocol;
use Quark\IQuarkTransportProviderServer;

use Quark\QuarkArchException;
use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkServer;
use Quark\QuarkTask;
use Quark\QuarkURI;

use Quark\TransportProviders\WebSocketTransportServer;

/**
 * Class ClusterController
 *
 * @package Quark\Scenarios
 */
class ClusterController implements IQuarkTask, IQuarkTransportProviderServer {
	const PORT_CLUSTER = 25800;
	const PORT_CONTROL = 25900;

	/**
	 * @var QuarkServer $_cluster
	 */
	private $_cluster;

	/**
	 * @var QuarkServer $_control
	 */
	private $_control;

	/**
	 * @var ClusterNode[] $_nodes
	 */
	private $_nodes = array();

	/**
	 * @param int   $argc
	 * @param array $argv
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function Task ($argc, $argv) {
		$cluster = isset($argv[3]) && (int)$argv[3] != 0 ? (int)$argv[3] : false;
		$control = isset($argv[4]) && (int)$argv[4] != 0 ? (int)$argv[4] : false;

		$this->_cluster = new QuarkServer('tcp://0.0.0.0:' . ($cluster ? $cluster : self::PORT_CLUSTER), $this);
		$this->_control = new QuarkServer('ws://0.0.0.0:' . ($control ? $control : self::PORT_CONTROL),
			new WebSocketTransportServer(new ClusterMonitor($this))
		);

		if (!$this->_cluster->Bind())
			throw new QuarkArchException('Cannot start Quark ClusterController at ' . $this->_cluster->URI()->URI());

		if (!$this->_control->Bind())
			throw new QuarkArchException('Cannot start Quark ClusterMonitor at ' . $this->_control->URI()->URI());

		QuarkTask::Queue(function () {
			$this->_cluster->Pipe();
			$this->_control->Pipe();
		});
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
	public function OnConnect (QuarkClient $client, $clients) {
		$client->node = new ClusterNode($client);
		$this->_event('nodes', $this->Nodes());
		echo "connect\r\n";
	}

	/**
	 * @param QuarkClient   $client
	 * @param QuarkClient[] $clients
	 * @param string        $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $clients, $data) {
		$json = json_decode($data);

		if (!$json || !isset($json->cmd)) return true;

		switch ($json->cmd) {
			case 'auth':
				break;

			case 'state':
				if (!isset($json->state)) break;

				$client->node->State($json->state);
				$this->_event('nodes', $this->Nodes());
				break;

			case 'endpoint':
				break;

			case 'broadcast':
				break;

			default:
				break;
		}

		return true;
	}

	/**
	 * @param QuarkClient   $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client, $clients) {
		$this->_event('nodes', $this->Nodes());
	}

	/**
	 * @param IQuarkTransportProtocol $protocol
	 *
	 * @return IQuarkTransportProtocol
	 */
	public function Protocol (IQuarkTransportProtocol $protocol) {
		// TODO: Implement Protocol() method.
	}

	/**
	 * @param string $name
	 * @param array $data
	 */
	private function _event ($name, $data) {
		$this->_control->Transport()->Protocol()->Event($name, $data);
	}

	/**
	 * @return ClusterNode[]
	 */
	public function Nodes () {
		$nodes = array();
		$clients = $this->_cluster->Clients();

		foreach ($clients as $client)
			$nodes[] = $client->node;

		return $nodes;
	}

	/**
	 * @return QuarkClient[]
	 */
	public function Monitors () {
		return $this->_control->Clients();
	}
}

/**
 * Class ClusterNode
 *
 * @package Quark\Scenarios
 */
class ClusterNode {
	public $address = '';
	public $clients = 0;

	/**
	 * @param QuarkClient $socket
	 */
	public function __construct (QuarkClient $socket) {
		$this->address = $socket->URI()->URI();
	}

	/**
	 * @param $state
	 */
	public function State ($state) {
		$this->clients = isset($state->clients) ? $state->clients : $this->clients;
		$this->address = isset($state->address) ? $state->address : $this->address;
	}
}

/**
 * Class ClusterMonitor
 *
 * @package Quark\Scenarios
 */
class ClusterMonitor implements IQuarkTransportProtocol {
	/**
	 * @var ClusterController $_cluster
	 */
	private $_cluster;

	/**
	 * @param ClusterController $cluster
	 */
	public function __construct (ClusterController $cluster = null) {
		$this->_cluster = $cluster;
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client, $clients) {
		$this->Event('nodes', $this->_cluster->Nodes());
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $clients, $data) {
		$json = json_decode($data);

		if (!$json || !isset($json->cmd)) return true;

		switch ($json->cmd) {
			case 'auth':
				break;

			case 'nodes':
				$this->Event('nodes', $this->_cluster->Nodes());
				break;

			case 'broadcast':
				break;

			default:
				break;
		}

		return true;
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client, $clients) {
		// TODO: Implement OnClose() method.
	}

	/**
	 * @param string $name
	 * @param array $data
	 */
	public function Event ($name, $data) {
		$clients = $this->_cluster->Monitors();

		foreach ($clients as $client)
			$client->Send(json_encode(array(
				'event' => $name,
				'data' => $data
			)));
	}
}