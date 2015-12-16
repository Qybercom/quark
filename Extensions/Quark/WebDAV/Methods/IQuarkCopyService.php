<?php
namespace Quark\Extensions\Quark\WebDAV\Methods;

use Quark\IQuarkHTTPService;

use Quark\QuarkDTO;
use Quark\QuarkSession;

/**
 * Interface IQuarkCopyService
 *
 * @package Quark\Extensions\Quark\WebDAV\Methods
 */
interface IQuarkCopyService extends IQuarkHTTPService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Copy(QuarkDTO $request, QuarkSession $session);
}