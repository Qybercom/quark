<?php
namespace Quark\Extensions\Quark\WebDAV\Methods;

use Quark\IQuarkHTTPService;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Interface IQuarkProppatchService
 *
 * @package Quark\Extensions\Quark\WebDAV\Methods
 */
interface IQuarkProppatchService extends IQuarkHTTPService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Proppatch(QuarkDTO $request, QuarkSession $session);
}