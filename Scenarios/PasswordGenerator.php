<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkCLIBehavior;

/**
 * Class PasswordGenerator
 *
 * @package Quark\Scenarios
 */
class PasswordGenerator implements IQuarkTask {
	use QuarkCLIBehavior;

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return void
	 */
	public function Task ($argc, $argv) {
		$length = $this->ServiceArg();

		echo Quark::GeneratePassword($length ? $length : 10);
	}
}