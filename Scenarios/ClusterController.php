<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;
use Quark\IQuarkTransportProvider;

use Quark\QuarkArchException;
use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkServer;
use Quark\QuarkStreamEnvironmentProvider;
use Quark\QuarkThreadSet;
use Quark\QuarkURI;

use Quark\TransportProviders\WebSocketTransportServer;

/**
 * Class ClusterController
 *
 * @package Quark\Scenarios
 */
class ClusterController implements IQuarkTask, IQuarkTransportProvider {
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
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		$streams = new QuarkStreamEnvironmentProvider();
		$streams->StartClusterController(new WebSocketTransportServer());
	}

	/**
	 * @param int   $argc
	 * @param array $argv
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function Task1 ($argc, $argv) {
		$cluster = isset($argv[3]) && (int)$argv[3] != 0 ? (int)$argv[3] : false;
		$control = isset($argv[4]) && (int)$argv[4] != 0 ? (int)$argv[4] : false;

		$cluster = 'tcp://0.0.0.0:' . ($cluster ? $cluster : self::PORT_CLUSTER);
		$control = 'ws://0.0.0.0:' . ($control ? $control : self::PORT_CONTROL);

		//$cluster = 'tcp://0.0.0.0:0';
		//$control = 'ws://0.0.0.0:0';

		$this->_cluster = new QuarkServer($cluster, $this);
		$this->_control = new QuarkServer($control,
			new WebSocketTransportServer(new ClusterMonitor($this))
		);

		if (!$this->_cluster->Bind())
			throw new QuarkArchException('Cannot start Quark ClusterController at ' . $this->_cluster->URI()->URI());

		if (!$this->_control->Bind())
			throw new QuarkArchException('Cannot start Quark ClusterMonitor at ' . $this->_control->URI()->URI());

		QuarkThreadSet::Queue(function () {
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
	 * @param QuarkClient $client
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client) {
		$client->node = new ClusterNode($client);
		$this->_event('nodes', $this->Nodes());
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $data) {
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
				if (!isset($json->service)) break;

				$this->_event('broadcast', $json->service);
				break;

			default:
				break;
		}

		return true;
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client) {
		$this->_event('nodes', $this->Nodes());
	}

	/**
	 * @param string $name
	 * @param array $data
	 */
	private function _event ($name, $data) {
		echo '[cluster] event ', $name, ' ', print_r($data, true), "\r\n";
		$this->_control->Transport()->Protocol()->Event($name, $data);

		$clients = $this->_cluster->Clients();

		foreach ($clients as $client) {
			$client->Send(json_encode(array(
				'event' => $name,
				'data' => $data
			)));
			//usleep(10000);
		}
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
	public $clients = 0;
	public $peers = 0;

	public $address = '';
	public $internal = '';
	public $external = '';

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
		$this->peers = isset($state->peers) ? $state->peers : $this->peers;

		$this->internal = isset($state->internal) ? $state->internal : $this->internal;
		$this->external = isset($state->external) ? $state->external : $this->external;
	}
}

/**
 * Class ClusterMonitor
 *
 * @package Quark\Scenarios
 */
class ClusterMonitor implements IQuarkTransportProvider {
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
	 * @param QuarkURI $uri
	 * @param QuarkCertificate $certificate
	 *
	 * @return mixed
	 */
	public function Setup (QuarkURI $uri, QuarkCertificate $certificate = null) {
		// TODO: Implement Setup() method.
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client) {
		$this->Event('nodes', $this->_cluster->Nodes());
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $data) {
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
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client) {
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