<?php
namespace Quark\Scenarios\Generate;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkCLIBehavior;

use Quark\Extensions\CertificateAuthority\CertificateAuthority;
use Quark\Extensions\CertificateAuthority\CertificateAuthorityConfig;
use Quark\Extensions\CertificateAuthority\Providers\LetsEncrypt;

/**
 * Class Certificate
 *
 * @package Quark\Scenarios\Generate
 */
class Certificate implements IQuarkTask {
	const EXTENSION = 'scenario-certificate';
	const TARGET = './runtime/certs/';
	
	use QuarkCLIBehavior;

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		Quark::Config()->Extension(self::EXTENSION, new CertificateAuthorityConfig(new LetsEncrypt($this->HasFlag('staging', 's'))));
		Quark::Config()->ExtensionOptions(self::EXTENSION);
		
		$domains = $this->ServiceArg(0);
		$target = $this->ServiceArg(1);
		
		$this->ShellView('Generate/Certificate');
		
		if (!$domains)
			return $this->ShellLog('You must select at least 1 domain for signing' . "\r\n", Quark::LOG_WARN);

		if (!$target)
			$target = self::TARGET . '/' . $domains . '.pem';

		return $this->ShellProcess(
			' Generating certificate...',
			$this->ShellProcessStatus(Quark::LOG_OK, 'Saved to ' . $target . ".\r\n"),
			$this->ShellProcessStatus(Quark::LOG_FATAL, 'See application log for details.' . "\r\n"),
			function () use (&$domains, &$target) {
				$authority = new CertificateAuthority(self::EXTENSION);
				$certificate = $authority->SignDomains(explode(',', $domains));

				return $certificate != null && $certificate->SaveTo($target);
			}
		);
	}
}