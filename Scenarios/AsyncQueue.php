<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\QuarkTask;

/**
 * Class AsyncQueue
 *
 * @package Quark\Scenarios
 */
class AsyncQueue implements IQuarkTask {
	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		QuarkTask::AsyncQueue();
	}
}