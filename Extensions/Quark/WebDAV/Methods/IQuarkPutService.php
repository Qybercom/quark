<?php
namespace Quark\Extensions\Quark\WebDAV\Methods;

use Quark\IQuarkHTTPService;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Interface IQuarkPutService
 *
 * @package Quark\Extensions\Quark\WebDAV\Methods
 */
interface IQuarkPutService extends IQuarkHTTPService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Put(QuarkDTO $request, QuarkSession $session);
}