<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkConfig;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkHTTPServerHost;
use Quark\QuarkHTTPException;
use Quark\QuarkArchException;
use Quark\QuarkThreadSet;

/**
 * Class SelfHostedFPM
 *
 * @package Quark\Scenarios
 */
class SelfHostedFPM implements IQuarkTask {
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

		if ($fpm == null)
			throw new QuarkArchException('Attempt to start a not configured self-hosted FPM instance');

		$namespace = Quark::Config()->Location(QuarkConfig::SERVICES);
		$base = Quark::Host() . '/' . $namespace;

		$http = new QuarkHTTPServerHost($fpm, Quark::Host(), $base, $namespace, Quark::Config()->SelfHostedFPMCertificate());

		$http->Deny($this->_secure);

		$http->On(QuarkHTTPServerHost::EVENT_HTTP_ERROR, function (QuarkDTO $request, QuarkDTO $response, $e = null) {
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

			if (Quark::Config()->SelfHostedFPMLog())
				echo '[', QuarkDate::Now(), '] ', $request->Method(), ' ', $query, ' "', $response->Status(), '" (', $response->Header(QuarkDTO::HEADER_CONTENT_LENGTH), " bytes)\r\n";
		});

		if (!$http->Bind())
			throw new QuarkArchException('Can not bind self-hosted FPM instance on ' . $fpm);

		QuarkThreadSet::Queue(function () use (&$http) {
			$http->Pipe();
		});
	}
}