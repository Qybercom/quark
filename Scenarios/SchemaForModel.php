<?php
namespace Quark\Scenarios;

use Quark\IQuarkAsyncTask;
use Quark\IQuarkTask;

use Quark\QuarkModel;
use Quark\QuarkSQL;

/**
 * Class SchemaForModel
 *
 * @package Quark\Scenarios
 */
class SchemaForModel implements IQuarkTask, IQuarkAsyncTask {
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
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		$class = $argv[3];
		$model = new QuarkModel(new $class);

		echo 'Generating table for ', $class, '... ',
			($model->GenerateSchema(array(
				QuarkSQL::OPTION_SCHEMA_GENERATE_PRINT => false
			)) ? 'OK' : 'FAIL');
	}
}