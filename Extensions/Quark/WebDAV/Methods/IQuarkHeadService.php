<?php
namespace Quark;

/**
 * Interface IQuarkHeadService
 *
 * @package Services
 */
interface IQuarkHeadService extends IQuarkService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	function Head(QuarkDTO $request, QuarkSession $session);
}