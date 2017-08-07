<?php
namespace Quark\Scenarios\Host;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkConfig;
use Quark\QuarkThreadSet;
use Quark\QuarkCLIBehavior;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkDate;
use Quark\QuarkHTTPServerHost;
use Quark\QuarkHTTPException;
use Quark\QuarkArchException;

/**
 * Class Fpm
 *
 * @package Quark\Scenarios\Host
 */
class Fpm implements IQuarkTask {
	use QuarkCLIBehavior;

	/**
	 * @var string[] $_secure
	 */
	private $_secure = array(
		'/.git',
		'/.idea',
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

		$this->ShellView(
			'Host/FPM',
			'Starting SelfHostedFPM instance...'
		);

		if ($fpm == null)
			$this->ShellArchException('Attempt to start a not configured self-hosted FPM instance');

		$this->ShellLog('Bind ' . $fpm->URI() . '...');

		$namespace = Quark::Config()->Location(QuarkConfig::SERVICES);
		$base = Quark::Host() . '/' . $namespace;

		$http = new QuarkHTTPServerHost($fpm, Quark::Host(), $base, $namespace, Quark::Config()->SelfHostedFPMCertificate());

		$http->Deny($this->_secure);

		$http->On(QuarkHTTPServerHost::EVENT_HTTP_ERROR, function (QuarkDTO $request, QuarkDTO $response, $e = null) {
			if (!($request->URI() instanceof QuarkURI)) {
				Quark::Log('[SelfHostedFPM] Can not parse query', Quark::LOG_FATAL);
				return;
			}

			$query = $request->URI()->Query();
			$msg = '[SelfHostedFPM] ' . $response->Status();
			$lvl = Quark::LOG_FATAL;

			if (isset($e)) {
				if ($e instanceof \Exception)
					$msg = $e->getMessage();

				if ($e instanceof QuarkHTTPException) {
					$msg = $e->log;
					$lvl = $e->lvl;
				}
			}

			Quark::Log('[' . $query . '] ' . $msg, $lvl);
		});

		if (Quark::Config()->SelfHostedFPMLog())
			$http->On(QuarkHTTPServerHost::EVENT_RESPONSE, function (QuarkDTO $request, QuarkDTO $response) {
				echo '[', QuarkDate::Now(), '] ', $request->Method(), ' ', $request->URI()->Query(), ' "', $response->Status(), '" (', $response->Header(QuarkDTO::HEADER_CONTENT_LENGTH), " bytes)\r\n";
			});

		if (!$http->Bind())
			$this->ShellArchException('Can not bind self-hosted FPM instance on ' . $fpm);

		$this->ShellLog('Started on ' . $fpm->URI(), Quark::LOG_OK);
		echo "\r\n";

		QuarkThreadSet::Queue(function () use (&$http) {
			$http->Pipe();
		});
	}
}