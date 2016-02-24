<?php
namespace Quark\Scenarios\Deploy;

use Quark\IQuarkAsyncTask;
use Quark\IQuarkTask;

/**
 * Class ProjectUpdate
 *
 * @package Quark\Scenarios\Deploy
 */
class ProjectUpdate implements IQuarkTask, IQuarkAsyncTask {
	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		echo 'Launch ProjectUpdate', "\r\n";
	}

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function OnLaunch ($argc, $argv) {
		// TODO: Implement OnLaunch() method.
	}
}