<?php
namespace Quark\Extensions\Quark\WebDAV\Methods;

use Quark\IQuarkHTTPService;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Interface IQuarkTraceService
 *
 * @package Quark\Extensions\Quark\WebDAV\Methods
 */
interface IQuarkTraceService extends IQuarkHTTPService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Trace(QuarkDTO $request, QuarkSession $session);
}