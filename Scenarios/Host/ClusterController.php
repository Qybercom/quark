<?php
namespace Quark\Scenarios\Host;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkThreadSet;
use Quark\QuarkStreamEnvironment;
use Quark\QuarkCLIBehavior;

use Quark\NetworkTransports\WebSocketNetworkTransportServer;

/**
 * Class ClusterController
 *
 * @package Quark\Scenarios\Host
 */
class ClusterController implements IQuarkTask {
	use QuarkCLIBehavior;

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

		$this->ShellView(
			'Host/ClusterController',
			'Starting ClusterController instance...'
		);

		if ($internal == null)
			$this->ShellArchException('Attempt to start a not configured cluster controller: Internal addr null');

		if ($external == null)
			$this->ShellArchException('Attempt to start a not configured cluster controller: External addr null');

		$stream = QuarkStreamEnvironment::ClusterController(new WebSocketNetworkTransportServer(), $external, $internal);

		if (!$stream->Cluster()->ControllerBind())
			throw new QuarkArchException('Can not bind cluster controller on [' . $internal . ' ' . $external . ']');

		$this->ShellLog('Started at [' . $internal . ' ' . $external . ']', Quark::LOG_OK);
		echo "\r\n";

		QuarkThreadSet::Queue(function () use (&$stream) {
			$stream->Cluster()->ControllerPipe();
		});
	}
}