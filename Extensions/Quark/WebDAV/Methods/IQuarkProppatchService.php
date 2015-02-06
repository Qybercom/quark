<?php
namespace Quark;

/**
 * Interface IQuarkProppatchService
 *
 * @package Services
 */
interface IQuarkProppatchService extends IQuarkService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	function Proppatch(QuarkDTO $request, QuarkSession $session);
}