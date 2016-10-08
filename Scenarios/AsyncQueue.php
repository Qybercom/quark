<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\QuarkArchException;
use Quark\QuarkCLIBehavior;
use Quark\QuarkObject;
use Quark\QuarkTask;

/**
 * Class AsyncQueue
 *
 * @package Quark\Scenarios
 */
class AsyncQueue implements IQuarkTask {
	use QuarkCLIBehavior;

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function Task ($argc, $argv) {
		$queue = $this->ServiceArg();
		$queue = $queue == null ? QuarkTask::QUEUE : QuarkObject::ConstValue($queue);

		if (!QuarkTask::AsyncQueue($queue))
			throw new QuarkArchException('Can not bind async queue on [' . QuarkObject::Stringify($queue) . ']');
	}
}