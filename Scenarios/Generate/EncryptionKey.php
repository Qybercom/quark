<?php
namespace Quark\Scenarios\Generate;

use Quark\IQuarkTask;

use Quark\QuarkFile;
use Quark\QuarkCLIBehavior;
use Quark\QuarkPEMIOProcessor;

use Quark\Extensions\Quark\EncryptionAlgorithms\EncryptionAlgorithmEC;

/**
 * Class EncryptionKey
 *
 * @package Quark\Scenarios\Generate
 */
class EncryptionKey implements IQuarkTask {
	use QuarkCLIBehavior;

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		$algorithm = null;
		$algorithmType = strtoupper($argv[3]);

		if ($algorithmType == EncryptionAlgorithmEC::PEM_TYPE)
			$algorithm = new EncryptionAlgorithmEC();

		if ($algorithm == null) {
			echo $this->ShellLineWarning('Unknown encryption algorithm ' . $argv[3] . '. Can be one of [ec, rsa, dsa, dh]');

			return;
		}

		if ($algorithm instanceof EncryptionAlgorithmEC) {
			$curves = EncryptionAlgorithmEC::CurveList();

			if (!in_array($argv[4], $curves)) {
				echo $this->ShellLineWarning('Unknown Elliptic Curve name ' . $argv[4] . '. Can be one of [' . implode(', ', $curves) . ']');

				return;
			}

			$key = EncryptionAlgorithmEC::KeyGenerate($argv[4]);

			if ($key == null) {
				echo $this->ShellLineWarning('Can not generate key for curve ' . $argv[4]);

				return;
			}

			$pem = new QuarkPEMIOProcessor();
			$pemOut = $pem->Encode(array($key));

			if (isset($argv[5])) {
				$proceed = true;

				if (!isset($argv[6]) || $argv[6] != '-y') {
					echo $this->ShellLine('You really want to save generated key to ' . $this->ShellLineInfo($argv[5]) . '?' . "\r\n" . 'This will replace contents of selected file, or will create a new one, if not exists. [y/n] ');
					$answer = readline();
					$proceed = strtolower($answer) == 'y';
				}

				if ($proceed) {
					$file = new QuarkFile($argv[5]);
					$file->Content($pemOut);

					echo $file->SaveContent()
						? $this->ShellLineSuccess('Successfully generated and saved key ' . $algorithmType . ':' . $argv[4] . ' to ' . $argv[5])
						: $this->ShellLineError('An error occurred during saving key ' . $algorithmType . ':' . $argv[4] . ' to ' . $argv[5]);
				}
				else {
					echo 'Skip saving';
				}
			}
			else {
				echo $pemOut;
			}
		}
	}
}