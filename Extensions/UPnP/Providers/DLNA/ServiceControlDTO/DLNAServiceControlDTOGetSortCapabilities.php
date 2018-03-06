<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\ServiceControlDTO;

use Quark\QuarkDTO;
use Quark\QuarkXMLNode;

use Quark\Extensions\Quark\SOAP\SOAPElement;
use Quark\Extensions\Quark\SOAP\SOAPEnvelope;

use Quark\Extensions\UPnP\IQuarkUPnPProviderServiceControlDTO;
use Quark\Extensions\UPnP\Providers\DLNA\Services\DLNAServiceContentDirectory;

/**
 * Class DLNAServiceControlDTOGetSortCapabilities
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\ServiceControlDTO
 */
class DLNAServiceControlDTOGetSortCapabilities implements IQuarkUPnPProviderServiceControlDTO {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkUPnPProviderServiceControlDTO
	 */
	public function UPnPProviderServiceControlRequest (QuarkDTO $request) {
		$soap = SOAPEnvelope::FromRequest($request);
		if ($soap == null) return null;

		$body = $soap->Body();
		if (sizeof($body) == 0) return null;

		$get = $body[0];
		if ($get->Name() != 'GetSortCapabilities') return null;

		return $this;
	}

	/**
	 * @return SOAPElement[]
	 */
	public function UPnPProviderServiceControlResponse () {
		return new SOAPElement('u', 'GetSortCapabilitiesResponse', DLNAServiceContentDirectory::TYPE, array(
			new QuarkXMLNode('SortCaps', array(), array(), true)
		));
	}
}