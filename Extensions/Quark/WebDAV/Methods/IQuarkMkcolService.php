<?php
namespace Quark;

/**
 * Interface IQuarkMkcolService
 *
 * @package Services
 */
interface IQuarkMkcolService extends IQuarkService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Mkcol(QuarkDTO $request, QuarkSession $session);
}