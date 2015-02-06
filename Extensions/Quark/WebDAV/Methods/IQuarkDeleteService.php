<?php
namespace Quark;

/**
 * Interface IQuarkDeleteService
 *
 * @package Services
 */
interface IQuarkDeleteService extends IQuarkService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	function Delete(QuarkDTO $request, QuarkSession $session);
}