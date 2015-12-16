<?php
namespace Quark\Extensions\Quark\WebDAV\Methods;

use Quark\IQuarkHTTPService;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Interface IQuarkHeadService
 *
 * @package Quark\Extensions\Quark\WebDAV\Methods
 */
interface IQuarkHeadService extends IQuarkHTTPService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Head(QuarkDTO $request, QuarkSession $session);
}