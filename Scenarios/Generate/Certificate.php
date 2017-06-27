<?php
namespace Quark\Scenarios\Generate;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkCLIBehavior;

use Quark\Extensions\SSLAuthority\SSLAuthority;
use Quark\Extensions\SSLAuthority\SSLAuthorityConfig;
use Quark\Extensions\SSLAuthority\Providers\LetsEncrypt;

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
		Quark::Config()->Extension(self::EXTENSION, new SSLAuthorityConfig(new LetsEncrypt(true)));
		Quark::Config()->ExtensionOptions(self::EXTENSION);
		
		$domains = $this->ServiceArg();
		$target = $this->ServiceArg(1);
		
		$this->ShellView('Generate/Certificate');
		
		if (!$domains)
			return $this->ShellLog('You must select at least 1 domain for signing');
		
		echo 'Generating certificate...';
		
		$ssl = new SSLAuthority(self::EXTENSION);
		$certificate = $ssl->SignDomains(explode(',', $domains));
		
		if (!$target)
			$target = self::TARGET . '/' . $domains . '.pem';
		
		if ($certificate == null || !$certificate->SaveTo($target))
			return $this->ShellLog('FAIL', Quark::LOG_FATAL)
				 . ' See application log for details.';
		
		//echo 'OK. ;
		$this->ShellLog('OK', Quark::LOG_OK); echo '.', "\r\n",
		'Saved to ' . $target . '.';
	}
}