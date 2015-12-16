<?php
namespace Quark\Extensions\Quark\WebDAV\Methods;

use Quark\IQuarkHTTPService;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Interface IQuarkPropfindService
 *
 * @package Quark\Extensions\Quark\WebDAV\Methods
 */
interface IQuarkPropfindService extends IQuarkHTTPService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Propfind(QuarkDTO $request, QuarkSession $session);
}