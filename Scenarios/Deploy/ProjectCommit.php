<?php
namespace Quark\Scenarios\Deploy;

use Quark\IQuarkAsyncTask;
use Quark\IQuarkTask;

/**
 * Class ProjectCommit
 *
 * @package Quark\Scenarios\Deploy
 */
class ProjectCommit implements IQuarkTask, IQuarkAsyncTask {
	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		echo 'Launch ProjectCommit', "\r\n";
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