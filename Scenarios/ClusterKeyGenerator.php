<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\Quark;

/**
 * Class ClusterKeyGenerator
 *
 * @package Quark\Scenarios
 */
class ClusterKeyGenerator implements IQuarkTask {
	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		echo Quark::GuID(), "\r\n";
	}
}