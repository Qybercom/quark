<?php
namespace Quark\Scenarios\Host;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkCLIBehavior;
use Quark\QuarkObject;
use Quark\QuarkTask;
use Quark\QuarkThreadSet;
use Quark\QuarkURI;

/**
 * Class AsyncQueue
 *
 * @package Quark\Scenarios\Host
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
		$key = $this->ServiceArg();

		if ($key == null) {
			$key = 'async-queue-' . Quark::GuID();
			Quark::Config()->AsyncQueue($key, QuarkURI::FromURI(QuarkTask::QUEUE));
		}

		$queue = QuarkTask::AsyncQueue(QuarkObject::ConstValue($key));

		$this->ShellView(
			'Host/AsyncQueue',
			'Starting AsyncQueue instance...'
		);

		if (!$queue->Bind())
			$this->ShellArchException('Can not bind async queue on [' . $queue->URI() . ']');

		$this->ShellLog('Started on ' . $queue->URI(), Quark::LOG_OK);
		echo "\r\n";

		QuarkThreadSet::Queue(function () use (&$queue) {
			return $queue->Pipe();
		});
	}
}