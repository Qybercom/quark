<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkCertificate;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkFile;
use Quark\QuarkFPMEnvironment;
use Quark\QuarkHTTPException;
use Quark\QuarkHTTPServer;
use Quark\QuarkService;
use Quark\QuarkThreadSet;
use Quark\QuarkURI;

/**
 * Class SelfHostedFPM
 *
 * @package Quark\Scenarios
 */
class SelfHostedFPM implements IQuarkTask {
	const UNKNOWN_URI = '<uri_unknown>';

	/**
	 * @var string[] $_secure
	 */
	private $_secure = array(
		'/Services',
		'/Models',
		'/ViewModels',
		'/Views',
		'/runtime',
		'/loader.php',
		'.htaccess',
	);
	
	/**
	 * @param string[] $secure = []
	 *
	 * @return string[]
	 */
	public function Secure ($secure = []) {
		if (func_num_args() != 0)
			$this->_secure = $secure;
		
		return $this->_secure;
	}

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return void
	 *
	 * @throws QuarkArchException
	 */
	public function Task ($argc, $argv) {
		$fpm = Quark::Config()->SelfHostedFPM();

		if ($fpm == null)
			throw new QuarkArchException('Attempt to start a not configured self-hosted FPM instance');

		$http = self::Instance($fpm, $this->_secure, Quark::Config()->SelfHostedFPMLog(), Quark::Config()->SelfHostedFPMCertificate());

		if (!$http->Bind())
			throw new QuarkArchException('Can not bind self-hosted FPM instance on ' . $fpm);

		QuarkThreadSet::Queue(function () use (&$http) {
			$http->Pipe();
		});
	}
	
	/**
	 * @param QuarkURI|string $uri = QuarkFPMEnvironment::SELF_HOSTED
	 * @param string[] $secure = []
	 * @param bool $log = true
	 * @param QuarkCertificate $certificate = null
	 *
	 * @return QuarkHTTPServer
	 */
	public static function Instance ($uri = QuarkFPMEnvironment::SELF_HOSTED, $secure = [], $log = true, QuarkCertificate $certificate = null) {
		return new QuarkHTTPServer($uri, function (QuarkDTO $request) use ($secure, $log) {
			$uri = $request->URI();

			$file = isset($uri->path) ? new QuarkFile(Quark::Host() . $uri->path) : null;
			$query = $uri instanceof QuarkURI ? $uri->Query() : self::UNKNOWN_URI;

			try {
				if ($file != null && $file->Exists()) {
					/**
					 * http://stackoverflow.com/a/684005/2097055
					 */
					if (preg_match('#' . implode('|', $secure) . '#Uis', $query)) {
						$response = QuarkDTO::ForStatus(QuarkDTO::STATUS_403_FORBIDDEN);

						$out = $response->SerializeResponse();
					}
					else {
						$file->Load();

						$response = new QuarkDTO();
						$response->Data($file);

						$out = $response->SerializeResponse();
					}
				}
				else {
					$env = Quark::Environment();
					$provider = null;
					foreach ($env as $i => $provider)
						if ($provider instanceof QuarkFPMEnvironment) break;

					$service = new QuarkService(
						$query,
						$provider instanceof QuarkFPMEnvironment ? $provider->Processor(QuarkFPMEnvironment::DIRECTION_REQUEST) : null,
						$provider instanceof QuarkFPMEnvironment ? $provider->Processor(QuarkFPMEnvironment::DIRECTION_RESPONSE) : null
					);

					unset($i, $provider, $env);

					$request->Processor($service->Input()->Processor());
					$service->Input()->Merge($request->UnserializeRequest($request->Raw()));
					$service->Input()->Signature($request->Signature());
					$service->InitProcessors();

					$body = QuarkHTTPServer::ServicePipeline($service);

					if ($service->Output()->Header(QuarkDTO::HEADER_LOCATION)) {
						$response = QuarkDTO::ForRedirect($service->Output()->Header(QuarkDTO::HEADER_LOCATION));
						$response->Merge($service->Session()->Output(), true, false);

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
				Quark::Log('[' . $query . '] ' . $e->log, $e->lvl);

				$response = QuarkDTO::ForStatus($e->Status());
				$out = $response->SerializeResponse();
			}
			catch (\Exception $e) {
				Quark::Log($e, Quark::LOG_FATAL);

				$response = QuarkDTO::ForStatus(QuarkDTO::STATUS_500_SERVER_ERROR);
				$out = $response->SerializeResponse();
			}
			
			if ($log)
				echo '[', QuarkDate::Now(), '] ', $request->Method(), ' ', $query, ' "', $response->Status(), '" (', $response->Header(QuarkDTO::HEADER_CONTENT_LENGTH), " bytes)\r\n";
			
			unset($file, $response, $request);

			return $out;
		}, $certificate);
	}
}