<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

/**
 * Class ModelMigration
 *
 * @package Quark\Scenarios
 */
class ModelMigration implements IQuarkTask {
	/**
	 * @return mixed
	 */
	public function Action () {
		echo 'Hello';
	}
}