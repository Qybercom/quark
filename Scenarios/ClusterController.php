<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkDate;
use Quark\QuarkThreadSet;
use Quark\QuarkStreamEnvironment;

use Quark\NetworkTransports\WebSocketNetworkTransportServer;

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
	 *
	 * @throws QuarkArchException
	 */
	public function Task ($argc, $argv) {
		$internal = Quark::Config()->ClusterControllerListen();
		$external = Quark::Config()->ClusterMonitor();

		if ($internal == null)
			throw new QuarkArchException('Attempt to start a not configured cluster controller: Internal addr null');

		if ($external == null)
			throw new QuarkArchException('Attempt to start a not configured cluster controller: External addr null');

		$stream = QuarkStreamEnvironment::ClusterController(new WebSocketNetworkTransportServer(), $external, $internal);

		if (!$stream->Cluster()->ControllerBind())
			throw new QuarkArchException('Can not bind cluster controller on [' . $internal . ' ' . $external . ']');

		echo '[info] ', QuarkDate::Now(), ' Cluster controller started at [', $internal, ' ', $external, "]\r\n";

		QuarkThreadSet::Queue(function () use (&$stream) {
			$stream->Cluster()->ControllerPipe();
		});

		return true;
	}
}