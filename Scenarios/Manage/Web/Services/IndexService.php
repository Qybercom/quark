<?php
namespace Quark\Scenarios\Manage\Web\Services;

use Quark\IQuarkGetService;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Class IndexService
 *
 * @package Quark\Scenarios\Manage\Web\Services
 */
class IndexService implements IQuarkGetService {
	/**
	 * @param QuarkDTO $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Get (QuarkDTO $request, QuarkSession $session) {
		echo 'hello';
	}
}