<?php
namespace Quark;

/**
 * Interface IQuarkOptionsService
 *
 * @package Services
 */
interface IQuarkOptionsService extends IQuarkService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Options(QuarkDTO $request, QuarkSession $session);
}