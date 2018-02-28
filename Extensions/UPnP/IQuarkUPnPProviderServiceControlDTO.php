<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkDTO;

use Quark\Extensions\Quark\SOAP\SOAPElement;

/**
 * Interface IQuarkUPnPProviderServiceControlResponse
 *
 * @package Quark\Extensions\UPnP
 */
interface IQuarkUPnPProviderServiceControlDTO {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkUPnPProviderServiceControlDTO
	 */
	public function UPnPProviderServiceControlRequest(QuarkDTO $request);

	/**
	 * @return SOAPElement[]
	 */
	public function UPnPProviderServiceControlResponse();
}