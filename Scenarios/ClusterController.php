<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkArchException;
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
	 *
	 * @throws QuarkArchException
	 */
	public function Task ($argc, $argv) {
		$controller = Quark::Config()->ClusterController();

		if ($controller == null)
			throw new QuarkArchException('Attempt to start a not configured cluster controller');

		$external = QuarkURI::FromURI(isset($argv[3]) ? $argv[3] : null);
		$internal = QuarkURI::FromURI(isset($argv[4]) ? $argv[4] : null);

		if ($external == null) $external = QuarkStreamEnvironment::URI_CONTROLLER_EXTERNAL;
		if ($internal == null) $internal = QuarkStreamEnvironment::URI_CONTROLLER_INTERNAL;

		$stream = QuarkStreamEnvironment::ClusterController(new WebSocketNetworkTransportServer(), $external, $internal);

		if (!$stream->Cluster()->ControllerBind())
			throw new QuarkArchException('Can not bind cluster controller on ' . $controller);

		QuarkThreadSet::Queue(function () use (&$stream) {
			$stream->Cluster()->ControllerPipe();
		});

		return true;
	}
}