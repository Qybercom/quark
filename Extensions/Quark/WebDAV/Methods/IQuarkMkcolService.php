<?php
namespace Quark\Extensions\Quark\WebDAV\Methods;

use Quark\IQuarkHTTPService;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Interface IQuarkMkcolService
 *
 * @package Quark\Extensions\Quark\WebDAV\Methods
 */
interface IQuarkMkcolService extends IQuarkHTTPService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Mkcol(QuarkDTO $request, QuarkSession $session);
}