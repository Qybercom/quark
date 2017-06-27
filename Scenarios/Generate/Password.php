<?php
namespace Quark\Scenarios\Generate;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkCLIBehavior;
use Quark\QuarkCLIColor;

/**
 * Class Password
 *
 * @package Quark\Scenarios\Generate
 */
class Password implements IQuarkTask {
	use QuarkCLIBehavior;

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return void
	 */
	public function Task ($argc, $argv) {
		$length = $this->ServiceArg();
		
		$this->ShellView(
			'Generate/Password',
			'Your generated password is: ' . $this->ShellLine(Quark::GeneratePassword($length ? $length : 10), new QuarkCLIColor(
				QuarkCLIColor::GREEN
			))
		);
		
		$this->ShellView(
			'Generate/Password-test',
			'Your generated password is: ' . $this->ShellLineSuccess(Quark::GeneratePassword($length ? $length : 10)),
			function () {
				echo ' Testing process 1...';
				sleep(1);
				echo ' ', $this->ShellLineSuccess('OK');
				
				echo ' Testing process 2...';
				sleep(1);
				echo ' ', $this->ShellLineWarning('WARN');
				
				echo ' Testing process 3...';
				sleep(1);
				echo ' ', $this->ShellLineError('FAIL');
			}
		);
	}
}