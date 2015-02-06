<?php
namespace Quark;

/**
 * Interface IQuarkTraceService
 *
 * @package Services
 */
interface IQuarkTraceService extends IQuarkService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	function Trace(QuarkDTO $request, QuarkSession $session);
}