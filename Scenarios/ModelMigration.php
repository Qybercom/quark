<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;

/**
 * Class ModelMigration
 *
 * @package Quark\Scenarios
 */
class ModelMigration implements IQuarkTask {
	/**
	 * @return mixed
	 */
	public function Action1 () {
		//echo 'Hello';
		var_dump(isset($_SERVER['SERVER_ADDR']));
		echo gethostbyname(gethostname());

		var_dump(QuarkHTTPClient::To('http://canihazip.com/', new QuarkDTO()));
	}

	/**
	 * @param int   $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		var_dump(isset($_SERVER['SERVER_ADDR']));
		echo gethostbyname(gethostname());
	}
}