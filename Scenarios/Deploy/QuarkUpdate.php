<?php
namespace Quark\Scenarios\Deploy;

use Quark\IQuarkAsyncTask;
use Quark\IQuarkTask;

/**
 * Class QuarkUpdate
 *
 * @package Quark\Scenarios\Deploy
 */
class QuarkUpdate implements IQuarkTask, IQuarkAsyncTask {
	/**
	 * @return bool
	 */
	private static function _scenario () {
		echo 'Updating Quark... OK', "\r\n";

		return true;
	}

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		self::_scenario();
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

	/**
	 * @return bool
	 */
	public static function Now () {
		return self::_scenario();
	}
}