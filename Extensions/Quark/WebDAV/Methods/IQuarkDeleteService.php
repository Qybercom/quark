<?php
namespace Quark\Extensions\Quark\WebDAV\Methods;

use Quark\IQuarkHTTPService;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Interface IQuarkDeleteService
 *
 * @package Quark\Extensions\Quark\WebDAV\Methods
 */
interface IQuarkDeleteService extends IQuarkHTTPService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Delete(QuarkDTO $request, QuarkSession $session);
}