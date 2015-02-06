<?php
namespace Quark;

/**
 * Interface IQuarkPutService
 *
 * @package Services
 */
interface IQuarkPutService extends IQuarkService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	function Put(QuarkDTO $request, QuarkSession $session);
}