<?php
namespace Quark;

/**
 * Interface IQuarkPropfindService
 *
 * @package Services
 */
interface IQuarkPropfindService extends IQuarkService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Propfind(QuarkDTO $request, QuarkSession $session);
}