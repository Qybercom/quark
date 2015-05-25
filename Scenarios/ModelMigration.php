<?php
namespace Quark\Scenarios;

use Quark\IQuarkTask;

use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransportClient;

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

		$client = new QuarkClient('http://canihazip.com/', new QuarkHTTPTransportClient(new QuarkDTO(), new QuarkDTO()));

		var_dump($client->Action());
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