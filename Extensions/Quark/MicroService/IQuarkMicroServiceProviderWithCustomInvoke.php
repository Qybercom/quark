<?php
namespace Quark\Extensions\Quark\MicroService;

use Quark\QuarkDTO;

/**
 * Interface IQuarkMicroServiceProviderWithCustomInvoke
 *
 * @package Quark\Extensions\Quark\MicroService
 */
interface IQuarkMicroServiceProviderWithCustomInvoke extends IQuarkMicroServiceProvider {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return QuarkDTO
	 */
	public function MicroServiceRequest(QuarkDTO $request);

	/**
	 * @param QuarkDTO $response
	 *
	 * @return QuarkDTO
	 */
	public function MicroServiceResponse(QuarkDTO $response);
}