<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkFile;
use Quark\QuarkFPMEnvironment;
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

			try {
				if ($file->Exists()) {
					$file->Load();

					$response = new QuarkDTO();
					$response->Data($file);

					$out = $response->SerializeResponse();
				}
				else {
					$env = Quark::Environment();
					$provider = null;
					foreach ($env as $i => $provider)
						if ($provider instanceof QuarkFPMEnvironment) break;

					$service = new QuarkService(
						$request->URI()->Query(),
						$provider instanceof QuarkFPMEnvironment ? $provider->Processor(QuarkFPMEnvironment::PROCESSOR_REQUEST) : null,
						$provider instanceof QuarkFPMEnvironment ? $provider->Processor(QuarkFPMEnvironment::PROCESSOR_RESPONSE) : null
					);

					unset($i, $provider, $env);

					$request->Processor($service->Input()->Processor());
					$service->Input()->Merge($request->UnserializeRequest($request->Raw()));

					$body = QuarkHTTPServer::ServicePipeline($service);

					if ($service->Output()->Header(QuarkDTO::HEADER_LOCATION)) {
						$response = QuarkDTO::ForRedirect($service->Output()->Header(QuarkDTO::HEADER_LOCATION));
						$response->FullControl(true);
						$response->Merge($service->Session()->Output());

						$out = $response->SerializeResponse();
					}
					else {
						$out = $service->Output()->SerializeResponseHeaders() . "\r\n\r\n" . $body;
						$response = $service->Output();
					}

					unset($body, $service);
				}
			}
			catch (QuarkHTTPException $e) {
				Quark::Log($e);

				$response = QuarkDTO::ForStatus(QuarkDTO::STATUS_404_NOT_FOUND);
				$out = $response->SerializeResponse();
			}
			catch (\Exception $e) {
				Quark::Log($e);

				$response = QuarkDTO::ForStatus(QuarkDTO::STATUS_500_SERVER_ERROR);
				$out = $response->SerializeResponse();
			}

			echo '[', QuarkDate::Now(), '] ', $request->Method(), ' ', $request->URI()->Query(), ': ', $response->Status(), ' (', $response->Header(QuarkDTO::HEADER_CONTENT_LENGTH), ")\r\n";
			unset($file, $response, $request);

			return $out;
		});

		if (!$http->Bind())
			throw new QuarkArchException('Can not bind self-hosted FPM instance on ' . $fpm);

		QuarkThreadSet::Queue(function () use (&$http) {
			$http->Pipe();
		});
	}
}