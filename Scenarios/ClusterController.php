<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\QuarkStreamEnvironmentProvider;

use Quark\TransportProviders\WebSocketTransportServer;

/**
 * Class ClusterController
 *
 * @package Quark\Scenarios
 */
class ClusterController implements IQuarkTask {
	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		$streams = new QuarkStreamEnvironmentProvider();
		$streams->ClusterController(new WebSocketTransportServer());
	}
}