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
	public function Head(QuarkDTO $request, QuarkSession $session);
}