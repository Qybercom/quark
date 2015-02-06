<?php
namespace Quark;

/**
 * Interface IQuarkCopyService
 *
 * @package Services
 */
interface IQuarkCopyService extends IQuarkService {
	/**
	 * @param QuarkDTO     $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	function Copy(QuarkDTO $request, QuarkSession $session);
}