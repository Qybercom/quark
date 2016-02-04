<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\QuarkArchException;
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
	 *
	 * @throws QuarkArchException
	 */
	public function Task ($argc, $argv) {
		if (!QuarkTask::AsyncQueue())
			throw new QuarkArchException('Can not bind async queue on [' . QuarkTask::QUEUE . ']');
	}
}