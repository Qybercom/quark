<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\QuarkURI;
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
	 */
	public function Task ($argc, $argv) {
		$external = QuarkURI::FromURI(isset($argv[3]) ? $argv[3] : null);
		$internal = QuarkURI::FromURI(isset($argv[4]) ? $argv[4] : null);

		if ($external == null) $external = QuarkStreamEnvironment::URI_CONTROLLER_EXTERNAL;
		if ($internal == null) $internal = QuarkStreamEnvironment::URI_CONTROLLER_INTERNAL;

		$stream = QuarkStreamEnvironment::ClusterController(new WebSocketNetworkTransportServer(), $external, $internal);

		if (!$stream->Cluster()->ControllerBind()) return false;

		QuarkThreadSet::Queue(function () use ($stream) {
			$stream->Cluster()->ControllerPipe();
		});

		return true;
	}
}