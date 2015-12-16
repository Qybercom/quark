<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkDTO;
use Quark\QuarkFile;
use Quark\QuarkHTTPException;
use Quark\QuarkHTTPServer;
use Quark\QuarkService;
use Quark\QuarkThreadSet;

/**
 * Class SelfHostedFPM
 *
 * @package Quark\Scenarios
 */
class SelfHostedFPM implements IQuarkTask {
	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function Task ($argc, $argv) {
		$fpm = Quark::Config()->SelfHostedFPM();

		if ($fpm == null)
			throw new QuarkArchException('Attempt to start a not configured self-hosted FPM instance');

		$http = new QuarkHTTPServer($fpm, function (QuarkDTO $request) {
			$file = new QuarkFile(Quark::Host() . $request->URI()->path);

			if ($file->Exists()) {
				try {
					$file->Load();

					$response = new QuarkDTO();
					$response->Data($file);

					return $response->SerializeResponse();
				}
				catch (QuarkHTTPException $e) {
					Quark::Log($e);
					return QuarkDTO::ForStatus(QuarkDTO::STATUS_500_SERVER_ERROR)->SerializeResponse();
				}
			}

			try {
				$service = new QuarkService($request->URI()->Query());
				$service->Input()->Merge($request);

				$response = QuarkHTTPServer::ServicePipeline($service);

				return $service->Output()->SerializeResponseHeaders() . "\r\n\r\n" . $response;
			}
			catch (QuarkHTTPException $e) {
				return QuarkDTO::ForStatus(QuarkDTO::STATUS_404_NOT_FOUND)->SerializeResponse();
			}
		});

		if (!$http->Bind())
			throw new QuarkArchException('Can not bind self-hosted FPM instance on ' . $fpm);

		QuarkThreadSet::Queue(function () use (&$http) {
			$http->Pipe();
		});
	}
}