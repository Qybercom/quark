<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkCertificateConfiguration;
use Quark\QuarkCLIBehavior;
use Quark\QuarkDateInterval;
use Quark\QuarkException;
use Quark\QuarkObject;

/**
 * Class CertificateGenerate
 *
 * @package Quark\Scenarios\Certificate
 */
class CertificateGenerate implements IQuarkTask {
	use QuarkCLIBehavior;
	
	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return void
	 *
	 * @throws QuarkArchException
	 */
	public function Task ($argc, $argv) {
		/**
		 * @var QuarkCertificateConfiguration $config
		 */
		$config = Quark::Config()->Configuration(QuarkObject::ConstValue($this->ServiceArg()));
		
		if (!($config instanceof QuarkCertificateConfiguration))
			throw new QuarkArchException('Can not generate certificate for undefined certificate configuration');
		
		echo 'Generating certificate... ';
		
		$location = $this->ServiceArg(1);
		$certificate = $config->Certificate($this->ServiceArg(2));
		$days = $this->ServiceArg(3);
		
		if (!$location)
			throw new QuarkArchException('Can not save certificate content in undefined location');
		
		$certificate->Location(Quark::Host() . $location);
		
		if (!$certificate->Generate($days ? $days : QuarkDateInterval::DAYS_IN_YEAR) || !$certificate->SaveContent()) {
			Quark::Log('Unable to generate certificate: ' . QuarkException::LastError());
			echo 'FAIL';
		}
		else echo 'OK';
	}
}